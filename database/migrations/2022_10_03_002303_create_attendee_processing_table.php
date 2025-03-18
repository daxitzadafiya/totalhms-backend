<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAttendeeProcessingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attendee_processing', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('added_by')->nullable();
            $table->unsignedBigInteger('attendee_id')->nullable();
            $table->text('comment')->nullable();
            $table->bigInteger('attachment_id')->nullable();
            $table->string('status', 20)->comment('new, in-progress, done, pending, verify, reopened, closed, cancelled')->nullable();
            $table->unsignedBigInteger('responsible_id')->nullable();
            $table->text('responsible_comment')->nullable();
            $table->bigInteger('responsible_attachment_id')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('added_by')->references('id')->on('users');
            $table->foreign('attendee_id')->references('id')->on('attendee')->onDelete('cascade');
            $table->foreign('responsible_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('task_processing');
    }
}
