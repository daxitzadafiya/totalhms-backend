<?php


use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Carbon\Carbon;


class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('email')->unique();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('password')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('role_id')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('personal_number')->nullable();
            $table->string('avatar')->nullable();
            $table->integer('zip_code')->nullable();
            $table->integer('added_by')->unsigned()->nullable();
            $table->string('status')->default('pending')->comment('pending, active, inactive, banned');

            $table->dateTime('active_date')->nullable();
            $table->dateTime('last_login')->default(Carbon::now()->format('Y-m-d H:i:s'));
            $table->rememberToken();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('role_id')->references('id')->on('roles');
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
