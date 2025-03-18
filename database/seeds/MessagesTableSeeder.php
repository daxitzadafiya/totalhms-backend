<?php

use Illuminate\Database\Seeder;
use App\Models\Message;

class MessagesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('messages')->delete();
        $tmp1 = Message::create(array(
            'id' => 1,
            'content' => '',
        ));
        $tmp2 = Message::create(array(
            'id' => 2,
            'content' => 'Test push notification when assigned tasks',
        ));
        $tmp3 = Message::create(array(
            'id' => 3,
            'content' => 'Test push notification when super admin change default setup',
        ));
        $tmp4 = Message::create(array(
            'id' => 4,
            'content' => 'Test push notification when assigned absence',
        ));
    }
}
