<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFikenIdToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('fiken_customer_id')->nullable()->after('payment_method');
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->string('fiken_product_number')->nullable()->after('free_trial_months');
            $table->string('fiken_plan_id')->nullable()->after('fiken_product_number');
            $table->string('fiken_additional_id')->nullable()->after('fiken_plan_id');
        });

        Schema::table('addons', function (Blueprint $table) {
            $table->string('description')->nullable()->after('frequency');
            $table->string('fiken_product_number')->nullable()->after('volume');
            $table->string('fiken_addon_id')->nullable()->after('fiken_product_number');
            $table->renameColumn('name', 'title');
        });

        Schema::table('billing_details', function (Blueprint $table) {
            $table->string('fiken_invoice_id')->nullable()->after('status');
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
            $table->dropColumn('fiken_customer_id');
        });
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['fiken_plan_id','fiken_additional_id','fiken_product_number']);
        });
        Schema::table('addons', function (Blueprint $table) {
            $table->dropColumn(['fiken_addon_id','description','fiken_product_number']);
            $table->renameColumn('title', 'name');
        });
        Schema::table('billing_details', function (Blueprint $table) {
            $table->dropColumn('fiken_invoice_id');
        });
    }
}
