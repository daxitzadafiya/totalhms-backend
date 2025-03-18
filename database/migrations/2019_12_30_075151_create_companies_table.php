<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Carbon\Carbon;

class CreateCompaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->unique();
            $table->string('phone_number');
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('vat_number');
            $table->unsignedBigInteger('industry_id');
            $table->string('address');
            $table->string('city');
            $table->integer('zip_code')->nullable();
            $table->string('country')->nullable();
            $table->string('logo')->nullable();
            $table->string('language')->default('no')->nullable();
            $table->date('active_since')->default(Carbon::now()->format('Y-m-d'));
            $table->date('established_date')->nullable();
            $table->unsignedBigInteger('ceo')->nullable();
            $table->unsignedBigInteger('hse_manager')->nullable();
            $table->unsignedBigInteger('safety_manager')->nullable();
            $table->string('status')->comment('pending, active, inactive, banned')->default('active');
            $table->timestamps();

            $table->foreign('industry_id')->references('id')->on('industries');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('companies');
    }
}
