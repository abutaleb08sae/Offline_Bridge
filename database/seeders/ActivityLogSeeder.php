<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ActivityLog;

class ActivityLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (ActivityLog::count() > 0) {
            return;
        }

        $logs = [
            [
                'client_ip'  => '192.168.1.101',
                'action'     => 'Client Connected',
                'details'    => 'Student Rahim Ahmed (2022-CSE-012) connected from LAB-PC-01',
                'status'     => 'info',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) PacketTracer-Client/1.0',
                'created_at' => now()->subMinutes(2),
            ],
            [
                'client_ip'  => '192.168.1.102',
                'action'     => 'Application Launched',
                'details'    => 'Fatima Yeasmin launched Cisco Packet Tracer v8.2 on LAB-PC-02',
                'status'     => 'info',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                'created_at' => now()->subMinutes(5),
            ],
            [
                'client_ip'  => '192.168.1.104',
                'action'     => 'Lab Task Uploaded',
                'details'    => 'Instructor uploaded lab_session_03.pkt (1.4 MB)',
                'status'     => 'success',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                'created_at' => now()->subMinutes(14),
            ],
            [
                'client_ip'  => '127.0.0.1',
                'action'     => 'Database Backup',
                'details'    => 'Master PC scheduled local database backup completed successfully',
                'status'     => 'success',
                'user_agent' => 'OfflineBridge-Master/Daemon',
                'created_at' => now()->subHours(1),
            ],
            [
                'client_ip'  => '192.168.1.103',
                'action'     => 'Solution Submitted',
                'details'    => 'Kamal Hasan (2022-CSE-028) submitted solution for Lab 02',
                'status'     => 'success',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                'created_at' => now()->subHours(2),
            ],
        ];

        foreach ($logs as $log) {
            ActivityLog::create($log);
        }
    }
}

