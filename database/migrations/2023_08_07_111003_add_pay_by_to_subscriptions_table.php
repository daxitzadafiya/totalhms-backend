<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPayByToSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->boolean('pay_by')->nullable()->after('discount');
            $table->dateTime('cancelled_at')->nullable()->after('deactivated_at');
            $table->dateTime('next_billing_at')->nullable()->after('billed_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['pay_by','cancelled_at','next_billing_at']);
        });
    }
}
