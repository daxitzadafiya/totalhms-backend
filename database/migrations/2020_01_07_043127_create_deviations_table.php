<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDeviationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('deviations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('added_by');
            $table->integer('consequence_for')->comment('1: Company; 2: Customer; 3: Andre');
            $table->string('place')->nullable();
            $table->string('subject');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->boolean('is_public')->default(false);
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->unsignedBigInteger('job_title_id')->nullable();
            $table->string('specifications')->nullable();
            $table->integer('happened_before')->comment('1: Yes; 2: No; 3: Uncertain')->nullable();
            $table->string('corrective_action')->nullable();
            $table->string('prososial_action')->nullable();
            $table->longText('description')->nullable();
            $table->string('attachment')-> nullable();
            $table->integer('status')->comment('1: New; 2: Ongoing; 3: Closed')->default(1);
            $table->boolean('report_as_anonymous')->default(false);
            $table->string('action')->nullable();
            $table->unsignedBigInteger('responsible')->nullable();
            $table->boolean('is_suggestion')->default(false)->nullable();
            $table->string('url')->default('/company/deviations')->nullable();
            $table->timestamps();

            $table->foreign('added_by')->references('id')->on('users');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('job_title_id')->references('id')->on('job_titles')->onDelete('cascade');
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
        Schema::dropIfExists('deviations');
    }
}
