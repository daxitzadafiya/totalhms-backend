<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateExtendedTimeline extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('extended_timeline', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('object_id')->nullable();
            $table->integer('process_id')->nullable();
            $table->text('old_deadline')->nullable();
            $table->text('deadline_date')->nullable();
            $table->text('deadline_time')->nullable();
            $table->text('reason')->nullable();
            $table->integer('requested_by')->nullable();
            $table->text('requested_by_name')->nullable();
            $table->integer('extended_by')->nullable();
            $table->text('extended_by_name')->nullable();
            $table->text('extended_by_reason')->nullable();
            $table->integer('status')->default(0)->nullable();
            $table->text('type')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('extended_timeline');
    }
}
