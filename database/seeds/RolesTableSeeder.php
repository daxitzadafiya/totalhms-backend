<?php

use Illuminate\Database\Seeder;
use App\Models\Role;

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permissionArray = [];
        $permission = json_encode($permissionArray);

        DB::table('roles')->delete();
        $tmp1 = Role::create(array(
            'id' => 1,
            'name' => "Super admin",
            'level' => 0,
            'permission' => $permission,
        ));
        $tmp2 = Role::create(array(
            'id' => 2,
            'name' => "Company admin",
            'level' => 1,
            'permission' => $permission,
        ));
        $tmp3 = Role::create(array(
            'id' => 3,
            'name' => "Manager",
            'level' => 2,
            'permission' => $permission,
        ));
        $tmp4 = Role::create(array(
            'id' => 4,
            'name' => "User",
            'level' => 3,
            'permission' => $permission,
        ));
    }
}
