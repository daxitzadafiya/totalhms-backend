<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateObjectsOptionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('objects_option', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('object_id')->nullable();
            $table->boolean('show_in_risk_analysis')->default(false);
            $table->integer('number_used_time')->nullable();
            $table->json('risk_analysis_array')->nullable();
            $table->bigInteger('image_id')->nullable();
            $table->timestamps();

            $table->foreign('object_id')->references('id')->on('objects')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('objects_option');
    }
}
