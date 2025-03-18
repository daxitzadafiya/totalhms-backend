<?php

use Illuminate\Database\Seeder;
use App\Models\User;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(User::class)->create([
            'id' => 1,
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'email' => 'superadmin@totalhms.no',
            'password' => 'hms123',
            'role_id' => 1,
            'status' => 'active',
        ]);

        factory(User::class)->create([
            'id' => 2,
            'first_name' => 'Company',
            'last_name' => 'Admin',
            'email' => 'companyadmin@totalhms.no',
            'password' => 'hms123',
            'role_id' => 2,
            'company_id' => 1,
            'added_by' => 1,
            'status' => 'active',
        ]);

        factory(User::class)->create([
            'id' => 3,
            'first_name' => 'Manager',
            'last_name' => 'Default',
            'email' => 'manager@totalhms.no',
            'password' => 'hms123',
            'role_id' => 3,
            'company_id' => 1,
            'added_by' => 2,
            'status' => 'active',
        ]);

        factory(User::class)->create([
            'id' => 4,
            'first_name' => 'Worker',
            'last_name' => 'Default',
            'email' => 'worker@totalhms.no',
            'password' => 'hms123',
            'role_id' => 4,
            'company_id' => 1,
            'added_by' => 2,
            'status' => 'active',
        ]);

//        factory(User::class)->create([
//            'id' => 5,
//            'first_name' => 'HR 3',
//            'last_name' => 'Manager',
//            'email' => 'hr3-manager@totalhms.no',
//            'password' => '123123',
//            'role_id' => 3,
//            'company_id' => 1,
//            'added_by' => 2,
//            'status' => 'active',
//        ]);
    }
}
