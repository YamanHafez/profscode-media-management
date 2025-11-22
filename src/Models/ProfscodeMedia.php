<?php

namespace Profscode\Translatable\Models;

use Illuminate\Database\Eloquent\Model;

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

    public function translatable()
    {
        return $this->morphTo();
    }
}
