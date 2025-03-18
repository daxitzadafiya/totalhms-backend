<?php

use App\Models\Role;
use Illuminate\Database\Seeder;

class CustomerServiceRoleSeeder extends Seeder
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
        
        $tmp5 = Role::create(array(
            'id' => 5,
            'name' => "Customer service",
            'level' => 4,
            'permission' => $permission,
        ));
    }
}