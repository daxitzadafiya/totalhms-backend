<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBillingDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('billing_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('billing_id');
            $table->unsignedBigInteger('plan_id')->nullable();
            $table->unsignedBigInteger('addon_id')->nullable();
            $table->integer('additional_user')->default(0);
            $table->double('additional_user_amount')->default(0);
            $table->double('amount')->default(0);
            $table->tinyInteger('status')->default(0);
            $table->timestamps();

            $table->foreign('billing_id')->references('id')->on('billings')->onDelete('cascade');
            $table->foreign('plan_id')->references('id')->on('plans')->onDelete('cascade');
            $table->foreign('addon_id')->references('id')->on('addons')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('billing_details');
    }
}
