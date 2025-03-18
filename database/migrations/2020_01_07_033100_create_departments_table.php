<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDepartmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('description')-> nullable();
            $table->unsignedBigInteger('company_id');
            $table->integer('disable_status')->default(0)->nullable();
            $table->bigInteger('parent_id')->nullable()->comment('null: root department of company');
            $table->json('manager_job_title')->nullable();
            $table->json('manager_array')->nullable();
            $table->json('member_array')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('departments');
    }
}
