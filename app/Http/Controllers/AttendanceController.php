<?php

namespace App\Http\Controllers;

use Biosync\Biosync;

class AttendanceController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function index()
    {
        $attendance = \DB::select('select * from bio_metric_data');
        return $attendance;
    }

    public function store()
    {
        info('started');
        $now = \Carbon\Carbon::now()->toDateTimeString();
        // dd($now);
        $zk = new Biosync("192.168.1.7", 4370);

        $ret = $zk->connect();
        info('connected to device');
        $zk->disableDevice();
        info('disabled device');

        info('getting attendance');
        $attendance = $zk->getAttendance();
        info('received attendance');

        $zk->enableDevice();
        info('activating device');
        $zk->disconnect();
        info('disconnected from device');

        $users_chunk = array_chunk($attendance,5000);
        info('count($attendance): ' . count($attendance));
        info('count($users_chunk): ' . count($users_chunk));
        
        foreach ($users_chunk as $users) {
            info('inserting count($users): ' . count($users));
            $insert_users = [];
            foreach ($users as $user) {
                // info('count($user): ' . count($user));
                // print_r($user);die;
                $insert_users[] = [
                    'entry_id' => null,
                    'type' => 'FP',
                    'badgeno' => $user[0],
                    'updateddate' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'checktime' => $user[3],
                ];
            }
            \DB::table('bio_metric_data')->insert($insert_users);
            info('inserted count($user): ' . count($users));
        }
        // var_dump($users);
        info('ended');
        return ['success'];
    }

    //
}
