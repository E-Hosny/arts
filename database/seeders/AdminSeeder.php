<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user if not exists
        $adminEmail = 'admin@example.com';
        
        if (!User::where('email', $adminEmail)->exists()) {
            User::create([
                'name' => 'مدير النظام',
                'email' => $adminEmail,
                'password' => Hash::make('password123'),
                'role' => User::ROLE_ADMIN,
                'email_verified' => true,
                'avatar_url' => null,
            ]);

            $this->command->info('تم إنشاء حساب الأدمن التجريبي:');
            $this->command->info('البريد الإلكتروني: admin@example.com');
            $this->command->info('كلمة المرور: password123');
        } else {
            $this->command->info('حساب الأدمن موجود بالفعل');
        }
    }
}