<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTitleCaptionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('title_caption', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title_key');
            $table->unsignedBigInteger('role_id')->nullable();
            $table->string('menu', 45);
            $table->string('sub_menu', 50);
            $table->string('tab', 50);
            $table->string('sub_tab', 50)->nullable();
            $table->text('note')->nullable();
            $table->longText('caption');
            $table->boolean('activate')->default(false);
            $table->timestamps();

            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('title_caption');
    }
}
