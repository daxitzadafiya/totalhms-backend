<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Carbon\Carbon;

class CreateTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('industry_id');
            $table->boolean('is_public')->default(false);
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->unsignedBigInteger('job_title_id')->nullable();
            $table->string('type')->nullable()->comment("for enhancement");
            $table->bigInteger('type_id')->nullable();
            $table->unsignedBigInteger('responsible_id')->nullable();
            $table->boolean('assigned_company')->nullable();
            $table->json('assigned_employee')->nullable();
            $table->json('assigned_department')->nullable();
            $table->bigInteger('type_main_id')->nullable();
            $table->integer('status')->default(1)->comment('1: New, 2: Ongoing/Processing, 3: Closed, 4: Cancel, 5: Disabled');
            $table->bigInteger('start_time')->nullable();
            $table->bigInteger('deadline')->nullable();
            $table->string('recurring')->default('indefinite')->comment('yearly, quarter, monthly, weekly, indefinite')->nullable();
            $table->dateTime('completed_time')->nullable();
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('added_by');
            $table->json('update_history')->nullable();
            $table->boolean('is_suggestion')->default(false)->nullable();
            $table->string('url')->default('/company/tasks')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('added_by')->references('id')->on('users');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('job_title_id')->references('id')->on('job_titles')->onDelete('cascade');
            $table->foreign('responsible_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('completed_by')->references('id')->on('users')->onDelete('cascade');
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
        Schema::dropIfExists('tasks');
    }
}
