<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInstructionActivitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('instruction_activities', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('activity');
            $table->unsignedBigInteger('instruction_id');
            $table->unsignedBigInteger('assignee')->nullable();
            $table->json('assigned_employee')->nullable();
            $table->json('assigned_department')->nullable();
            $table->timestamps();

            $table->foreign('instruction_id')->references('id')->on('instructions')->onDelete('cascade');
            $table->foreign('assignee')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('instruction_activities');
    }
}
