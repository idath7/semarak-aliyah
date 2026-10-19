<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Settings Table for global configurable site texts
        if (!Schema::hasTable('settings')) {
            Schema::create('settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique()->index();
                $table->longText('value')->nullable();
                $table->timestamps();
            });
        }

        // 2. Sponsors Table
        if (!Schema::hasTable('sponsors')) {
            Schema::create('sponsors', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('short_name')->nullable();
                $table->string('tier')->nullable();
                $table->string('category')->nullable();
                $table->string('logo')->nullable();
                $table->boolean('only_logo')->default(false);
                $table->string('icon')->default('ri-shake-hands-line');
                $table->string('color')->default('#6366f1');
                $table->string('bg_color')->default('rgba(99, 102, 241, 0.1)');
                $table->string('border_color')->default('rgba(99, 102, 241, 0.3)');
                $table->string('website_url')->nullable();
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 3. Seed Default Sponsor Section Settings
        $defaultSettings = [
            'sponsor_section_tag' => 'Mitra & Kolaborasi',
            'sponsor_section_title' => 'Didukung Oleh Mitra & Sponsor Terpercaya',
            'sponsor_section_desc' => 'Apresiasi dan terima kasih kepada institusi perbankan syariah, penyedia telekomunikasi, media massa, serta mitra strategis yang turut bersinergi menyukseskan Semarak Aliyah XII 2026.',
            'sponsor_cta_text' => 'Peluang Sponsorship: Bergabunglah sebagai mitra resmi dan promosikan brand Anda kepada ribuan pelajar, santri & pendidik se-Kabupaten Jember.',
            'sponsor_cta_wa' => '6283850070767',
            'sponsor_cta_btn_text' => 'Ajukan Kerja Sama Sponsor',
            'sponsor_is_active' => '1',
        ];

        foreach ($defaultSettings as $key => $val) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $val, 'created_at' => now(), 'updated_at' => now()]
            );
        }

        // 4. Seed Initial 12 Sponsors if empty
        if (DB::table('sponsors')->count() === 0) {
            $initialSponsors = [
                [
                    'name' => 'Bank Syariah Indonesia',
                    'short_name' => 'BSI',
                    'tier' => 'Official Bank',
                    'category' => 'Mitra Perbankan Utama',
                    'logo' => null,
                    'only_logo' => false,
                    'icon' => 'ri-bank-line',
                    'color' => '#00a39d',
                    'bg_color' => 'rgba(0, 163, 157, 0.1)',
                    'border_color' => 'rgba(0, 163, 157, 0.3)',
                    'website_url' => 'https://www.bankbsi.co.id',
                    'sort_order' => 1,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Bank Jatim Syariah',
                    'short_name' => 'Bank Jatim',
                    'tier' => 'Regional Partner',
                    'category' => 'Mitra Finansial Daerah',
                    'logo' => null,
                    'only_logo' => false,
                    'icon' => 'ri-safe-2-line',
                    'color' => '#e11d48',
                    'bg_color' => 'rgba(225, 29, 72, 0.1)',
                    'border_color' => 'rgba(225, 29, 72, 0.3)',
                    'website_url' => 'https://www.bankjatim.co.id',
                    'sort_order' => 2,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Telkomsel & By.U',
                    'short_name' => 'Telkomsel',
                    'tier' => 'Telco & Digital',
                    'category' => 'Official Connectivity',
                    'logo' => null,
                    'only_logo' => false,
                    'icon' => 'ri-signal-tower-line',
                    'color' => '#ef4444',
                    'bg_color' => 'rgba(239, 68, 68, 0.1)',
                    'border_color' => 'rgba(239, 68, 68, 0.3)',
                    'website_url' => 'https://www.telkomsel.com',
                    'sort_order' => 3,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Cleo Pure Water',
                    'short_name' => 'Cleo',
                    'tier' => 'Hydration Partner',
                    'category' => 'Official Mineral Water',
                    'logo' => null,
                    'only_logo' => false,
                    'icon' => 'ri-drop-line',
                    'color' => '#0ea5e9',
                    'bg_color' => 'rgba(14, 165, 233, 0.1)',
                    'border_color' => 'rgba(14, 165, 233, 0.3)',
                    'website_url' => 'https://cleopurewater.com',
                    'sort_order' => 4,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Indofood Sukses Makmur',
                    'short_name' => 'Indofood',
                    'tier' => 'Official Partner',
                    'category' => 'Konsumsi & Nutrisi Peserta',
                    'logo' => null,
                    'only_logo' => false,
                    'icon' => 'ri-restaurant-line',
                    'color' => '#f59e0b',
                    'bg_color' => 'rgba(245, 158, 11, 0.1)',
                    'border_color' => 'rgba(245, 158, 11, 0.3)',
                    'website_url' => 'https://www.indofood.com',
                    'sort_order' => 5,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'ESI (E-Sport Indonesia) Jember',
                    'short_name' => 'ESI Jember',
                    'tier' => 'Esports Arbiter',
                    'category' => 'Wasit & Teknis MLBB',
                    'logo' => null,
                    'only_logo' => false,
                    'icon' => 'ri-gamepad-line',
                    'color' => '#8b5cf6',
                    'bg_color' => 'rgba(139, 92, 246, 0.1)',
                    'border_color' => 'rgba(139, 92, 246, 0.3)',
                    'website_url' => null,
                    'sort_order' => 6,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Kemenag Kabupaten Jember',
                    'short_name' => 'Kemenag',
                    'tier' => 'Instansi Pembina',
                    'category' => 'Dukungan Kelembagaan',
                    'logo' => null,
                    'only_logo' => false,
                    'icon' => 'ri-government-line',
                    'color' => '#10b981',
                    'bg_color' => 'rgba(16, 185, 129, 0.1)',
                    'border_color' => 'rgba(16, 185, 129, 0.3)',
                    'website_url' => 'https://jember.kemenag.go.id',
                    'sort_order' => 7,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Percetakan Balung Mandiri',
                    'short_name' => 'PBM Print',
                    'tier' => 'Merchandise Partner',
                    'category' => 'Percetakan & Atribut Lomba',
                    'logo' => null,
                    'only_logo' => false,
                    'icon' => 'ri-printer-line',
                    'color' => '#ec4899',
                    'bg_color' => 'rgba(236, 72, 153, 0.1)',
                    'border_color' => 'rgba(236, 72, 153, 0.3)',
                    'website_url' => null,
                    'sort_order' => 8,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Radar Jember Media',
                    'short_name' => 'Radar Jember',
                    'tier' => 'Media Partner',
                    'category' => 'Publikasi & Pemberitaan',
                    'logo' => null,
                    'only_logo' => false,
                    'icon' => 'ri-newspaper-line',
                    'color' => '#6366f1',
                    'bg_color' => 'rgba(99, 102, 241, 0.1)',
                    'border_color' => 'rgba(99, 102, 241, 0.3)',
                    'website_url' => 'https://radarjember.jawapos.com',
                    'sort_order' => 9,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Kahf Everyday Grooming',
                    'short_name' => 'Kahf',
                    'tier' => 'Personal Care',
                    'category' => 'Official Hygiene Partner',
                    'logo' => null,
                    'only_logo' => false,
                    'icon' => 'ri-sparkling-line',
                    'color' => '#14b8a6',
                    'bg_color' => 'rgba(20, 184, 166, 0.1)',
                    'border_color' => 'rgba(20, 184, 166, 0.3)',
                    'website_url' => 'https://www.kahfeveryday.com',
                    'sort_order' => 10,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Wardah Cosmetics',
                    'short_name' => 'Wardah',
                    'tier' => 'Beauty Partner',
                    'category' => 'Sponsor Penunjang Seni',
                    'logo' => null,
                    'only_logo' => false,
                    'icon' => 'ri-magic-line',
                    'color' => '#f43f5e',
                    'bg_color' => 'rgba(244, 63, 94, 0.1)',
                    'border_color' => 'rgba(244, 63, 94, 0.3)',
                    'website_url' => 'https://www.wardahbeauty.com',
                    'sort_order' => 11,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'YPIA Wahid Hasyim Balung',
                    'short_name' => 'YPIA WH',
                    'tier' => 'Grand Patron',
                    'category' => 'Yayasan Penyelenggara',
                    'logo' => null,
                    'only_logo' => false,
                    'icon' => 'ri-building-line',
                    'color' => '#059669',
                    'bg_color' => 'rgba(5, 150, 105, 0.1)',
                    'border_color' => 'rgba(5, 150, 105, 0.3)',
                    'website_url' => 'https://maswahas.sch.id',
                    'sort_order' => 12,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ];

            DB::table('sponsors')->insert($initialSponsors);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sponsors');
        Schema::dropIfExists('settings');
    }
};
