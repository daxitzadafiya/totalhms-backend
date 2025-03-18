<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRiskElementSourcesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('risk_element_sources', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('industry_id');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->boolean('is_public')->default(false);
            $table->unsignedBigInteger('job_title_id')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type')->nullable();
            $table->bigInteger('type_id')->nullable();
            $table->unsignedBigInteger('added_by');
            $table->integer('delete_status')->default(0)->nullable();
            $table->boolean('is_template')->default(false);
            $table->json('update_history')->nullable();
            $table->boolean('is_suggestion')->default(false)->nullable();
            $table->string('url')->default('/company/riskAreas')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->foreign('job_title_id')->references('id')->on('job_titles')->onDelete('cascade');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
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
        Schema::dropIfExists('risk_element_sources');
    }
}
