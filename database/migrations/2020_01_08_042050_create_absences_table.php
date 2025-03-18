<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAbsencesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('absences', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('project_id')->nullable();
            $table->unsignedBigInteger('added_by');
            $table->unsignedBigInteger('absence_reason_id');
            $table->boolean('illegal')->default(false);
            $table->text('description')->nullable();
            $table->json('processor')->nullable();
            $table->unsignedBigInteger('processed_by')->nullable();
            $table->unsignedBigInteger('absence_reason_id_added_by_admin')->nullable();
            $table->float('duration_time');
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->integer('status')->default(1)->comment('1: New; 2: Processing; 3: Approved; 4: Reject; 5: reject && change reason; 6: added automatically');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->boolean('is_paid')->default(false);
            $table->text('reject_reason')->nullable();
            $table->timestamps();

            $table->foreign('processed_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('absence_reason_id')->references('id')->on('absence_reasons')->onDelete('cascade');
            $table->foreign('added_by')->references('id')->on('users');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('absence_reason_id_added_by_admin')->references('id')->on('absence_reasons')->onDelete('cascade');
            $table->foreign('parent_id')->references('id')->on('absences')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('absences');
    }
}
