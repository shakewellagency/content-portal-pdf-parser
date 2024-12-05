<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Shakewellagency\ContentPortalPdfParser\Enums\PublicationStatusEnum;
use Shakewellagency\ContentPortalPdfParser\Enums\PublicationTypeEnum;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('publications', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('publication_no')->nullable();
            $table->text('title');
            $table->text('slug');
            $table->longText('description')->nullable();
            $table->string('doc_type')->nullable();
            $table->enum('type', PublicationTypeEnum::values())
                ->default(PublicationTypeEnum::UploadFile->value);
            $table->enum('status', PublicationStatusEnum::values())
                ->default(PublicationStatusEnum::Draft->value);
            $table->text('link')->nullable();
            $table->boolean('new_badge')->default(0);
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
        Schema::dropIfExists('publications');
    }
};