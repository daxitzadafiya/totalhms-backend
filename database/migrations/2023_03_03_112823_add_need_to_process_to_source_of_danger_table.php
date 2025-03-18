<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNeedToProcessToSourceOfDangerTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('source_of_danger', function (Blueprint $table) {
            $table->boolean('need_to_process')->default(false)->nullable()->after('comment');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('source_of_danger', function (Blueprint $table) {
            $table->dropColumn('need_to_process');
        });
    }
}
