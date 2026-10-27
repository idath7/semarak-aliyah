<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            CategorySeeder::class,
            GallerySeeder::class,
            FaqSeeder::class,
            SponsorSeeder::class,
            RegistrantSeeder::class,
            // CompetitionSeeder::class, // Dinonaktifkan: Cabang lomba dibuat manual dari nol oleh panitia
            // FormFieldSeeder::class,
            // SchoolSeeder::class,      // Dinonaktifkan: Database sekolah diisi manual / impor oleh panitia
            // TournamentSeeder::class,  // Dinonaktifkan: Simulasi turnamen
        ]);
    }
}
