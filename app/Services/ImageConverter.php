<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

class ImageConverter
{
    /**
     * If the stored file is a HEIC/HEIF image, convert it to JPEG and return the new path.
     */
    public static function convertIfHeic(string $path, string $disk = 'public'): string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (! in_array($extension, ['heic', 'heif'])) {
            return $path;
        }

        $fullPath = Storage::disk($disk)->path($path);
        $newPath = preg_replace('/\.(heic|heif)$/i', '.jpg', $path);
        $newFullPath = Storage::disk($disk)->path($newPath);

        if (! file_exists($fullPath)) {
            return $path;
        }

        $nodeScript = sprintf(
            'import convert from "heic-convert"; import fs from "fs"; const buf = fs.readFileSync(%s); const out = await convert({ buffer: buf, format: "JPEG", quality: 0.9 }); fs.writeFileSync(%s, out);',
            json_encode($fullPath),
            json_encode($newFullPath)
        );

        $result = Process::path(base_path())->run([
            'node',
            '--input-type=module',
            '-e',
            $nodeScript,
        ]);

        if ($result->successful() && file_exists($newFullPath)) {
            @unlink($fullPath);

            return $newPath;
        }

        return $path;
    }
}
