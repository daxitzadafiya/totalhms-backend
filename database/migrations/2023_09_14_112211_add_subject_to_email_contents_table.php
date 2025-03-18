<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSubjectToEmailContentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('email_contents', function (Blueprint $table) {
            $table->string('subject')->nullable()->after('title');
            $table->boolean('is_sms')->default(false)->after('source_code');
            $table->text('sms_description')->nullable()->after('description');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('email_contents', function (Blueprint $table) {
            $table->dropColumn(['subject','is_sms','sms_description']);
        });
    }
}