<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmployeesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->integer('disable_status')->default(0)->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('job_title_id')->nullable();
            $table->string('account_number')->nullable();
            $table->unsignedBigInteger('nearest_manager')->nullable()->charset(null)->collation(null);
            $table->float('hourly_salary')->nullable();
            $table->float('overtime_pay')->nullable();
            $table->integer('night_allowance')->nullable();
            $table->integer('holidays')->default(0)->comment('0,4,5,6 weeks')->nullable();
            $table->float('tax')->nullable();
            $table->integer('weekend_addition')->nullable();
            $table->integer('evening_allowance')->nullable();
            $table->json('absence_info')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
            $table->foreign('job_title_id')->references('id')->on('job_titles')->onDelete('cascade');
            $table->foreign('nearest_manager')->references('id')->on('users')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employees');
    }
}
