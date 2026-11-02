<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Process;
use Livewire\Drawer\Utils;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;

class CustomFilePreviewController implements HasMiddleware
{
    public static array $middleware = ['web'];

    public static function middleware()
    {
        return array_map(fn ($middleware) => new Middleware($middleware), static::$middleware);
    }

    public function handle(string $filename)
    {
        abort_unless(request()->hasValidRelativeSignature(), 401);

        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($extension, ['heic', 'heif'])) {
            $storage = FileUploadConfiguration::storage();
            $filePath = FileUploadConfiguration::path($filename);

            if ($storage->exists($filePath)) {
                $fullPath = $storage->path($filePath);
                $jpgPath = preg_replace('/\.(heic|heif)$/i', '.jpg', $fullPath);

                if (! file_exists($jpgPath)) {
                    $nodeScript = sprintf(
                        'import convert from "heic-convert"; import fs from "fs"; const buf = fs.readFileSync(%s); const out = await convert({ buffer: buf, format: "JPEG", quality: 0.85 }); fs.writeFileSync(%s, out);',
                        json_encode($fullPath),
                        json_encode($jpgPath)
                    );

                    Process::path(base_path())->run([
                        'node',
                        '--input-type=module',
                        '-e',
                        $nodeScript,
                    ]);
                }

                if (file_exists($jpgPath)) {
                    return response()->file($jpgPath, [
                        'Content-Type' => 'image/jpeg',
                        'Cache-Control' => 'public, max-age=31536000',
                    ]);
                }
            }
        }

        return Utils::pretendPreviewResponseIsPreviewFile($filename);
    }
}
