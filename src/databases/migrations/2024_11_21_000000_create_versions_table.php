<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVersionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('versions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('publication_id'); 
            $table->foreign('publication_id')
                ->references('id')  
                ->on('publications') 
                ->onDelete('cascade'); 
            $table->text('title');
            $table->text('slug');
            $table->longText('description')->nullable();
            $table->string('type')->nullable();
            $table->json('system_meta')->nullable();
            $table->json('version_meta')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->boolean('approved')->default(0);
            $table->boolean('archived')->default(0);
            $table->boolean('is_current')->default(0);
            $table->boolean('new_badge')->default(0);
            $table->longtext('preview_token')->nullable();
            $table->longtext('approved_token')->nullable();
            $table->unsignedBigInteger('approved_by'); 
            $table->foreign('approved_by')
                ->references('id')  
                ->on('users') 
                ->onDelete('set null'); 
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
        Schema::dropIfExists('versions');
    }
}
