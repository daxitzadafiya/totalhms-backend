<?php

use Illuminate\Database\Seeder;
use App\Models\Employee;

class EmployeesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('employees') -> delete();
        $absence_info = [];
        $absence_illness = [
          'absence_reason_id' => 3,
            'class_of_absence' => 'interval',
            'used_interval_absence' => 0,
            'pending_interval_absence' => 0,
            'used_illegal_interval_absence' => 0,
            'pending_illegal_interval_absence' => 0,
            'used_days_off' => 0,
            'pending_days_off' => 0,
            'used_illegal_days_off' => 0,
            'pending_illegal_days_off' => 0,
            'max_days_off' => 0,
        ];
        array_push($absence_info, $absence_illness);
        $absence_sick_child = [
          'absence_reason_id' => 4,
            'class_of_absence' => 'day',
            'used_interval_absence' => 0,
            'pending_interval_absence' => 0,
            'used_illegal_interval_absence' => 0,
            'pending_illegal_interval_absence' => 0,
            'used_days_off' => 0,
            'pending_days_off' => 0,
            'used_illegal_days_off' => 0,
            'pending_illegal_days_off' => 0,
            'max_days_off' => 0,
        ];
        array_push($absence_info, $absence_sick_child);
        $absence_info = json_encode($absence_info);

        $tmp1 = Employee::create(array(
            'id' => 1,
            'user_id' => 2,
            'absence_info' => $absence_info,
        ));

        $tmp2 = Employee::create(array(
            'id' => 2,
            'user_id' => 3,
            'department_id' => 6,
            'absence_info' => $absence_info,
        ));

        $tmp3 = Employee::create(array(
            'id' => 3,
            'user_id' => 4,
            'department_id' => 3,
            'absence_info' => $absence_info,
        ));
//
//        $tmp4 = Employee::create(array(
//            'id' => 4,
//            'user_id' => 5,
//            'absence_info' => $absence_info,
//        ));
    }
}
