<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Shakewellagency\ContentPortalPdfParser\Enums\PackageStatusEnum;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('file_type')->nullable();
            $table->text('file_name')->nullable();
            $table->text('hash')->nullable();
            $table->enum('status', [
                PackageStatusEnum::Queued->value,
                PackageStatusEnum::Processing->value,
                PackageStatusEnum::Finished->value,
                PackageStatusEnum::Failed->value,
            ])->default(PackageStatusEnum::Queued->value);
            $table->string('location')->nullable()->default('s3');
            $table->text('file_path')->nullable();
            $table->text('request_ip')->nullable();
            $table->text('parser_version')->nullable();
            $table->unsignedBigInteger('initiated_by')->nullable(); 
            $table->foreign('initiated_by')
                ->references('id')  
                ->on('users') 
                ->onDelete('set null'); 
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->longtext('failed_exception')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
