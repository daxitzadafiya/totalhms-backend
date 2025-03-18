<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRepositoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('repositories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('added_by');
            $table->string('object_name')->nullable();
            $table->string('object_type')->comment('goal, routine,...')->nullable();
            $table->unsignedBigInteger('object_id')->nullable();
            $table->unsignedBigInteger('attachment_id')->nullable();
            $table->string('attachment_uri')->nullable();
            $table->float('attachment_size')->default(0)->nullable();
            $table->dateTime('date_of_permanent_deletion')->nullable();
            $table->dateTime('deleted_date')->nullable();
            $table->dateTime('restore_date')->nullable();
            $table->unsignedBigInteger('restore_by')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('added_by')->references('id')->on('users');
            $table->foreign('restore_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('repositories');
    }
}
