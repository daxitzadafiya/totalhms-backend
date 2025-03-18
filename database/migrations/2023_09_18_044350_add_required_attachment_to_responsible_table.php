<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRequiredAttachmentToResponsibleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('responsible', function (Blueprint $table) {
            $table->longText('required_comments_array')->nullable()->after('department_array');
            $table->longText('required_attachments_array')->nullable()->after('required_comments_array');
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
            //
        });
    }
}
