<?php

namespace Profscode\Translatable;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Profscode\Translatable\Models\ProfscodeTranslate;

trait MediaManagement
{
    public function addMediaFromRequest(
        string $file_name,
        string $collection = "default",
        array $conversions = ["admin_panel" => ["width" => 100, "height" => 100, "webp" => true]]
    ) {
        if (!request()->hasFile($file_name)) {
            return null;
        }

        $file = request()->file($file_name);

        $originalName = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension());
        $mimeType = $file->getClientMimeType();
        $disk = $this->mediaDisk ?? 'public';
        $modelType = get_class($this);
        $modelId = $this->id;

        $webpSupported = ['jpg', 'jpeg', 'png'];

        $storedName = uniqid() . "." . $extension;

        $file->storeAs("media/{$modelType}/{$modelId}/{$collection}", $storedName, $disk);

        $originalWebpPath = null;
        if (in_array($extension, $webpSupported)) {
            $sourceOriginal = $this->createImageFromFile($file->getRealPath(), $extension);
            if ($sourceOriginal) {
                $originalWebpName = uniqid() . ".webp";
                $originalWebpPath = "media/{$modelType}/{$modelId}/{$collection}/" . $originalWebpName;
                $this->saveWebpToDisk($sourceOriginal, $originalWebpPath, $disk);
                imagedestroy($sourceOriginal);
            }
        }

        $storedConversions = [];

        foreach ($conversions as $key => $config) {

            $width = $config['width'] ?? null;
            $height = $config['height'] ?? null;
            $convertToWebp = $config['webp'] ?? false;

            $conversionBaseName = $key . "_" . $storedName;
            $conversionPath = "media/{$modelType}/{$modelId}/{$collection}/conversions/" . $conversionBaseName;

            $source = $this->createImageFromFile($file->getRealPath(), $extension);
            if (!$source) {
                continue;
            }

            $resized = $this->resizeImage($source, $width, $height);

            $this->saveImageToDisk($resized, $extension, $conversionPath, $disk);

            if ($convertToWebp && in_array($extension, $webpSupported)) {
                $webpName = $key . "_" . pathinfo($storedName, PATHINFO_FILENAME) . ".webp";
                $webpPath = "media/{$modelType}/{$modelId}/{$collection}/conversions/" . $webpName;

                $this->saveWebpToDisk($resized, $webpPath, $disk);

                $storedConversions[$key]['webp'] = $webpPath;
            }

            $storedConversions[$key]['original'] = $conversionPath;

            imagedestroy($source);
            imagedestroy($resized);
        }

        return $this->media()->create([
            'model_id' => $modelId,
            'model_type' => $modelType,
            'collection' => $collection,
            'original_name' => $originalName,
            'name' => $storedName,
            'mime_type' => $mimeType,
            'disk' => $disk,
            'size' => $file->getSize(),
            'conversions' => json_encode([
                'original_webp' => $originalWebpPath,
                'items' => $storedConversions
            ]),
        ]);
    }
    private function saveWebpToDisk($image, $path, $disk)
    {
        ob_start();
        imagewebp($image, null, 80);
        $data = ob_get_clean();
        Storage::disk($disk)->put($path, $data);
    }


    private function createImageFromFile($path, $extension)
    {
        return match ($extension) {
            'jpg', 'jpeg' => imagecreatefromjpeg($path),
            'png' => imagecreatefrompng($path),
            'gif' => imagecreatefromgif($path),
            default => null,
        };
    }

    private function resizeImage($source, $targetWidth, $targetHeight)
    {
        $width = imagesx($source);
        $height = imagesy($source);

        if (!$targetWidth && !$targetHeight) {
            return $source;
        }

        if ($targetWidth && !$targetHeight) {
            $ratio = $targetWidth / $width;
            $targetHeight = intval($height * $ratio);
        } elseif (!$targetWidth && $targetHeight) {
            $ratio = $targetHeight / $height;
            $targetWidth = intval($width * $ratio);
        }

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        return $canvas;
    }

    private function saveImageToDisk($image, $extension, $path, $disk)
    {
        ob_start();
        match ($extension) {
            'jpg', 'jpeg' => imagejpeg($image, null, 90),
            'png' => imagepng($image),
            'gif' => imagegif($image),
        };
        $data = ob_get_clean();
        Storage::disk($disk)->put($path, $data);
    }

}
