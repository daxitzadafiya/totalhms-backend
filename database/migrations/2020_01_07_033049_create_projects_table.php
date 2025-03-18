<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProjectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->text('description')-> nullable();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('added_by')->nullable();
            $table->string('project_number')->nullable();
            $table->string('project_number_custom')->nullable();
            $table->string('thumbnail')->nullable();
            $table->text('reference')->nullable();
            $table->dateTime('start')->nullable();
            $table->dateTime('deadline')->nullable();
            $table->integer('status')->comment('1: Draft, 2: Active, 3: Complete')->default(1);
            $table->string('responsible')->nullable()->comment('responsible employees array');
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
        Schema::dropIfExists('projects');
    }
}
