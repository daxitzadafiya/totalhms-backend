<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRequiredCommentToResponsibleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('responsible', function (Blueprint $table) {
            $table->boolean('required_comment')->default(false)->after('employee_array');
            $table->boolean('required_attachment')->default(false)->after('required_comment');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('responsible', function (Blueprint $table) {
            $table->dropColumn('required_comment');
            $table->dropColumn('required_attachment');
        });
    }
}
