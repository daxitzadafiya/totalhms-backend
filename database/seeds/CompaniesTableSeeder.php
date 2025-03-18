<?php

use Illuminate\Database\Seeder;
use App\Models\Company;

class CompaniesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('companies') -> delete();
        $tmp1 = Company::create(array(
            'id'=> 1,
            'name' => "Total HMS",
            'phone_number' => "123 45 678",
            'vat_number' => "111 222 333",
            'industry_id' => 2,
            'address'=> "123 Wall Street",
            'city'=> "New York",
            'zip_code'=> '1000',
            'language'=> 'no',
        ));
    }
}
