<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateResponsibleHistory extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('responsible_history', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('object_id')->nullable();
            $table->text('type')->nullable();
            $table->text('reason')->nullable();
            $table->text('old_responsible_department')->nullable();
            $table->text('old_responsible_employee')->nullable();
            $table->text('new_responsible_department')->nullable();
            $table->text('new_responsible_employee')->nullable();  
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
        Schema::dropIfExists('responsible_history');
    }
}
