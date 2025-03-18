<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDocumentsAttachmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('documents_attachments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('document_id');
            $table->string('original_file_name')->nullable();
            $table->float('file_size')->default(0)->nullable();
            $table->string('file_extension')->nullable();
            $table->string('uri')->nullable();
            $table->timestamps();

            $table->foreign('document_id')->references('id')->on('documents_new')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('documents_attachments');
    }
}
