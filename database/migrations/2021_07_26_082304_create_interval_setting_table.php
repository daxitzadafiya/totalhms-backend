<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIntervalSettingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('interval_setting', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('type')->comment('repository,...');
            $table->unsignedBigInteger('added_by');
            $table->integer('year')->default(0)->nullable();
            $table->integer('month')->default(0)->nullable();
            $table->integer('day')->default(0)->nullable();
            $table->integer('hour')->default(0)->nullable();
            $table->integer('minute')->default(0)->nullable();
            $table->integer('status')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
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
        Schema::dropIfExists('interval_setting');
    }
}
