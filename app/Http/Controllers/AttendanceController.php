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
        $this->ip = env('DEVICE_IP1');
    }

    public function index()
    {
        $attendance = \DB::table('bio_metric_data')->paginate();
        // $attendance = \DB::table('bio_metric_data')->orderBy('id', 'desc')->first();
        return $attendance;
    }

    public function store()
    {
        info('===============================================');
        info('started');
        $now = \Carbon\Carbon::now()->toDateTimeString();
        $attendance = $this->getAttendance();
        $users_chunk = array_chunk($attendance,5000);
        info('count($attendance): ' . count($attendance));
        info('count($users_chunk): ' . count($users_chunk));
        
        foreach ($users_chunk as $users) {
            info('inserting count($users): ' . count($users));
            $insert_users = [];
            foreach ($users as $user) {
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
        info('ended');
        return ['success'];
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
