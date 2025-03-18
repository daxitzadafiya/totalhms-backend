<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateChecklistOptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('checklist_options', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->integer('type_of_option_answer')->comment('1: Slider, 2: Dropdown');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('checklist_id')->nullable();
            $table->integer('count_option_answers')->nullable();
            $table->boolean('is_template')->nullable();
            $table->integer('count_used_time')->nullable();
            $table->unsignedBigInteger('added_by');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('checklist_id')->references('id')->on('checklists')->onDelete('cascade');
            $table->foreign('added_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('checklist_options');
    }
}
