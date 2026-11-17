<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Application;

class ApplicationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Application::updateOrCreate(
            ['name' => 'Cisco Packet Tracer'],
            [
                'version' => 'v8.2.1',
                'required_port' => 'TCP 38000',
                'cached_login_support' => true,
                'offline_status' => 'Verified (0% Packet Loss)',
                'notes' => 'Multiuser peering successfully verified over offline LAN subnet without WAN.',
            ]
        );

        Application::updateOrCreate(
            ['name' => 'Wireshark Packet Sniffer'],
            [
                'version' => 'v4.0.6',
                'required_port' => 'Npcap / Promiscuous',
                'cached_login_support' => true,
                'offline_status' => 'Supported (Fully Offline)',
                'notes' => 'Local network protocol analysis and packet capture enabled on LAN interfaces.',
            ]
        );

        Application::updateOrCreate(
            ['name' => 'Offline Programming Judge'],
            [
                'version' => 'v1.2.0',
                'required_port' => 'Local Runner / CLI',
                'cached_login_support' => true,
                'offline_status' => 'Supported (Fully Offline)',
                'notes' => 'Automated C++17 and Python 3 programming assessment with isolated testcase evaluation.',
            ]
        );
    }
}
