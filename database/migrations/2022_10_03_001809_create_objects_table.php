<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateObjectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('objects', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->json('industry')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('added_by')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type', 50)->comment('goal, task, routine, instruction, risk, checklist')->nullable();
            $table->boolean('is_template')->default(false);
            $table->bigInteger('category_id')->nullable();
            $table->boolean('is_suggestion')->default(false);
            $table->boolean('is_valid')->default(true);
            $table->string('status', 20)->comment('new, in-progress, done')->nullable();
            $table->string('source', 50)->comment('subGoal, activity,...')->nullable();
            $table->bigInteger('source_id')->nullable();
            $table->bigInteger('used_count')->deafult(0);
            $table->string('url', 50)->nullable();
            $table->json('update_history')->nullable();
            $table->boolean('required_comment')->default(false);
            $table->boolean('required_attachment')->default(false);

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
        Schema::dropIfExists('objects');
    }
}
