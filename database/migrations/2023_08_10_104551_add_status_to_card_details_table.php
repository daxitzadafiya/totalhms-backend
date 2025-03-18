<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStatusToCardDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('card_details', function (Blueprint $table) {
            $table->boolean('status')->default(1)->after('brand');
        });
        Schema::table('companies', function (Blueprint $table) {
            $table->boolean('is_freeable')->nullable()->after('is_freeze');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('card_details', function (Blueprint $table) {
            $table->dropColumn('status');
        });
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('is_freeable');
        });
    }
}
