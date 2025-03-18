<?php

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Illuminate\Support\Str;

class PermissionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('role_has_permissions')->delete();
        DB::table('user_permissions')->delete();
        DB::table('permissions')->delete();
        $permissionSuperAdmin_ids = []; // an empty array of stored permission IDs
        $permissionCompanyAdmin_ids = []; // an empty array of stored permission IDs
        $permissionWorker_ids = []; // an empty array of stored permission IDs

        //block permissions
        $blockCompanyAdmin = ['index-company', 'store-company', 'destroy-company', 'admin-company'];

        //worker's permissions exception
        $exceptionWorker = ['show-company', 'show-report', 'store-report',
            'show-deviation', 'store-deviation'];


        $basicPermissionsAttach = [
            'index-category', 'index-project', 'show-project', 'index-department', 'index-employee', 'store-riskanalysis',
            'update-task', 'store-task', 'store-report', 'index-riskanalysis', 'index-task', 'show-task', 'show-riskelementsource',
            'show-checklist', 'index-checklist', 'index-topic', 'index-report', 'show-report', 'index-riskelementsource',
            'store-absence', 'index-absence', 'show-absence', 'update-absence', 'show-riskanalysis', 'index-absencereason',
            'index-role', 'show-employee'
        ];

        // iterate though all routes
        foreach (Route::getRoutes()->getRoutes() as $key => $route) {
            if (strpos($route->uri, 'api/v1') !== false) {
                // get route action
                $action = $route->getActionname();
                // separating controller and method
                $_action = explode('@', $action);

                $controller = $_action[0];
                $controllerName = explode('\\', $controller);
                $controllerName = end($controllerName);
                $controllerName = str_replace('Controller', '', $controllerName);
                $method = end($_action);

                // check if this permission is already exists
                $permission_check = Permission::where(
                    ['controller' => $controller, 'method' => $method]
                )->first();
                if (!$permission_check) {
                    $permission = new Permission;
                    $permission->controller = $controller;
                    $permission->method = $method;
                    $permission->name = str_replace('Controller', '', $controllerName);
                    $permission->key = Str::slug($method . ' ' . $controllerName, '-');
                    $permission->save();
                    // add stored permission id in array
                    $permissionSuperAdmin_ids[] = $permission->id;

                    if (!in_array($permission->key, $blockCompanyAdmin)) {
                        $permissionCompanyAdmin_ids[] = $permission->id;

                        if ($permission->method == 'index' || in_array($permission->key, $basicPermissionsAttach)) {
                            $permissionWorker_ids[] = $permission->id;
                        }
                    }

                    if ($method == 'destroy') {
                        $admin = 'admin';
                        $permissionExtra = new Permission;
                        $permissionExtra->controller = $controller;
                        $permissionExtra->method = $admin;
                        $permissionExtra->name = str_replace('Controller', '', $controllerName);
                        $permissionExtra->key = Str::slug($admin . ' ' . $controllerName, '-');
                        $permissionExtra->save();

                        $permissionSuperAdmin_ids[] = $permissionExtra->id;

                        if (!in_array($permissionExtra->key, $blockCompanyAdmin)) {
                            $permissionCompanyAdmin_ids[] = $permissionExtra->id;
                        }
                    }
                }
            }
        }
        // setup super admin role
        $admin_role = Role::where('name', 'Super admin')->first();
        // attache all permissions to supper admin role
        $admin_role->permissions()->attach($permissionSuperAdmin_ids);
        //attache permissions of Super admin role to Super admin
//        $admin = User::where('role_id', $admin_role->id)->first();
//        $admin->permissions()->attach($permissionSuperAdmin_ids);

        // setup company admin role
        //default by super admin
        $default_company_role = Role::where('name', 'Company admin')->whereNull('company_id')->first();
        $default_company_role->permissions()->attach($permissionCompanyAdmin_ids);
        //company id: 1
        $company_role = Role::where('name', 'Company admin')->where('company_id', 1)->first();
        $company_role->permissions()->attach($permissionCompanyAdmin_ids);
        $companyUsers = User::where('role_id', $company_role->id)->where('company_id', 1)->get();
        foreach ($companyUsers as $companyUser) {
            $companyUser->permissions()->attach($permissionCompanyAdmin_ids);
        }

        // setup worker role
        //default by super admin
        $default_worker_role = Role::where('name', 'Worker')->whereNull('company_id')->first();
        $default_worker_role->permissions()->attach($permissionWorker_ids);
        //company id: 1
        $worker_role = Role::where('name', 'Worker')->where('company_id', 1)->first();
        $worker_role->permissions()->attach($permissionWorker_ids);
        $workers = User::where('role_id', $worker_role->id)->where('company_id', 1)->get();
        foreach ($workers as $worker) {
            $worker->permissions()->attach($permissionWorker_ids);
        }
    }
}
