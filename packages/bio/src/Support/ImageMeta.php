<?php

namespace Hop\Bio\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Size and average colour of a picture on the public disk, for width/height attributes (no layout shift) and a colour
 * to show while the picture loads. Computed once from the stored file and kept in the cache.
 */
class ImageMeta
{
    /** @return array{w:int, h:int, color:string}|null  null for outside addresses, SVG or anything unreadable */
    public static function for(?string $path): ?array
    {
        $path = preg_replace('#^/?storage/#', '', trim((string) $path));
        if ($path === '' || ! preg_match('#^(avatars|bio-images|bio-thumbnails|bio-icons)/[A-Za-z0-9._-]+\.(webp|png|jpe?g|gif)$#i', $path)) {
            return null;
        }

        return Cache::rememberForever('img-meta:'.$path, function () use ($path) {
            $disk = Storage::disk('public');
            if (! $disk->exists($path) || ! ($img = @imagecreatefromstring((string) $disk->get($path)))) {
                return null;
            }
            $w = imagesx($img);
            $h = imagesy($img);
            $px = imagecreatetruecolor(1, 1);
            imagecopyresampled($px, $img, 0, 0, 0, 0, 1, 1, $w, $h);
            $c = imagecolorat($px, 0, 0);

            return ['w' => $w, 'h' => $h, 'color' => sprintf('#%02x%02x%02x', ($c >> 16) & 255, ($c >> 8) & 255, $c & 255)];
        });
    }

    /** width, height and a placeholder colour as HTML attributes (empty for pictures it cannot read). */
    public static function attrs(?string $path): string
    {
        $m = self::for($path);

        return $m ? sprintf('width="%d" height="%d" style="background-color:%s"', $m['w'], $m['h'], $m['color']) : '';
    }
}
