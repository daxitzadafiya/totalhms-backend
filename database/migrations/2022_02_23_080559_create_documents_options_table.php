<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDocumentsOptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('documents_options', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('document_id');
            $table->boolean('is_renewed')->nullable();
            $table->string('renewed_employee_array')->nullable();
            $table->bigInteger('deadline')->nullable();
            $table->boolean('show_manager')->nullable();
            $table->boolean('is_public')->default(0)->nullable();
            $table->string('security_department_array')->nullable();
            $table->string('security_project_array')->nullable();
            $table->string('security_employee_array')->nullable();
            $table->bigInteger('report_question_id')->nullable()->comment('a value of question_id on table reports');
            $table->timestamps();

            $table->foreign('document_id')->references('id')->on('documents_new')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('documents_options');
    }
}
