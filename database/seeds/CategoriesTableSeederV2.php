<?php

use Illuminate\Database\Seeder;
use App\Models\CategoryV2;
use Illuminate\Support\Facades\DB;

class CategoriesTableSeederV2 extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('categories_new') -> delete();
        // goal
        $tmp1 = CategoryV2::create(array(
            'id' => 1,
            'industry' => '[1,2,3,4,5,6,7]',
            'name' => "Fire and Emergency",
            'description' => 'Fire and Emergency',
            'type' => 'goal',
            'added_by' => 1,
            'is_priority' => 0,
            'is_valid' => 1,
        ));
        $tmp2 = CategoryV2::create(array(
            'id' => 2,
            'industry' => '[1,2,3,4,5,6,7]',
            'name' => "HSE-activities",
            'description' => 'HSE-activities',
            'type' => 'goal',
            'added_by' => 1,
            'is_priority' => 0,
            'is_valid' => 1,
        ));
        $tmp3 = CategoryV2::create(array(
            'id' => 3,
            'industry' => '[1,2,3,4,5,6,7]',
            'name' => "Environment",
            'description' => 'Environment',
            'type' => 'goal',
            'added_by' => 1,
            'is_priority' => 0,
            'is_valid' => 1,
        ));
        $tmp4 = CategoryV2::create(array(
            'id' => 4,
            'industry' => '[1,2,3,4,5,6,7]',
            'name' => "Psychosocial routines",
            'description' => 'Psychosocial routines',
            'type' => 'goal',
            'added_by' => 1,
            'is_priority' => 0,
            'is_valid' => 1,
        ));
        $tmp5 = CategoryV2::create(array(
            'id' => 5,
            'industry' => '[1,2,3,4,5,6,7]',
            'name' => "Physical and chemical conditions",
            'description' => 'Physical and chemical conditions',
            'type' => 'goal',
            'added_by' => 1,
            'is_priority' => 0,
            'is_valid' => 1,
        ));
        $tmp6 = CategoryV2::create(array(
            'id' => 6,
            'industry' => '[1,2,3,4,5,6,7]',
            'name' => "Others",
            'description' => 'Others',
            'type' => 'goal',
            'added_by' => 1,
            'is_priority' => 1,
            'is_valid' => 1,
        ));

        // LOGO
        $tmp7 = CategoryV2::create(array(
            'id' => 7,
            'industry' => '[1,2,3,4,5,6,7]',
            'name' => "Logo",
            'description' => 'Logo',
            'type' => 'attachment',
            'added_from' => 1, //company
            'added_by' => 1,
            'is_priority' => 0,
            'is_valid' => 1,
        ));
        // AVATAR
        $tmp8 = CategoryV2::create(array(
            'id' => 8,
            'industry' => '[1,2,3,4,5,6,7]',
            'name' => "Avatar",
            'description' => 'Avatar',
            'type' => 'attachment',
            'added_from' => 3, // employee
            'added_by' => 1,
            'is_priority' => 0,
            'is_valid' => 1,
        ));

        // routine
        $tmp9 = CategoryV2::create(array(
            'id' => 9,
            'industry' => '[1,2,3,4,5,6,7]',
            'name' => "Fire and Emergency",
            'description' => 'Fire and Emergency',
            'type' => 'routine',
            'added_by' => 1,
            'is_priority' => 0,
            'is_valid' => 1,
        ));
        $tmp10 = CategoryV2::create(array(
            'id' => 10,
            'industry' => '[1,2,3,4,5,6,7]',
            'name' => "HSE-activities",
            'description' => 'HSE-activities',
            'type' => 'routine',
            'added_by' => 1,
            'is_priority' => 0,
            'is_valid' => 1,
        ));
        $tmp11 = CategoryV2::create(array(
            'id' => 11,
            'industry' => '[1,2,3,4,5,6,7]',
            'name' => "Environment",
            'description' => 'Environment',
            'type' => 'routine',
            'added_by' => 1,
            'is_priority' => 0,
            'is_valid' => 1,
        ));
        $tmp12 = CategoryV2::create(array(
            'id' => 12,
            'industry' => '[1,2,3,4,5,6,7]',
            'name' => "Psychosocial routines",
            'description' => 'Psychosocial routines',
            'type' => 'routine',
            'added_by' => 1,
            'is_priority' => 0,
            'is_valid' => 1,
        ));
        $tmp13 = CategoryV2::create(array(
            'id' => 13,
            'industry' => '[1,2,3,4,5,6,7]',
            'name' => "Physical and chemical conditions",
            'description' => 'Physical and chemical conditions',
            'type' => 'routine',
            'added_by' => 1,
            'is_priority' => 0,
            'is_valid' => 1,
        ));
        $tmp14 = CategoryV2::create(array(
            'id' => 14,
            'industry' => '[1,2,3,4,5,6,7]',
            'name' => "Others",
            'description' => 'Others',
            'type' => 'routine',
            'added_by' => 1,
            'is_priority' => 1,
            'is_valid' => 1,
        ));

        // instruction
        $tmp15 = CategoryV2::create(array(
            'id' => 15,
            'industry' => '[1,2,3,4,5,6,7]',
            'name' => "Fire and Emergency",
            'description' => 'Fire and Emergency',
            'type' => 'instruction',
            'added_by' => 1,
            'is_priority' => 0,
            'is_valid' => 1,
        ));
        $tmp16 = CategoryV2::create(array(
            'id' => 16,
            'industry' => '[1,2,3,4,5,6,7]',
            'name' => "HSE-activities",
            'description' => 'HSE-activities',
            'type' => 'instruction',
            'added_by' => 1,
            'is_priority' => 0,
            'is_valid' => 1,
        ));
        $tmp17 = CategoryV2::create(array(
            'id' => 17,
            'industry' => '[1,2,3,4,5,6,7]',
            'name' => "Environment",
            'description' => 'Environment',
            'type' => 'instruction',
            'added_by' => 1,
            'is_priority' => 0,
            'is_valid' => 1,
        ));
        $tmp18 = CategoryV2::create(array(
            'id' => 18,
            'industry' => '[1,2,3,4,5,6,7]',
            'name' => "Psychosocial routines",
            'description' => 'Psychosocial routines',
            'type' => 'instruction',
            'added_by' => 1,
            'is_priority' => 0,
            'is_valid' => 1,
        ));
        $tmp19 = CategoryV2::create(array(
            'id' => 19,
            'industry' => '[1,2,3,4,5,6,7]',
            'name' => "Physical and chemical conditions",
            'description' => 'Physical and chemical conditions',
            'type' => 'instruction',
            'added_by' => 1,
            'is_priority' => 0,
            'is_valid' => 1,
        ));
        $tmp20 = CategoryV2::create(array(
            'id' => 20,
            'industry' => '[1,2,3,4,5,6,7]',
            'name' => "Others",
            'description' => 'Others',
            'type' => 'instruction',
            'added_by' => 1,
            'is_priority' => 1,
            'is_valid' => 1,
        ));

        // document
        $tmp21 = CategoryV2::create(array(
            'id' => 21,
            'industry' => '[1,2,3,4,5,6,7]',
            'name' => "Fire and Emergency",
            'description' => 'Fire and Emergency',
            'type' => 'document',
            'added_by' => 1,
            'is_priority' => 0,
            'is_valid' => 1,
        ));
        $tmp22 = CategoryV2::create(array(
            'id' => 22,
            'industry' => '[1,2,3,4,5,6,7]',
            'name' => "HSE-activities",
            'description' => 'HSE-activities',
            'type' => 'document',
            'added_by' => 1,
            'is_priority' => 0,
            'is_valid' => 1,
        ));
        $tmp23 = CategoryV2::create(array(
            'id' => 23,
            'industry' => '[1,2,3,4,5,6,7]',
            'name' => "Environment",
            'description' => 'Environment',
            'type' => 'document',
            'added_by' => 1,
            'is_priority' => 0,
            'is_valid' => 1,
        ));
        $tmp24 = CategoryV2::create(array(
            'id' => 24,
            'industry' => '[1,2,3,4,5,6,7]',
            'name' => "Psychosocial routines",
            'description' => 'Psychosocial routines',
            'type' => 'document',
            'added_by' => 1,
            'is_priority' => 0,
            'is_valid' => 1,
        ));
        $tmp25 = CategoryV2::create(array(
            'id' => 25,
            'industry' => '[1,2,3,4,5,6,7]',
            'name' => "Physical and chemical conditions",
            'description' => 'Physical and chemical conditions',
            'type' => 'document',
            'added_by' => 1,
            'is_priority' => 0,
            'is_valid' => 1,
        ));
        $tmp26 = CategoryV2::create(array(
            'id' => 26,
            'industry' => '[1,2,3,4,5,6,7]',
            'name' => "Others",
            'description' => 'Others',
            'type' => 'document',
            'added_by' => 1,
            'is_priority' => 1,
            'is_valid' => 1,
        ));

        // contact
        $tmp27 = CategoryV2::create(array(
            'id' => 27,
            'industry' => '[1,2,3,4,5,6,7]',
            'name' => "Fire and Emergency",
            'description' => 'Fire and Emergency',
            'type' => 'contact',
            'added_by' => 1,
            'is_priority' => 0,
            'is_valid' => 1,
        ));
        $tmp28 = CategoryV2::create(array(
            'id' => 28,
            'industry' => '[1,2,3,4,5,6,7]',
            'name' => "HSE-activities",
            'description' => 'HSE-activities',
            'type' => 'contact',
            'added_by' => 1,
            'is_priority' => 0,
            'is_valid' => 1,
        ));
        $tmp29 = CategoryV2::create(array(
            'id' => 29,
            'industry' => '[1,2,3,4,5,6,7]',
            'name' => "Environment",
            'description' => 'Environment',
            'type' => 'contact',
            'added_by' => 1,
            'is_priority' => 0,
            'is_valid' => 1,
        ));
        $tmp30 = CategoryV2::create(array(
            'id' => 30,
            'industry' => '[1,2,3,4,5,6,7]',
            'name' => "Psychosocial routines",
            'description' => 'Psychosocial routines',
            'type' => 'contact',
            'added_by' => 1,
            'is_priority' => 0,
            'is_valid' => 1,
        ));
        $tmp31 = CategoryV2::create(array(
            'id' => 31,
            'industry' => '[1,2,3,4,5,6,7]',
            'name' => "Physical and chemical conditions",
            'description' => 'Physical and chemical conditions',
            'type' => 'contact',
            'added_by' => 1,
            'is_priority' => 0,
            'is_valid' => 1,
        ));
        $tmp32 = CategoryV2::create(array(
            'id' => 32,
            'industry' => '[1,2,3,4,5,6,7]',
            'name' => "Others",
            'description' => 'Others',
            'type' => 'contact',
            'added_by' => 1,
            'is_priority' => 1,
            'is_valid' => 1,
        ));

        // checklist
        $tmp33 = CategoryV2::create(array(
            'id' => 33,
            'industry' => '[1,2,3,4,5,6,7]',
            'name' => "Fire and Emergency",
            'description' => 'Fire and Emergency',
            'type' => 'checklist',
            'added_by' => 1,
            'is_priority' => 0,
            'is_valid' => 1,
        ));
        $tmp34 = CategoryV2::create(array(
            'id' => 34,
            'industry' => '[1,2,3,4,5,6,7]',
            'name' => "HSE-activities",
            'description' => 'HSE-activities',
            'type' => 'checklist',
            'added_by' => 1,
            'is_priority' => 0,
            'is_valid' => 1,
        ));
        $tmp35 = CategoryV2::create(array(
            'id' => 35,
            'industry' => '[1,2,3,4,5,6,7]',
            'name' => "Environment",
            'description' => 'Environment',
            'type' => 'checklist',
            'added_by' => 1,
            'is_priority' => 0,
            'is_valid' => 1,
        ));
        $tmp36 = CategoryV2::create(array(
            'id' => 36,
            'industry' => '[1,2,3,4,5,6,7]',
            'name' => "Psychosocial routines",
            'description' => 'Psychosocial routines',
            'type' => 'checklist',
            'added_by' => 1,
            'is_priority' => 0,
            'is_valid' => 1,
        ));
        $tmp37 = CategoryV2::create(array(
            'id' => 37,
            'industry' => '[1,2,3,4,5,6,7]',
            'name' => "Physical and chemical conditions",
            'description' => 'Physical and chemical conditions',
            'type' => 'checklist',
            'added_by' => 1,
            'is_priority' => 0,
            'is_valid' => 1,
        ));
        $tmp38 = CategoryV2::create(array(
            'id' => 38,
            'industry' => '[1,2,3,4,5,6,7]',
            'name' => "Others",
            'description' => 'Others',
            'type' => 'checklist',
            'added_by' => 1,
            'is_priority' => 1,
            'is_valid' => 1,
        ));

        // deviation
        $tmp39 = CategoryV2::create(array(
            'id' => 39,
            'industry' => '[1,2,3,4,5,6,7]',
            'name' => "Fire and Emergency",
            'description' => 'Fire and Emergency',
            'type' => 'deviation',
            'added_by' => 1,
            'is_priority' => 0,
            'is_valid' => 1,
        ));
        $tmp40 = CategoryV2::create(array(
            'id' => 40,
            'industry' => '[1,2,3,4,5,6,7]',
            'name' => "HSE-activities",
            'description' => 'HSE-activities',
            'type' => 'deviation',
            'added_by' => 1,
            'is_priority' => 0,
            'is_valid' => 1,
        ));
        $tmp41 = CategoryV2::create(array(
            'id' => 41,
            'industry' => '[1,2,3,4,5,6,7]',
            'name' => "Environment",
            'description' => 'Environment',
            'type' => 'deviation',
            'added_by' => 1,
            'is_priority' => 0,
            'is_valid' => 1,
        ));
        $tmp42 = CategoryV2::create(array(
            'id' => 42,
            'industry' => '[1,2,3,4,5,6,7]',
            'name' => "Psychosocial routines",
            'description' => 'Psychosocial routines',
            'type' => 'deviation',
            'added_by' => 1,
            'is_priority' => 0,
            'is_valid' => 1,
        ));
        $tmp43 = CategoryV2::create(array(
            'id' => 43,
            'industry' => '[1,2,3,4,5,6,7]',
            'name' => "Physical and chemical conditions",
            'description' => 'Physical and chemical conditions',
            'type' => 'deviation',
            'added_by' => 1,
            'is_priority' => 0,
            'is_valid' => 1,
        ));
        $tmp44 = CategoryV2::create(array(
            'id' => 44,
            'industry' => '[1,2,3,4,5,6,7]',
            'name' => "Others",
            'description' => 'Others',
            'type' => 'deviation',
            'added_by' => 1,
            'is_priority' => 1,
            'is_valid' => 1,
        ));

        // risk
        $tmp45 = CategoryV2::create(array(
            'id' => 45,
            'industry' => '[1,2,3,4,5,6,7]',
            'name' => "Fire and Emergency",
            'description' => 'Fire and Emergency',
            'type' => 'risk',
            'added_by' => 1,
            'is_priority' => 0,
            'is_valid' => 1,
        ));
        $tmp46 = CategoryV2::create(array(
            'id' => 46,
            'industry' => '[1,2,3,4,5,6,7]',
            'name' => "HSE-activities",
            'description' => 'HSE-activities',
            'type' => 'risk',
            'added_by' => 1,
            'is_priority' => 0,
            'is_valid' => 1,
        ));
        $tmp47 = CategoryV2::create(array(
            'id' => 47,
            'industry' => '[1,2,3,4,5,6,7]',
            'name' => "Environment",
            'description' => 'Environment',
            'type' => 'risk',
            'added_by' => 1,
            'is_priority' => 0,
            'is_valid' => 1,
        ));
        $tmp48 = CategoryV2::create(array(
            'id' => 48,
            'industry' => '[1,2,3,4,5,6,7]',
            'name' => "Psychosocial routines",
            'description' => 'Psychosocial routines',
            'type' => 'risk',
            'added_by' => 1,
            'is_priority' => 0,
            'is_valid' => 1,
        ));
        $tmp49 = CategoryV2::create(array(
            'id' => 49,
            'industry' => '[1,2,3,4,5,6,7]',
            'name' => "Physical and chemical conditions",
            'description' => 'Physical and chemical conditions',
            'type' => 'risk',
            'added_by' => 1,
            'is_priority' => 0,
            'is_valid' => 1,
        ));
        $tmp50 = CategoryV2::create(array(
            'id' => 50,
            'industry' => '[1,2,3,4,5,6,7]',
            'name' => "Others",
            'description' => 'Others',
            'type' => 'risk',
            'added_by' => 1,
            'is_priority' => 1,
            'is_valid' => 1,
        ));

        // task
        $tmp51 = CategoryV2::create(array(
            'id' => 51,
            'industry' => '[1,2,3,4,5,6,7]',
            'name' => "Fire and Emergency",
            'description' => 'Fire and Emergency',
            'type' => 'task',
            'added_by' => 1,
            'is_priority' => 0,
            'is_valid' => 1,
        ));
        $tmp52 = CategoryV2::create(array(
            'id' => 52,
            'industry' => '[1,2,3,4,5,6,7]',
            'name' => "HSE-activities",
            'description' => 'HSE-activities',
            'type' => 'task',
            'added_by' => 1,
            'is_priority' => 0,
            'is_valid' => 1,
        ));
        $tmp53 = CategoryV2::create(array(
            'id' => 53,
            'industry' => '[1,2,3,4,5,6,7]',
            'name' => "Environment",
            'description' => 'Environment',
            'type' => 'task',
            'added_by' => 1,
            'is_priority' => 0,
            'is_valid' => 1,
        ));
        $tmp54 = CategoryV2::create(array(
            'id' => 54,
            'industry' => '[1,2,3,4,5,6,7]',
            'name' => "Psychosocial routines",
            'description' => 'Psychosocial routines',
            'type' => 'task',
            'added_by' => 1,
            'is_priority' => 0,
            'is_valid' => 1,
        ));
        $tmp55 = CategoryV2::create(array(
            'id' => 55,
            'industry' => '[1,2,3,4,5,6,7]',
            'name' => "Physical and chemical conditions",
            'description' => 'Physical and chemical conditions',
            'type' => 'task',
            'added_by' => 1,
            'is_priority' => 0,
            'is_valid' => 1,
        ));
        $tmp56 = CategoryV2::create(array(
            'id' => 56,
            'industry' => '[1,2,3,4,5,6,7]',
            'name' => "Others",
            'description' => 'Others',
            'type' => 'task',
            'added_by' => 1,
            'is_priority' => 1,
            'is_valid' => 1,
        ));

        // attachment
        $tmp57 = CategoryV2::create(array(
            'id' => 57,
            'industry' => '[1,2,3,4,5,6,7]',
            'name' => "Others",
            'description' => 'Others',
            'type' => 'attachment',
            'added_from' => 1, //company
            'added_by' => 1,
            'is_priority' => 1,
            'is_valid' => 1,
        ));

        // // risk analysis
        // $tmp58 = CategoryV2::create(array(
        //     'id' => 45,
        //     'industry' => '[1,2,3,4,5,6,7]',
        //     'name' => "Fire and Emergency",
        //     'description' => 'Fire and Emergency',
        //     'type' => 'risk-analysis',
        //     'added_by' => 1,
        //     'is_priority' => 0,
        //     'is_valid' => 1,
        // ));
        // $tmp59 = CategoryV2::create(array(
        //     'id' => 46,
        //     'industry' => '[1,2,3,4,5,6,7]',
        //     'name' => "HSE-activities",
        //     'description' => 'HSE-activities',
        //     'type' => 'risk-analysis',
        //     'added_by' => 1,
        //     'is_priority' => 0,
        //     'is_valid' => 1,
        // ));
        // $tmp60 = CategoryV2::create(array(
        //     'id' => 47,
        //     'industry' => '[1,2,3,4,5,6,7]',
        //     'name' => "Environment",
        //     'description' => 'Environment',
        //     'type' => 'risk-analysis',
        //     'added_by' => 1,
        //     'is_priority' => 0,
        //     'is_valid' => 1,
        // ));
        // $tmp61 = CategoryV2::create(array(
        //     'id' => 48,
        //     'industry' => '[1,2,3,4,5,6,7]',
        //     'name' => "Psychosocial routines",
        //     'description' => 'Psychosocial routines',
        //     'type' => 'risk-analysis',
        //     'added_by' => 1,
        //     'is_priority' => 0,
        //     'is_valid' => 1,
        // ));
        // $tmp62 = CategoryV2::create(array(
        //     'id' => 49,
        //     'industry' => '[1,2,3,4,5,6,7]',
        //     'name' => "Physical and chemical conditions",
        //     'description' => 'Physical and chemical conditions',
        //     'type' => 'risk-analysis',
        //     'added_by' => 1,
        //     'is_priority' => 0,
        //     'is_valid' => 1,
        // ));
        // $tmp63 = CategoryV2::create(array(
        //     'id' => 50,
        //     'industry' => '[1,2,3,4,5,6,7]',
        //     'name' => "Others",
        //     'description' => 'Others',
        //     'type' => 'risk-analysis',
        //     'added_by' => 1,
        //     'is_priority' => 1,
        //     'is_valid' => 1,
        // ));
    }
}
