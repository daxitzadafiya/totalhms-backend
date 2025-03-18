<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateResponsibleProcessing extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('responsible_processing', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('added_by')->nullable();
            $table->text('comment')->nullable();
            $table->bigInteger('attachment_id')->nullable();
            $table->string('status', 20)->comment('new, ongoing, done, pending, verify, reopened, closed, cancelled')->nullable();
            $table->unsignedBigInteger('responsible_id')->nullable();
            $table->text('responsible_comment')->nullable();
            $table->bigInteger('responsible_attachment_id')->nullable(); 
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('added_by')->references('id')->on('users');
            $table->unsignedBigInteger('attendee_id')->nullable();
            $table->foreign('attendee_id')->references('id')->on('responsible')->onDelete('cascade');
            $table->foreign('responsible_id')->references('id')->on('users'); 
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
        Schema::dropIfExists('responsible_processing');
    }
}
