<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Shakewellagency\ContentPortalPdfParser\Enums\RenditionTypeEnum;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('renditions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('version_id')->nullable(); 
            $table->foreign('version_id')
                ->references('id')  
                ->on('versions') 
                ->onDelete('set null'); 
            $table->unsignedBigInteger('package_id'); 
            $table->foreign('package_id')
                ->references('id')  
                ->on('packages') 
                ->onDelete('cascade'); 
            $table->enum('type', RenditionTypeEnum::values())
                ->default(RenditionTypeEnum::PDF->value);    
            $table->longText('summary')->nullable();;
            $table->json('outline')->nullable();
            $table->text('cover_photo_path')->nullable();
            $table->boolean('is_parsed')->default(0);
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
        Schema::dropIfExists('renditions');
    }
};
