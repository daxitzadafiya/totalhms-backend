<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTimeManagementTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('time_management', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('added_by')->nullable();
            $table->unsignedBigInteger('object_id')->nullable();
            $table->unsignedBigInteger('document_id')->nullable();
            $table->text('start_time')->nullable();
            $table->bigInteger('start_date')->nullable();
            $table->bigInteger('deadline')->nullable();
            $table->string('recurring', 20)->comment('yearly, quarter, monthly, weekly, daily')->nullable();

            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('added_by')->references('id')->on('users');
            $table->foreign('object_id')->references('id')->on('objects')->onDelete('cascade');
            $table->foreign('document_id')->references('id')->on('documents_new')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('time_management');
    }
}
