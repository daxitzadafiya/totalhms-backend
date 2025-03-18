<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDocumentsNewTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('documents_new', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('industry_id')->nullable();
            $table->string('name');
            $table->longText('description')->nullable();
            $table->integer('status')->default(0)->nullable()->comment('0: draft, 1: public');
            $table->unsignedBigInteger('added_by');
            $table->integer('delete_status')->default(0)->nullable();
            $table->boolean('is_template')->default(false);
            $table->bigInteger('parent_id')->nullable();
            $table->integer('type_of_attachment')->nullable()->comment('1: Attachment, 2: Only note');
            $table->string('type')->nullable()->comment("document, attachment, report");
            $table->string('object_type')->nullable()->comment("absence, checklist, contact, deviation, employee, help center, risk element source,...");
            $table->bigInteger('object_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->boolean('is_suggestion')->default(false)->nullable();
            $table->string('url')->default('/documents/documents')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('added_by')->references('id')->on('users');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('documents_new');
    }
}
