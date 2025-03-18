<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHelpCenterQuestionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('help_center_questions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('topic_id')->nullable();
            $table->bigInteger('title_id')->nullable();
            $table->text('question');
            $table->text('answer')->nullable();
            $table->boolean('only_company_admin')->default(false)->nullable();
            $table->integer('disable_status')->default(0)->nullable();
            $table->timestamps();

            $table->foreign('topic_id')->references('id')->on('help_center')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('help_center_questions');
    }
}
