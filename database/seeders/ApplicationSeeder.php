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
                'version' => '8.x',
                'required_port' => 'TCP 38000',
                'cached_login_support' => true,
                'offline_status' => 'Verified (0% Packet Loss)',
                'notes' => 'Multiuser peering successfully verified over offline LAN subnet.',
            ]
        );
    }
}

