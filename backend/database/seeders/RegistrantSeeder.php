<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Registrant;
use Illuminate\Support\Facades\Hash;

class RegistrantSeeder extends Seeder
{
    /**
     * Run the database seeds according to DOKUMENTASI_AKUN_TESTING.md
     */
    public function run(): void
    {
        $accounts = [
            [
                'name' => 'Ahmad Fauzi',
                'email' => 'ahmad.fauzi@example.com',
                'phone' => '6281277778888',
                'password' => Hash::make('pendaftar123'),
                'registrant_role' => 'Pendamping',
                'school_name' => 'MTsN 1 Jember',
                'school_district' => 'Kaliwates',
                'gender' => 'Laki-laki',
                'is_active' => true,
            ],
            [
                'name' => 'Ust. Ahmad Fauzi, S.Pd.',
                'email' => 'pendaftar@semarak.id',
                'phone' => '6281234567899',
                'password' => Hash::make('pendaftar123'),
                'registrant_role' => 'Pendamping',
                'school_name' => 'SMP Negeri 1 Balung',
                'school_district' => 'Balung',
                'gender' => 'Laki-laki',
                'is_active' => true,
            ],
            [
                'name' => 'Ust. Budi Santoso',
                'email' => 'budi@santoso.com',
                'phone' => '6281234567891',
                'password' => Hash::make('pendaftar123'),
                'registrant_role' => 'Koordinator',
                'school_name' => 'SMP Negeri 1 Balung',
                'school_district' => 'Balung',
                'gender' => 'Laki-laki',
                'is_active' => true,
            ],
            [
                'name' => 'Ahmad Fauzi, S.Pd.',
                'email' => 'ahmad.fauzi@gmail.com',
                'phone' => '6281299997777',
                'password' => Hash::make('pendaftar123'),
                'registrant_role' => 'Pendamping',
                'school_name' => 'SMPN 1 Jember',
                'school_district' => 'Patrang',
                'gender' => 'Laki-laki',
                'is_active' => true,
            ],
        ];

        foreach ($accounts as $acc) {
            Registrant::updateOrCreate(
                ['email' => $acc['email']],
                $acc
            );
        }
    }
}
