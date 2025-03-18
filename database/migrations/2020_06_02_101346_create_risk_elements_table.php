<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRiskElementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('risk_elements', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('risk_analysis_id');
            $table->text('name');
            $table->string('type')->nullable();
            $table->bigInteger('type_id')->nullable();
            $table->enum('probability', ['1', '2', '3', '4'])->default('1')->comment('1:Low, 2:Moderate, 3:High, 4:Very high');
            $table->enum('consequence', ['1', '2', '3', '4'])->default('1')->comment('1:Low, 2:Moderate, 3:High, 4:Very high');
            $table->longText('description_resolve')->nullable();
            $table->timestamps();

            $table->foreign('risk_analysis_id')->references('id')->on('risk_analysis')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('risk_elements');
    }
}
