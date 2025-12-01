<?php

namespace Profscode\MediaManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ProfscodeMedia extends Model
{
    protected $table = 'profscode_media';

    protected $fillable = [
        'model_id',
        'model_type',
        'collection',
        'original_name',
        'name',
        'mime_type',
        'disk',
        'size',
        'conversions',
    ];

    public function media()
    {
        return $this->morphTo();
    }
    public function getUrl($thumb = null)
    {
        $disk = $this->disk;
        $modelType = class_basename($this->model_type);
        $modelId = $this->model_id;
        $collection = $this->collection;

        $conversions = json_decode($this->conversions, true);
        if (!$thumb) {
            return Storage::disk($disk)->url(
                "media/{$modelType}/{$modelId}/{$collection}/{$this->name}"
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
            "media/{$modelType}/{$modelId}/{$collection}/{$this->name}"
        );
    }
    protected static function booted()
    {
        static::deleting(function ($media) {

            $disk = $media->disk;
            $modelType = class_basename($media->model_type);
            $modelId = $media->model_id;
            $collection = $media->collection;

            $originalPath = "media/{$modelType}/{$modelId}/{$collection}/{$media->name}";
            Storage::disk($disk)->delete($originalPath);

            $conversions = json_decode($media->conversions, true);

            if (!empty($conversions['items'])) {
                foreach ($conversions['items'] as $item) {

                    if (isset($item['original'])) {
                        Storage::disk($disk)->delete($item['original']);
                    }

                    if (isset($item['webp'])) {
                        Storage::disk($disk)->delete($item['webp']);
                    }
                }
            }

            if (!empty($conversions['original_webp'])) {
                Storage::disk($disk)->delete($conversions['original_webp']);
            }

            $paths = [
                "media/{$modelType}/{$modelId}/{$collection}/conversions",
                "media/{$modelType}/{$modelId}/{$collection}",
                "media/{$modelType}/{$modelId}",
                "media/{$modelType}",
            ];

            foreach ($paths as $path) {
                if (
                    empty(Storage::disk($disk)->files($path)) &&
                    empty(Storage::disk($disk)->directories($path))
                ) {
                    Storage::disk($disk)->deleteDirectory($path);
                }
            }
        });
    }


}
