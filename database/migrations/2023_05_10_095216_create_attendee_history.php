<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAttendeeHistory extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attendee_history', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('object_id')->nullable();
            $table->text('type')->nullable();
            $table->text('reason')->nullable();
            $table->text('old_attendee_department')->nullable();
            $table->text('old_attendee_employee')->nullable();
            $table->text('new_attendee_department')->nullable();
            $table->text('new_attendee_employee')->nullable();
            $table->text('transfer_information')->nullable();
            $table->string('transfer_feedback')->nullable();
            $table->string('transfer_attachment')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attendee_history');
    }
}
