<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAbsenceReasonsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('absence_reasons', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('type');
            $table->text('description')->nullable();
            $table->string('is_paid')->nullable();
            $table->unsignedBigInteger('added_by');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->json('processor')->nullable();
            $table->bigInteger('related_id')->nullable()->comment('role id: created by super admin');
            $table->boolean('illegal')->default(false);
            $table->boolean('sick_child')->default(false);
            $table->string('class_of_absence')->default('day')->comment('interval, day');
            $table->integer('interval_absence')->nullable()->comment('null = unlimited');
            $table->float('days_off')->nullable();
            $table->float('days_off_exception')->nullable();
            $table->integer('extra_alone_custody')->nullable();
            $table->integer('sick_child_max_age')->nullable();
            $table->integer('sick_child_max_age_handicapped')->nullable();
            $table->integer('deadline_registration_number')->nullable();
            $table->string('deadline_registration_unit')->nullable();
            $table->integer('reset_time_number')->nullable();
            $table->string('reset_time_unit')->nullable();
            $table->integer('apply_time_number')->nullable();
            $table->string('apply_time_unit')->nullable();
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
        Schema::dropIfExists('absence_reasons');
    }
}
