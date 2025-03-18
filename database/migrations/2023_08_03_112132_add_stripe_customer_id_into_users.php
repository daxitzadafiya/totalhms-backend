<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStripeCustomerIdIntoUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('customer_stripe_id')->nullable()->after('role_id');
            $table->string('payment_method')->nullable()->after('customer_stripe_id');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->boolean('is_freeze')->after('status')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('customer_stripe_id');
            $table->dropColumn('payment_method');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('is_freeze');
        });
    }
}
