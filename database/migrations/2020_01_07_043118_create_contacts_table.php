<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContactsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->boolean('is_template')->default(false);
            $table->unsignedBigInteger('project_id')->nullable();
            $table->string('phone_number');
            $table->string('email');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('address');
            $table->string('city');
            $table->integer('zip_code')->nullable();
            $table->string('organization_number')->nullable();
            $table->unsignedBigInteger('added_by');
            $table->boolean('is_suggestion')->default(false)->nullable();
            $table->string('url')->default('/company/contacts')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->foreign('added_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('contacts');
    }
}
