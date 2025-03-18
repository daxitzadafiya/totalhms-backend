<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeCompanyIdNullableToEmailLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('email_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->change();
            $table->boolean('for_admin')->after('description')->nullable();
        });
        Schema::table('customer_service_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id')->nullable()->after('module');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('email_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->change();
            $table->dropColumn('for_admin');
        });

        Schema::table('customer_service_permissions', function (Blueprint $table) {
            $table->dropForeign('customer_service_permissions_role_id_foreign');
            $table->dropColumn('role_id');
        });
    }
}