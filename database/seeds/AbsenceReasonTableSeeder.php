<?php

use Illuminate\Database\Seeder;
use App\Models\AbsenceReason;

class AbsenceReasonTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('absence_reasons')->delete();
        $processorArray = ['manager', 'admin'];
        $tmp1 = AbsenceReason::create(array(
            'id' => 1,
            'added_by' => 1,
            'processor' => json_encode($processorArray),
            'class_of_absence' => 'interval',
            'type' => 'Illness',
            'interval_absence' => 3,
            'days_off' => 4,
            'reset_time_number' => 12,
            'reset_time_unit' => 'month',
            'apply_time_number' => 2,
            'apply_time_unit' => 'month',
            'deadline_registration_number' => 1,
            'deadline_registration_unit' => 'week',
        ));
        $tmp2 = AbsenceReason::create(array(
            'id' => 2,
            'added_by' => 1,
            'processor' => json_encode($processorArray),
            'type' => 'Sick child',
            'class_of_absence' => 'day',
            'sick_child' => 1,
            'days_off' => 10,
            'days_off_exception' => 15,
            'extra_alone_custody' => 2,
            'sick_child_max_age' => 12,
            'sick_child_max_age_handicapped' => 18,
            'reset_time_number' => 12,
            'reset_time_unit' => 'month',
            'apply_time_number' => 2,
            'apply_time_unit' => 'month',
            'deadline_registration_number' => 1,
            'deadline_registration_unit' => 'week',
        ));
        $tmp3 = AbsenceReason::create(array(
            'id' => 3,
            'type' => 'Illness',
            'class_of_absence' => 'interval',
            'company_id' => 1,
            'processor' => json_encode($processorArray),
            'added_by' => 1,
            'related_id' => 1,
            'interval_absence' => 3,
            'days_off' => 4,
            'reset_time_number' => 12,
            'reset_time_unit' => 'month',
            'apply_time_number' => 2,
            'apply_time_unit' => 'month',
            'deadline_registration_number' => 1,
            'deadline_registration_unit' => 'week',
        ));
        $tmp4 = AbsenceReason::create(array(
            'id' => 4,
            'type' => 'Sick child',
            'class_of_absence' => 'day',
            'company_id' => 1,
            'processor' => json_encode($processorArray),
            'added_by' => 1,
            'related_id' => 2,
            'sick_child' => 1,
            'days_off' => 10,
            'days_off_exception' => 15,
            'extra_alone_custody' => 2,
            'sick_child_max_age' => 12,
            'sick_child_max_age_handicapped' => 18,
            'reset_time_number' => 12,
            'reset_time_unit' => 'month',
            'apply_time_number' => 2,
            'apply_time_unit' => 'month',
            'deadline_registration_number' => 1,
            'deadline_registration_unit' => 'week',
        ));
    }
}
