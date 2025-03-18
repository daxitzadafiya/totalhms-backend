<?php

use App\Models\CustomerServicePermission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CustomerServicePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $CsRole= Role::where('level',4)->first();
        $permissions = [
            ['module' => 'companies','is_enabled' => true,'role_id' => $CsRole->id],
            ['module' => 'users','is_enabled' => true, 'role_id' => $CsRole->id ],
            ['module' => 'customerService','is_enabled' => false, 'role_id' => $CsRole->id ],
            ['module' => 'invites','is_enabled' => true, 'role_id' => $CsRole->id ],
            ['module' => 'roles','is_enabled' => true, 'role_id' => $CsRole->id ],
            ['module' => 'jobTitles','is_enabled' => true, 'role_id' => $CsRole->id ],
            ['module' => 'billings','is_enabled' => true, 'role_id' => $CsRole->id ],
            ['module' => 'settings','is_enabled' => true, 'role_id' => $CsRole->id ],
            ['module' => 'email_logs','is_enabled' => true, 'role_id' => $CsRole->id ],
        ];
        DB::table('customer_service_permissions')->delete();
        foreach($permissions as $permission){
            CustomerServicePermission::create([
               'module' => $permission['module'],
               'is_enabled' => $permission['is_enabled'],
               'role_id' => $permission['role_id'],
            ]);
            
        }
    }
}