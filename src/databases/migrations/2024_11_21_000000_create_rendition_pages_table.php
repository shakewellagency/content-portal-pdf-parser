<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRenditionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rendition_pages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('rendition_id'); 
            $table->foreign('rendition_id')
                ->references('id')  
                ->on('renditions') 
                ->onDelete('cascade'); 
            $table->text('slug')->nullable();
            $table->integer('page_no')->nullable();
            $table->json('content')->nullable();
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
        Schema::dropIfExists('rendition_pages');
    }
}
