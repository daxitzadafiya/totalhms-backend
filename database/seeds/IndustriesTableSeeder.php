<?php

use Illuminate\Database\Seeder;
use App\Models\Industry;

class IndustriesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('industries') -> delete();
        $tmp1 = Industry::create(array(
            'id' => 1,
            'name' => "Retail"
        ));
        $tmp2 = Industry::create(array(
            'id' => 2,
            'name' => "Office"
        ));
        $tmp3 = Industry::create(array(
            'id' => 3,
            'name' => "Construction"
        ));
        $tmp4 = Industry::create(array(
            'id' => 4,
            'name' => "Restaurant and hotel"
        ));
        $tmp5 = Industry::create(array(
            'id' => 5,
            'name' => "Hairdresser"
        ));
        $tmp6 = Industry::create(array(
            'id' => 6,
            'name' => "Health"
        ));
        $tmp7 = Industry::create(array(
            'id' => 7,
            'name' => "Other"
        ));
    }
}
