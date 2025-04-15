<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Shakewellagency\ContentPortalPdfParser\Enums\RenditionAssetTypeEnum;

return new class extends Migration
{


    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rendition_assets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('rendition_id');
            $table->foreign('rendition_id')
                ->references('id')
                ->on('renditions')
                ->onDelete('cascade');
            $table->enum('type', [
                RenditionAssetTypeEnum::Image->value,
                RenditionAssetTypeEnum::Video->value,
                RenditionAssetTypeEnum::Audio->value,
            ])->default(RenditionAssetTypeEnum::Image->value);
            $table->text('file_name')->nullable();
            $table->text('file_path')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rendition_assets');
    }
};
