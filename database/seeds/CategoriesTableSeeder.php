<?php

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategoriesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('categories') -> delete();
        // goal
        $tmp1 = Category::create(array(
            'id' => 1,
            'industry_id' => '1,2,3,4,5,6,7',
            'name' => "Fire and Emergency",
            'description' => 'Fire and Emergency',
            'type' => 'goal',
            'added_by' => 1,
            'is_primary' => 0,
        ));
        $tmp2 = Category::create(array(
            'id' => 2,
            'industry_id' => '1,2,3,4,5,6,7',
            'name' => "HSE-activities",
            'description' => 'HSE-activities',
            'type' => 'goal',
            'added_by' => 1,
            'is_primary' => 0,
        ));
        $tmp3 = Category::create(array(
            'id' => 3,
            'industry_id' => '1,2,3,4,5,6,7',
            'name' => "Environment",
            'description' => 'Environment',
            'type' => 'goal',
            'added_by' => 1,
            'is_primary' => 0,
        ));
        $tmp4 = Category::create(array(
            'id' => 4,
            'industry_id' => '1,2,3,4,5,6,7',
            'name' => "Psychosocial routines",
            'description' => 'Psychosocial routines',
            'type' => 'goal',
            'added_by' => 1,
            'is_primary' => 0,
        ));
        $tmp5 = Category::create(array(
            'id' => 5,
            'industry_id' => '1,2,3,4,5,6,7',
            'name' => "Physical and chemical conditions",
            'description' => 'Physical and chemical conditions',
            'type' => 'goal',
            'added_by' => 1,
            'is_primary' => 0,
        ));
        $tmp6 = Category::create(array(
            'id' => 6,
            'industry_id' => '1,2,3,4,5,6,7',
            'name' => "Others",
            'description' => 'Others',
            'type' => 'goal',
            'added_by' => 1,
            'is_primary' => 1,
        ));

        // LOGO
        $tmp7 = Category::create(array(
            'id' => 7,
            'industry_id' => '1,2,3,4,5,6,7',
            'name' => "Logo",
            'description' => 'Logo',
            'type' => 'attachment',
            'added_from' => 1, //company
            'added_by' => 1,
            'is_primary' => 0,
        ));
        // AVATAR
        $tmp8 = Category::create(array(
            'id' => 8,
            'industry_id' => '1,2,3,4,5,6,7',
            'name' => "Avatar",
            'description' => 'Avatar',
            'type' => 'attachment',
            'added_from' => 3, // employee
            'added_by' => 1,
            'is_primary' => 0,
        ));

        // routine
        $tmp9 = Category::create(array(
            'id' => 9,
            'industry_id' => '1,2,3,4,5,6,7',
            'name' => "Fire and Emergency",
            'description' => 'Fire and Emergency',
            'type' => 'routine',
            'added_by' => 1,
            'is_primary' => 0,
        ));
        $tmp10 = Category::create(array(
            'id' => 10,
            'industry_id' => '1,2,3,4,5,6,7',
            'name' => "HSE-activities",
            'description' => 'HSE-activities',
            'type' => 'routine',
            'added_by' => 1,
            'is_primary' => 0,
        ));
        $tmp11 = Category::create(array(
            'id' => 11,
            'industry_id' => '1,2,3,4,5,6,7',
            'name' => "Environment",
            'description' => 'Environment',
            'type' => 'routine',
            'added_by' => 1,
            'is_primary' => 0,
        ));
        $tmp12 = Category::create(array(
            'id' => 12,
            'industry_id' => '1,2,3,4,5,6,7',
            'name' => "Psychosocial routines",
            'description' => 'Psychosocial routines',
            'type' => 'routine',
            'added_by' => 1,
            'is_primary' => 0,
        ));
        $tmp13 = Category::create(array(
            'id' => 13,
            'industry_id' => '1,2,3,4,5,6,7',
            'name' => "Physical and chemical conditions",
            'description' => 'Physical and chemical conditions',
            'type' => 'routine',
            'added_by' => 1,
            'is_primary' => 0,
        ));
        $tmp14 = Category::create(array(
            'id' => 14,
            'industry_id' => '1,2,3,4,5,6,7',
            'name' => "Others",
            'description' => 'Others',
            'type' => 'routine',
            'added_by' => 1,
            'is_primary' => 1,
        ));

        // instruction
        $tmp15 = Category::create(array(
            'id' => 15,
            'industry_id' => '1,2,3,4,5,6,7',
            'name' => "Fire and Emergency",
            'description' => 'Fire and Emergency',
            'type' => 'instruction',
            'added_by' => 1,
            'is_primary' => 0,
        ));
        $tmp16 = Category::create(array(
            'id' => 16,
            'industry_id' => '1,2,3,4,5,6,7',
            'name' => "HSE-activities",
            'description' => 'HSE-activities',
            'type' => 'instruction',
            'added_by' => 1,
            'is_primary' => 0,
        ));
        $tmp17 = Category::create(array(
            'id' => 17,
            'industry_id' => '1,2,3,4,5,6,7',
            'name' => "Environment",
            'description' => 'Environment',
            'type' => 'instruction',
            'added_by' => 1,
            'is_primary' => 0,
        ));
        $tmp18 = Category::create(array(
            'id' => 18,
            'industry_id' => '1,2,3,4,5,6,7',
            'name' => "Psychosocial routines",
            'description' => 'Psychosocial routines',
            'type' => 'instruction',
            'added_by' => 1,
            'is_primary' => 0,
        ));
        $tmp19 = Category::create(array(
            'id' => 19,
            'industry_id' => '1,2,3,4,5,6,7',
            'name' => "Physical and chemical conditions",
            'description' => 'Physical and chemical conditions',
            'type' => 'instruction',
            'added_by' => 1,
            'is_primary' => 0,
        ));
        $tmp20 = Category::create(array(
            'id' => 20,
            'industry_id' => '1,2,3,4,5,6,7',
            'name' => "Others",
            'description' => 'Others',
            'type' => 'instruction',
            'added_by' => 1,
            'is_primary' => 1,
        ));

        // document
        $tmp21 = Category::create(array(
            'id' => 21,
            'industry_id' => '1,2,3,4,5,6,7',
            'name' => "Fire and Emergency",
            'description' => 'Fire and Emergency',
            'type' => 'document',
            'added_by' => 1,
            'is_primary' => 0,
        ));
        $tmp22 = Category::create(array(
            'id' => 22,
            'industry_id' => '1,2,3,4,5,6,7',
            'name' => "HSE-activities",
            'description' => 'HSE-activities',
            'type' => 'document',
            'added_by' => 1,
            'is_primary' => 0,
        ));
        $tmp23 = Category::create(array(
            'id' => 23,
            'industry_id' => '1,2,3,4,5,6,7',
            'name' => "Environment",
            'description' => 'Environment',
            'type' => 'document',
            'added_by' => 1,
            'is_primary' => 0,
        ));
        $tmp24 = Category::create(array(
            'id' => 24,
            'industry_id' => '1,2,3,4,5,6,7',
            'name' => "Psychosocial routines",
            'description' => 'Psychosocial routines',
            'type' => 'document',
            'added_by' => 1,
            'is_primary' => 0,
        ));
        $tmp25 = Category::create(array(
            'id' => 25,
            'industry_id' => '1,2,3,4,5,6,7',
            'name' => "Physical and chemical conditions",
            'description' => 'Physical and chemical conditions',
            'type' => 'document',
            'added_by' => 1,
            'is_primary' => 0,
        ));
        $tmp26 = Category::create(array(
            'id' => 26,
            'industry_id' => '1,2,3,4,5,6,7',
            'name' => "Others",
            'description' => 'Others',
            'type' => 'document',
            'added_by' => 1,
            'is_primary' => 1,
        ));

        // contact
        $tmp27 = Category::create(array(
            'id' => 27,
            'industry_id' => '1,2,3,4,5,6,7',
            'name' => "Fire and Emergency",
            'description' => 'Fire and Emergency',
            'type' => 'contact',
            'added_by' => 1,
            'is_primary' => 0,
        ));
        $tmp28 = Category::create(array(
            'id' => 28,
            'industry_id' => '1,2,3,4,5,6,7',
            'name' => "HSE-activities",
            'description' => 'HSE-activities',
            'type' => 'contact',
            'added_by' => 1,
            'is_primary' => 0,
        ));
        $tmp29 = Category::create(array(
            'id' => 29,
            'industry_id' => '1,2,3,4,5,6,7',
            'name' => "Environment",
            'description' => 'Environment',
            'type' => 'contact',
            'added_by' => 1,
            'is_primary' => 0,
        ));
        $tmp30 = Category::create(array(
            'id' => 30,
            'industry_id' => '1,2,3,4,5,6,7',
            'name' => "Psychosocial routines",
            'description' => 'Psychosocial routines',
            'type' => 'contact',
            'added_by' => 1,
            'is_primary' => 0,
        ));
        $tmp31 = Category::create(array(
            'id' => 31,
            'industry_id' => '1,2,3,4,5,6,7',
            'name' => "Physical and chemical conditions",
            'description' => 'Physical and chemical conditions',
            'type' => 'contact',
            'added_by' => 1,
            'is_primary' => 0,
        ));
        $tmp32 = Category::create(array(
            'id' => 32,
            'industry_id' => '1,2,3,4,5,6,7',
            'name' => "Others",
            'description' => 'Others',
            'type' => 'contact',
            'added_by' => 1,
            'is_primary' => 1,
        ));

        // checklist
        $tmp33 = Category::create(array(
            'id' => 33,
            'industry_id' => '1,2,3,4,5,6,7',
            'name' => "Fire and Emergency",
            'description' => 'Fire and Emergency',
            'type' => 'checklist',
            'added_by' => 1,
            'is_primary' => 0,
        ));
        $tmp34 = Category::create(array(
            'id' => 34,
            'industry_id' => '1,2,3,4,5,6,7',
            'name' => "HSE-activities",
            'description' => 'HSE-activities',
            'type' => 'checklist',
            'added_by' => 1,
            'is_primary' => 0,
        ));
        $tmp35 = Category::create(array(
            'id' => 35,
            'industry_id' => '1,2,3,4,5,6,7',
            'name' => "Environment",
            'description' => 'Environment',
            'type' => 'checklist',
            'added_by' => 1,
            'is_primary' => 0,
        ));
        $tmp36 = Category::create(array(
            'id' => 36,
            'industry_id' => '1,2,3,4,5,6,7',
            'name' => "Psychosocial routines",
            'description' => 'Psychosocial routines',
            'type' => 'checklist',
            'added_by' => 1,
            'is_primary' => 0,
        ));
        $tmp37 = Category::create(array(
            'id' => 37,
            'industry_id' => '1,2,3,4,5,6,7',
            'name' => "Physical and chemical conditions",
            'description' => 'Physical and chemical conditions',
            'type' => 'checklist',
            'added_by' => 1,
            'is_primary' => 0,
        ));
        $tmp38 = Category::create(array(
            'id' => 38,
            'industry_id' => '1,2,3,4,5,6,7',
            'name' => "Others",
            'description' => 'Others',
            'type' => 'checklist',
            'added_by' => 1,
            'is_primary' => 1,
        ));

        // deviation
        $tmp39 = Category::create(array(
            'id' => 39,
            'industry_id' => '1,2,3,4,5,6,7',
            'name' => "Fire and Emergency",
            'description' => 'Fire and Emergency',
            'type' => 'deviation',
            'added_by' => 1,
            'is_primary' => 0,
        ));
        $tmp40 = Category::create(array(
            'id' => 40,
            'industry_id' => '1,2,3,4,5,6,7',
            'name' => "HSE-activities",
            'description' => 'HSE-activities',
            'type' => 'deviation',
            'added_by' => 1,
            'is_primary' => 0,
        ));
        $tmp41 = Category::create(array(
            'id' => 41,
            'industry_id' => '1,2,3,4,5,6,7',
            'name' => "Environment",
            'description' => 'Environment',
            'type' => 'deviation',
            'added_by' => 1,
            'is_primary' => 0,
        ));
        $tmp42 = Category::create(array(
            'id' => 42,
            'industry_id' => '1,2,3,4,5,6,7',
            'name' => "Psychosocial routines",
            'description' => 'Psychosocial routines',
            'type' => 'deviation',
            'added_by' => 1,
            'is_primary' => 0,
        ));
        $tmp43 = Category::create(array(
            'id' => 43,
            'industry_id' => '1,2,3,4,5,6,7',
            'name' => "Physical and chemical conditions",
            'description' => 'Physical and chemical conditions',
            'type' => 'deviation',
            'added_by' => 1,
            'is_primary' => 0,
        ));
        $tmp44 = Category::create(array(
            'id' => 44,
            'industry_id' => '1,2,3,4,5,6,7',
            'name' => "Others",
            'description' => 'Others',
            'type' => 'deviation',
            'added_by' => 1,
            'is_primary' => 1,
        ));

        // risk
        $tmp45 = Category::create(array(
            'id' => 45,
            'industry_id' => '1,2,3,4,5,6,7',
            'name' => "Fire and Emergency",
            'description' => 'Fire and Emergency',
            'type' => 'risk',
            'added_by' => 1,
            'is_primary' => 0,
        ));
        $tmp46 = Category::create(array(
            'id' => 46,
            'industry_id' => '1,2,3,4,5,6,7',
            'name' => "HSE-activities",
            'description' => 'HSE-activities',
            'type' => 'risk',
            'added_by' => 1,
            'is_primary' => 0,
        ));
        $tmp47 = Category::create(array(
            'id' => 47,
            'industry_id' => '1,2,3,4,5,6,7',
            'name' => "Environment",
            'description' => 'Environment',
            'type' => 'risk',
            'added_by' => 1,
            'is_primary' => 0,
        ));
        $tmp48 = Category::create(array(
            'id' => 48,
            'industry_id' => '1,2,3,4,5,6,7',
            'name' => "Psychosocial routines",
            'description' => 'Psychosocial routines',
            'type' => 'risk',
            'added_by' => 1,
            'is_primary' => 0,
        ));
        $tmp49 = Category::create(array(
            'id' => 49,
            'industry_id' => '1,2,3,4,5,6,7',
            'name' => "Physical and chemical conditions",
            'description' => 'Physical and chemical conditions',
            'type' => 'risk',
            'added_by' => 1,
            'is_primary' => 0,
        ));
        $tmp50 = Category::create(array(
            'id' => 50,
            'industry_id' => '1,2,3,4,5,6,7',
            'name' => "Others",
            'description' => 'Others',
            'type' => 'risk',
            'added_by' => 1,
            'is_primary' => 1,
        ));

        // task
        $tmp51 = Category::create(array(
            'id' => 51,
            'industry_id' => '1,2,3,4,5,6,7',
            'name' => "Fire and Emergency",
            'description' => 'Fire and Emergency',
            'type' => 'task',
            'added_by' => 1,
            'is_primary' => 0,
        ));
        $tmp52 = Category::create(array(
            'id' => 52,
            'industry_id' => '1,2,3,4,5,6,7',
            'name' => "HSE-activities",
            'description' => 'HSE-activities',
            'type' => 'task',
            'added_by' => 1,
            'is_primary' => 0,
        ));
        $tmp53 = Category::create(array(
            'id' => 53,
            'industry_id' => '1,2,3,4,5,6,7',
            'name' => "Environment",
            'description' => 'Environment',
            'type' => 'task',
            'added_by' => 1,
            'is_primary' => 0,
        ));
        $tmp54 = Category::create(array(
            'id' => 54,
            'industry_id' => '1,2,3,4,5,6,7',
            'name' => "Psychosocial routines",
            'description' => 'Psychosocial routines',
            'type' => 'task',
            'added_by' => 1,
            'is_primary' => 0,
        ));
        $tmp55 = Category::create(array(
            'id' => 55,
            'industry_id' => '1,2,3,4,5,6,7',
            'name' => "Physical and chemical conditions",
            'description' => 'Physical and chemical conditions',
            'type' => 'task',
            'added_by' => 1,
            'is_primary' => 0,
        ));
        $tmp56 = Category::create(array(
            'id' => 56,
            'industry_id' => '1,2,3,4,5,6,7',
            'name' => "Others",
            'description' => 'Others',
            'type' => 'task',
            'added_by' => 1,
            'is_primary' => 1,
        ));

        // attachment
        $tmp57 = Category::create(array(
            'id' => 57,
            'industry_id' => '1,2,3,4,5,6,7',
            'name' => "Others",
            'description' => 'Others',
            'type' => 'attachment',
            'added_from' => 1, //company
            'added_by' => 1,
            'is_primary' => 1,
        ));
    }
}
