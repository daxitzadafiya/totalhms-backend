<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateJobTitlesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('job_titles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->integer('disable_status')->default(0)->nullable();
            $table->string('industry_id');
            $table->string('name');
            $table->bigInteger('role_id')->nullable();
            $table->string('role_name')->nullable();
            $table->bigInteger('parent_id')->nullable();
            $table->boolean('is_super')->default(false)->nullable();
            $table->json('department')->nullable();
            $table->json('permission')->nullable();
            $table->unsignedBigInteger('added_by')->nullable();
            $table->timestamps();

            $table->foreign('added_by')->references('id')->on('users');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('job_titles');
    }
}
