<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDiscountToBillingDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('billing_details', function (Blueprint $table) {
            $table->integer('discount')->default(0)->after('additional_user_amount');
            $table->integer('vat')->default(0)->after('discount');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('discount');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('billing_details', function (Blueprint $table) {
            $table->dropColumn(['discount', 'vat']);
        });
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->integer('discount')->default(0);
        });
    }
}
