<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddVotesToDocumentsNewTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('documents_new', function (Blueprint $table) {
                $table->bigInteger('task_id')->after('url')->nullable();
                $table->tinyInteger('is_reminder')->default(0)->after('task_id');        });    

     }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('documents_new', function (Blueprint $table) {
            $table->dropColumn('task_id');
            $table->dropColumn('is_reminder');
            $table->dropColumn('is_shared');
        });
    }
}
