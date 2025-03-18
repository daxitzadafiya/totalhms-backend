<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePermissionsFormatTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('permissions_format', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('function');
            $table->string('filter_by')->comment('user, manager');
            $table->boolean('show')->default(false);
            $table->string('permission_name')->comment('view, detail, basic, resource, process');
            $table->string('permission_type')->default('unknown');
            $table->boolean('permission_disable')->default(false);
            $table->string('permission_apply')->default('unknown');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('permissions_format');
    }
}
