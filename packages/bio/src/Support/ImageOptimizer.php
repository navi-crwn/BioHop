<?php

namespace Hop\Bio\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Every picture a member uploads is shrunk before it is stored: scaled down to the size the page can ever show,
 * turned into WebP and rotated by its camera orientation. A 6 MB phone photo becomes tens of kilobytes, so the
 * public page stays light on a weak connection. Animated GIFs cannot be resized here: they are kept but capped.
 */
class ImageOptimizer
{
    public const MAX_PIXELS = 36_000_000;

    /** A decoded picture takes about 4 bytes a pixel, plus working copies: refuse what would not fit the memory limit. */
    public static function fitsInMemory(int $w, int $h): bool
    {
        $limit = ini_parse_quantity((string) ini_get('memory_limit'));
        if ($limit <= 0) {
            return true;
        }

        return $w * $h * 6 + 16 * 1024 * 1024 < $limit - memory_get_usage(true);
    }

    public const ANIMATED_MAX_BYTES = 1_000_000;

    /** Longest edge per kind of picture, in pixels (a little over what a 2x phone screen shows). */
    public const EDGE = ['avatars' => 640, 'bio-thumbnails' => 240, 'bio-icons' => 160, 'bio-images' => 1600];

    /** @return string path on the public disk, e.g. avatars/abc.webp */
    public static function store(UploadedFile $file, string $directory, ?int $maxEdge = null, string $field = 'image', string $format = 'webp'): string
    {
        $maxEdge ??= self::EDGE[$directory] ?? 1600;
        $bytes = (string) file_get_contents($file->getRealPath());
        $info = @getimagesizefromstring($bytes);
        if (! $info || ! function_exists('imagecreatefromstring')) {
            return $file->store($directory, 'public');
        }
        [$w, $h, $type] = $info;
        if ($w * $h > self::MAX_PIXELS || ! self::fitsInMemory($w, $h)) {
            throw ValidationException::withMessages([$field => 'This picture is too large to process. Resize it a little (under about 25 megapixels) and try again.']);
        }
        if ($type === IMAGETYPE_GIF && self::isAnimatedGif($bytes)) {
            if (strlen($bytes) > self::ANIMATED_MAX_BYTES) {
                throw ValidationException::withMessages([$field => 'This animated picture is too heavy (over 1 MB) for a page that has to load on a phone. Use a shorter animation, or a still picture.']);
            }

            return $file->store($directory, 'public');
        }
        $src = @imagecreatefromstring($bytes);
        if (! $src) {
            return $file->store($directory, 'public');
        }
        if ($type === IMAGETYPE_JPEG) {
            $src = self::orient($src, $bytes);
        }
        $sw = imagesx($src);
        $sh = imagesy($src);
        $scale = min(1, $maxEdge / max($sw, $sh));
        $tw = max(1, (int) round($sw * $scale));
        $th = max(1, (int) round($sh * $scale));
        $dst = imagecreatetruecolor($tw, $th);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $tw, $th, $sw, $sh);

        $png = $format === 'png' || ! function_exists('imagewebp');
        ob_start();
        $png ? imagepng($dst, null, 7) : imagewebp($dst, null, $directory === 'bio-images' ? 80 : 82);
        $out = (string) ob_get_clean();
        // already small and nothing gained (a tiny PNG icon): keep what was sent
        if ($out === '' || (strlen($out) >= strlen($bytes) && $scale >= 1)) {
            return $file->store($directory, 'public');
        }
        $path = $directory.'/'.Str::random(40).($png ? '.png' : '.webp');
        Storage::disk('public')->put($path, $out);

        return $path;
    }

    public static function isAnimatedGif(string $bytes): bool
    {
        return preg_match_all('#\x00\x21\xF9\x04.{4}\x00[\x2C\x21]#s', $bytes) > 1;
    }

    private static function orient(\GdImage $img, string $bytes): \GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $img;
        }
        $exif = @exif_read_data('data://image/jpeg;base64,'.base64_encode($bytes));
        $angle = match ((int) ($exif['Orientation'] ?? 1)) {
            3 => 180, 6 => -90, 8 => 90, default => 0,
        };

        return $angle ? (imagerotate($img, $angle, 0) ?: $img) : $img;
    }
}
