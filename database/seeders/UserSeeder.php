<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) env('ADMIN_EMAIL', 'admin@gmail.com');
        $password = env('ADMIN_PASSWORD');

        if (!is_string($password) || strlen($password) < 12) {
            throw new RuntimeException('Set ADMIN_PASSWORD to a unique password of at least 12 characters before seeding.');
        }

        // 1. Master Administrator
        $admin = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Administrator',
                'password' => Hash::make($password),
                'role' => 'admin',
                'status' => 'active',
            ]
        );

        if (!$admin->isAdmin()) {
            $admin->forceFill(['role' => 'admin', 'status' => 'active'])->save();
        }

        // 2. Lab Instructor
        User::firstOrCreate(
            ['email' => 'instructor@offlinebridge.local'],
            [
                'name' => 'Dr. Kamrul Hasan',
                'password' => Hash::make($password),
                'role' => 'instructor',
                'status' => 'active',
            ]
        );

        // 3. Lab Students
        $students = [
            [
                'name' => 'Rahim Ahmed (2022-CSE-012)',
                'email' => 'rahim.012@student.cse.edu',
            ],
            [
                'name' => 'Fatima Yeasmin (2022-CSE-015)',
                'email' => 'fatima.015@student.cse.edu',
            ],
            [
                'name' => 'Tanvir Hossain (2022-CSE-028)',
                'email' => 'tanvir.028@student.cse.edu',
            ],
            [
                'name' => 'Anika Tabassum (2022-CSE-033)',
                'email' => 'anika.033@student.cse.edu',
            ],
        ];

        foreach ($students as $stu) {
            User::firstOrCreate(
                ['email' => $stu['email']],
                [
                    'name' => $stu['name'],
                    'password' => Hash::make($password),
                    'role' => 'student',
                    'status' => 'active',
                ]
            );
        }
    }
}
