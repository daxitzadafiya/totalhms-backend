<?php

use Illuminate\Database\Seeder;
use App\Models\Department;

class DepartmentsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('departments') -> delete();
        $tmp1 = Department::create(array(
            'id' => 1,
            'name' => "Director",
            'description' => 'Director department',
            'company_id' => 1,
        ));

        $tmp2 = Department::create(array(
            'id' => 2,
            'name' => "Production",
            'description' => 'Production department',
            'company_id' => 1,
            'parent_id' => 1,
        ));

        $tmp3 = Department::create(array(
            'id' => 3,
            'name' => "IT",
            'description' => 'IT department',
            'company_id' => 1,
            'parent_id' => 2,
        ));

        $tmp4 = Department::create(array(
            'id' => 4,
            'name' => "Sales",
            'description' => 'Sales department',
            'company_id' => 1,
            'parent_id' => 1,
        ));

        $tmp5 = Department::create(array(
            'id' => 5,
            'name' => "Marketing",
            'description' => 'Marketing department',
            'company_id' => 1,
            'parent_id' => 4,
        ));

        $tmp6 = Department::create(array(
            'id' => 6,
            'name' => "HR",
            'description' => 'HR department',
            'company_id' => 1,
            'parent_id' => 1,
        ));

        $tmp7 = Department::create(array(
            'id' => 7,
            'name' => "Workers",
            'description' => 'Workers department',
            'company_id' => 1,
            'parent_id' => 3,
        ));
    }
}
