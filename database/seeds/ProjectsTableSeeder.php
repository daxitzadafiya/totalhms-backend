<?php

use Illuminate\Database\Seeder;
use App\Models\Project;

class ProjectsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('projects') -> delete();
        $tmp1 = Project::create(array(
            'name' => "Project 1",
            'description' => 'Project 1',
            'company_id' => 1
        ));
        $tmp2 = Project::create(array(
            'name' => "Project 2",
            'description' => 'Project 2',
            'company_id' => 1
        ));
        $tmp3 = Project::create(array(
            'name' => "Project 3",
            'description' => 'Project 3',
            'company_id' => 1
        ));
    }
}
