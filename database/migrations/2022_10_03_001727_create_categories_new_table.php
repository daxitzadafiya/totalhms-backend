<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCategoriesNewTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('categories_new', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->json('industry')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('added_by')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type', 50)->comment('goal, task, routine, instruction, document, risk, checklist, report, deviation, riskAnalysis, contact,...')->nullable();
            $table->string('added_from', 50)->comment('company, contact, employee')->nullable();
            $table->boolean('is_priority')->default(false);
            $table->boolean('is_valid')->default(true);
            $table->string('source', 50)->comment('goal, task, routine, instruction, document, risk element source, checklist, report, deviation, risk analysis, contact,...')->nullable();

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
        Schema::dropIfExists('categories_new');
    }
}
