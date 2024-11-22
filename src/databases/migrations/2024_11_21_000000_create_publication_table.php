<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePublicationsTable extends Migration
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
            $table->string('type')->nullable();
            $table->timestamp('issue_at')->nullable();
            $table->text('link')->nullable();
            $table->boolean('new_badge')->default(0);
            $table->string('cover_image')->nullable();
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
}
