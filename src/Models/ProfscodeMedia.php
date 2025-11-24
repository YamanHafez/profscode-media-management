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
        $modelType = $this->model_type;
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

}
