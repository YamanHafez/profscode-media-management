<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('profscode_media', function (Blueprint $table) {
            $table->id();
            $table->uuid('model_id');
            $table->string('model_type');
            $table->string('collection')->default('default');
            $table->string('original_name');
            $table->string('name');
            $table->string('mime_type');
            $table->string('disk');
            $table->string('size');
            $table->json('conversions');
            $table->index(["model_id", "model_type"]);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profscode_media');
    }
};
