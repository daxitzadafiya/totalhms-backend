<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(PermissionsFormatTableSeeder::class);
        $this->call(IndustriesTableSeeder::class);
        $this->call(CompaniesTableSeeder::class);
        $this->call(RolesTableSeeder::class);
        $this->call(UsersTableSeeder::class);
//        $this->call(PermissionTableSeeder::class);
        $this->call(DepartmentsTableSeeder::class);
        $this->call(CategoriesTableSeeder::class);
        $this->call(CategoriesTableSeederV2::class);
        $this->call(EmployeesTableSeeder::class);
        $this->call(AbsenceReasonTableSeeder::class);
        $this->call(MessagesTableSeeder::class);
        $this->call(SettingSeeder::class);
        $this->call(EmailContentSeeder::class);
        $this->call(CustomerServiceRoleSeeder::class);
        $this->call(CustomerServicePermissionSeeder::class);
    }
}