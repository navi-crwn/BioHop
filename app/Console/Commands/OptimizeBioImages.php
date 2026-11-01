<?php

namespace App\Console\Commands;

use Hop\Bio\Support\BioUploads;
use Hop\Bio\Support\ImageOptimizer;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Shrinks the pictures uploaded before the optimizer existed: PNG and JPEG files in avatars/, bio-images/,
 * bio-thumbnails/ and bio-icons/ become WebP at the size the page can show, and every place that points to the
 * old file (pages, blocks, carousel pictures) is updated. Dry run unless --apply. Favicons stay PNG.
 */
class OptimizeBioImages extends Command
{
    protected $signature = 'bio:optimize-images {--apply : Write the changes (without it, only report)} {--min=30 : Skip files smaller than this many KB}';

    protected $description = 'Convert old uploaded pictures to small WebP files and update what refers to them';

    /** table => columns that can hold a picture path */
    private const REFERENCES = [
        'bio_pages' => ['avatar_url', 'background_image', 'background_value', 'cover_image', 'og_image'],
        'bio_links' => ['thumbnail_url', 'custom_icon', 'content', 'settings'],
    ];

    public function handle(): int
    {
        $disk = Storage::disk('public');
        $apply = (bool) $this->option('apply');
        $min = (int) $this->option('min') * 1024;
        $favicons = DB::table('bio_pages')->whereNotNull('favicon')->pluck('favicon')->map(fn ($f) => preg_replace('#^/?storage/#', '', $f))->all();
        $before = $after = $files = 0;
        foreach (array_keys(ImageOptimizer::EDGE) as $dir) {
            foreach ($disk->files($dir) as $path) {
                if (! preg_match('/\.(png|jpe?g)$/i', $path) || in_array($path, $favicons, true) || $disk->size($path) < $min) {
                    continue;
                }
                $size = $disk->size($path);
                try {
                    $file = new UploadedFile($disk->path($path), basename($path), null, null, true);
                    $new = ImageOptimizer::store($file, $dir);
                } catch (\Throwable $e) {
                    $this->warn("skip {$path}: ".$e->getMessage());

                    continue;
                }
                $newSize = $disk->size($new);
                if ($new === $path || $newSize >= $size) {
                    $disk->delete($new === $path ? [] : [$new]);

                    continue;
                }
                $files++;
                $before += $size;
                $after += $newSize;
                $this->line(sprintf('%-48s %7.0f KB -> %6.0f KB', $path, $size / 1024, $newSize / 1024));
                if (! $apply) {
                    $disk->delete($new);

                    continue;
                }
                // plain paths, and the form JSON columns use (slashes escaped)
                foreach ([[$path, $new], [str_replace('/', '\\/', $path), str_replace('/', '\\/', $new)]] as [$from, $to]) {
                    foreach (self::REFERENCES as $table => $columns) {
                        foreach ($columns as $col) {
                            DB::update("UPDATE {$table} SET {$col} = REPLACE({$col}, ?, ?) WHERE {$col} LIKE ?", [$from, $to, '%'.$from.'%']);
                        }
                    }
                }
                BioUploads::deleteIfUnused($path);
            }
        }
        $this->info(sprintf('%d pictures, %.1f MB -> %.1f MB%s', $files, $before / 1048576, $after / 1048576, $apply ? '' : ' (dry run: add --apply to write it)'));

        return self::SUCCESS;
    }
}
