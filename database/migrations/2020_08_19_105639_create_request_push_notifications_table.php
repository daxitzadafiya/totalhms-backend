<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRequestPushNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('request_push_notifications', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('message_id')->default(1);
            $table->string('type')->default('notification')->comment('alert, notification');
            $table->string('feature')->nullable()->comment('name of feature/function');
            $table->bigInteger('feature_id')->nullable()->comment('name of feature/function');
            $table->unsignedBigInteger('processed_by')->nullable()->comment('pending, applied, declined');
            $table->string('process_status')->nullable()->comment('pending, applied, denied');
            $table->string('send_to_option')->default('user')->comment('user, company, industry');
            $table->unsignedBigInteger('send_from');
            $table->json('send_to')->nullable()->comment('Contains a list of users/companies/industries to receive notifications. Null = send to all filter by option');
            $table->string('url')->nullable();
            $table->string('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->integer('status')->default(1)->comment('1: create, 2: pending, 3: sent');
            $table->dateTime('sending_time')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('message_id')->references('id')->on('messages')->onDelete('cascade');
            $table->foreign('processed_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('send_from')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('request_push_notifications');
    }
}
