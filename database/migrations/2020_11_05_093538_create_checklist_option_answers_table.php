<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateChecklistOptionAnswersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('checklist_option_answers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('default_option_id');
            $table->string('name');
            $table->integer('arrangement_order');
            $table->timestamps();

            $table->foreign('default_option_id')->references('id')->on('checklist_options')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('checklist_option_answers');
    }
}
