<?php

namespace App\Http\Controllers;

use Biosync\Biosync;

class AttendanceController extends Controller
{
    protected $ip;

    protected $device;

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
        $attendance = \DB::table('bio_metric_data')->paginate();
        // $attendance = \DB::table('bio_metric_data')->orderBy('id', 'desc')->first();
        return $attendance;
    }

    public function store($ip, $sn=1)
    {
        $this->ip = $ip;
        info('===============================================');
        info('started');
        $now = \Carbon\Carbon::now()->toDateTimeString();
        $last_entry = \DB::table('bio_metric_data')->where('sn',$sn)->orderBy('id', 'desc')->first();
        $last_entry_date=\Carbon\Carbon::today();
        if($last_entry) {
            $last_entry_date = $last_entry->checktime;
        }
        $last_entry_date = \Carbon\Carbon::parse($last_entry_date);
        info('last_entry_date: ' . $last_entry_date->toDateTimeString());
        // dd($last_entry_date);
        $attendance = $this->getAttendance();
        // print_r($attendance);die;
        // $users_chunk = array_chunk($attendance,5000);
        info('count($attendance): ' . count($attendance));
        // info('count($users_chunk): ' . count($users_chunk));
        
        // foreach ($users_chunk as $users) {
            info('inserting count($attendance): ' . count($attendance));
            $insert_users = [];
            foreach ($attendance as $user) {
                $user_entry_date = \Carbon\Carbon::parse($user[3]);
                // dd($user_entry_date);
                if( $user_entry_date > $last_entry_date) {
                    // dd($user_entry_date);
                    $insert_users[] = [
                        'entry_id' => null,
                        'type' => 'FP',
                        'sn' => $sn,
                        'badgeno' => $user[0],
                        'updateddate' => $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                        'checktime' => $user[3],
                    ];
                }
            }
            // dd($insert_users);
            \DB::table('bio_metric_data')->insert($insert_users);
            info('inserted count(attendance): ' . count($attendance));
            info('inserted count($insert_users): ' . count($insert_users));
        // }
        info('ended');
        return ['success'];
    }

    public function syncGroundFloor()
    {
        $ip = env('DEVICE_IP2');
        $this->store($ip,1);
    }

    public function test()
    {
        $ip = env('DEVICE_IP2');
        $this->store($ip,1);
        // dd('ok');
        /*info('starting test');
        $this->ip = env('DEVICE_IP2');
        $attendance = $this->getAttendance();
        info('ending test');
        dd(count($attendance));*/
    }

    public function excel()
    {
        # code...
    }

    public function syncFirstFloor()
    {
        $ip = env('DEVICE_IP1');
        $this->store($ip,2);
    }

    public function connect()
    {
        $this->device = new Biosync($this->ip, 4370);
        $ret = $this->device->connect();
        info('connected to device ' . $this->ip);
        $this->device->disableDevice();
        info('disabled device ' . $this->ip);
    }

    public function disconnect()
    {
        $this->device->enableDevice();
        info('activating device ' . $this->ip);
        $this->device->disconnect();
        info('disconnected from device ' . $this->ip);
    }

    public function getAttendance()
    {
        $this->connect();
        info('getting attendance ' . $this->ip);
        $attendance = $this->device->getAttendance();
        info('received attendance from ' . $this->ip);
        $this->disconnect();
        return $attendance;
    }
    //
}
