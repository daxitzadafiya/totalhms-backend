<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->integer('disable_status')->default(0)->nullable();
            $table->string('industry_id');
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('type')->nullable()->comment('goal, routine, instruction, document, risk element source, ...');
            $table->unsignedBigInteger('added_by')->default(1);
            $table->integer('added_from')->nullable()->comment('1: company, 2: contact, 3: employee');
            $table->boolean('is_primary')->default(0)->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
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
        Schema::dropIfExists('categories');
    }
}
