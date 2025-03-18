<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHelpCenterTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('help_center', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('role')->nullable();
            $table->string('type')->nullable()->comment('main article, topic, title');
            $table->bigInteger('parent_id')->nullable()->comment('null: Help Center main article');
            $table->string('menu_function')->nullable()->comment('management: Management function, basic: Basic function, task: The process of task, report: The process of report');
            $table->boolean('only_company_admin')->default(false)->nullable();
            $table->integer('disable_status')->default(0)->nullable();
            $table->longText('description')->nullable();

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
        Schema::dropIfExists('help_center');
    }
}
