<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRiskAnalysisTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('risk_analysis', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->boolean('is_public')->default(false);
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->unsignedBigInteger('job_title_id')->nullable();
            $table->unsignedBigInteger('report_id')->nullable();
            $table->unsignedBigInteger('deviation_id')->nullable();
            $table->string('name');
            $table->unsignedBigInteger('responsible')->nullable();
            $table->integer('status')->default(1)->comment('1: New, 2: Ongoing/Processing, 3: Closed, 4: Cancel, 5: Disabled');
            $table->unsignedBigInteger('added_by');
            $table->tinyInteger('need_to_process')->default(0);
            $table->boolean('is_suggestion')->default(false)->nullable();
            $table->string('url')->default('/reports/reportedRiskanalysis')->nullable();
            $table->timestamps();
            
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('job_title_id')->references('id')->on('job_titles')->onDelete('cascade');
            $table->foreign('report_id')->references('id')->on('reports')->onDelete('cascade');
            $table->foreign('deviation_id')->references('id')->on('deviations')->onDelete('cascade');
            $table->foreign('responsible')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('added_by')->references('id')->on('users');
            $table->unsignedBigInteger('object_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('risk_analysis');
    }
}
