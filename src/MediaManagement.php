<?php

namespace Profscode\MediaManagement;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Profscode\MediaManagement\Models\ProfscodeMedia;

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
        $disk = $this->mediaDisk ?? config('filesystems.default');
        $modelType = class_basename($this);
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
    public function addMedia(
        $fileSource,
        string $collection = "default",
        array $conversions = ["admin_panel" => ["width" => 100, "height" => 100, "webp" => true]]
    ) {
        if ($fileSource instanceof \Illuminate\Http\UploadedFile) {
            $file = $fileSource;

        } elseif (is_string($fileSource) && file_exists($fileSource)) {

            $originalName = basename($fileSource);
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

            $file = new \Illuminate\Http\UploadedFile(
                $fileSource,
                $originalName,
                mime_content_type($fileSource),
                null,
                true
            );

        } else {
            return null;
        }

        $originalName = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension());
        $mimeType = $file->getClientMimeType();
        $disk = $this->mediaDisk ?? config('filesystems.default');
        $modelType = class_basename($this);
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

    public function addMediaFromUrl(
        string $file_url,
        string $collection = "default",
        array $conversions = ["admin_panel" => ["width" => 100, "height" => 100, "webp" => true]]
    ) {
        $contents = file_get_contents($file_url);

        if (!$contents) {
            return null;
        }

        $tmpPath = tempnam(sys_get_temp_dir(), 'media_');
        file_put_contents($tmpPath, $contents);

        $originalName = basename(parse_url($file_url, PHP_URL_PATH));
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        $file = new UploadedFile(
            $tmpPath,
            $originalName,
            mime_content_type($tmpPath),
            null,
            true
        );


        $mimeType = $file->getClientMimeType();
        $disk = $this->mediaDisk ?? 'public';
        $modelType = class_basename($this);
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
    public function media()
    {
        return $this->morphMany(ProfscodeMedia::class, 'model');
    }
    public function getFirstMediaUrl($collection = "default", $thumb = null)
    {
        $media = $this->media()->where("collection", $collection)->first();
        if (!$media) {
            return null;
        }

        $disk = $media->disk;

        $conversions = json_decode($media->conversions, true);
        if (!$thumb) {
            return Storage::disk($disk)->url(
                "media/" . class_basename($this) . "/" . $this->id . "/" . $collection . "/" . $media->name
            );
        }

        if (isset($conversions['items'][$thumb])) {

            if (isset($conversions['items'][$thumb]['webp'])) {
                return Storage::disk($disk)->url(
                    $conversions['items'][$thumb]['webp']
                );
            }

            if (isset($conversions['items'][$thumb]['original'])) {
                return Storage::disk($disk)->url(
                    $conversions['items'][$thumb]['original']
                );
            }
        }
        return Storage::disk($disk)->url(
            "media/" . class_basename($this) . "/" . $this->id . "/" . $collection . "/" . $media->name
        );
    }
    public function getFirstMedia($collection = "default")
    {
        $media = $this->media()->where("collection", $collection)->first();
        if (!$media) {
            return null;
        }
        return $media;
    }
    public function getUrl()
    {
    }
    public function getMedia($collection = "default")
    {
        $media = $this->media()->where("collection", $collection)->get();
        return $media;
    }
    public function getMediaUrls($collection = "default", $thumb = null)
    {
        return $this->media()
            ->where("collection", $collection)
            ->get()
            ->map(function ($item) use ($thumb) {
                return $item->getUrl($thumb);
            })->toArray();
    }
}
