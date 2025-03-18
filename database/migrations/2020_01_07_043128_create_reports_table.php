<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->json('answer');
            $table->longText('description')-> nullable();
            $table->unsignedBigInteger('company_id');
            $table->boolean('is_public')->default(false);
            $table->unsignedBigInteger('job_title_id')->nullable();
            $table->unsignedBigInteger('project_id')-> nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('added_by')->default(1);
            $table->unsignedBigInteger('category_id')->nullable();
            $table->json('checklist_info');
            $table->unsignedBigInteger('responsible')->nullable();
            $table->integer('status')->default(1)->comment('1: New, 2: Ongoing/Processing, 3: Closed');
            $table->string('action_done')->comment("['risk', 'task']")->nullable();
            $table->boolean('is_suggestion')->default(false)->nullable();
            $table->string('url')->default('/reports/reportedChecklists')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('job_title_id')->references('id')->on('job_titles')->onDelete('cascade');
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
            $table->foreign('added_by')->references('id')->on('users');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->foreign('responsible')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reports');
    }
}
