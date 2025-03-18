<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRoutinesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('routines', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('industry_id');
            $table->boolean('is_public')->default(false);
            $table->longText('description')-> nullable();
            $table->unsignedBigInteger('job_title_id')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->boolean('is_activated')->default(0)->nullable()->comment("none / activate");
            $table->bigInteger('start_time')->nullable();
            $table->bigInteger('deadline')->nullable();
            $table->string('recurring')->default('indefinite')->comment('yearly, quarter, monthly, weekly, indefinite')->nullable();
            $table->boolean('is_attending_activated')->default(0)->nullable()->comment("none / activate");
            $table->json('attending_emps')->nullable();
            $table->json('attending_contact')->nullable();
            $table->integer('attendings_count')->nullable();
            $table->unsignedBigInteger('responsible_id')->nullable();
            $table->string('status');
            $table->unsignedBigInteger('added_by')->default(1);
            $table->integer('delete_status')->default(0)->nullable();
            $table->boolean('is_template')->default(false);
            $table->bigInteger('parent_id')->nullable();
            $table->bigInteger('duration')->nullable();
            $table->bigInteger('is_duartion')->nullable();
            $table->boolean('is_suggestion')->default(false)->nullable();
            $table->string('url')->default('/company/routines')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('job_title_id')->references('id')->on('job_titles')->onDelete('cascade');
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->foreign('added_by')->references('id')->on('users');
            $table->foreign('responsible_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('routines');
    }
}
