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
        // 1. Galleries Table
        if (!Schema::hasTable('galleries')) {
            Schema::create('galleries', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('category_label')->nullable();
                $table->string('pill_class')->default('pill-gold');
                $table->string('icon_class')->default('ri-image-line');
                $table->string('image_path');
                $table->string('location')->nullable();
                $table->string('tag')->nullable();
                $table->text('description')->nullable();
                $table->unsignedTinyInteger('row_position')->default(1)->comment('1: Baris Atas (Geser Kiri), 2: Baris Bawah (Geser Kanan)');
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 2. Seed Default Gallery Section Settings
        $defaultSettings = [
            'gallery_section_tag' => 'Galeri Dokumentasi',
            'gallery_section_title' => 'Momen Spektakuler & Kilas Balik',
            'gallery_section_desc' => 'Saksikan sorak kemeriahan, kobaran semangat sportivitas talenta muda, dan kilas balik keseruan panggung Semarak Aliyah.',
            'gallery_is_active' => '1',
        ];

        foreach ($defaultSettings as $key => $val) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $val, 'created_at' => now(), 'updated_at' => now()]
            );
        }

        // 3. Seed Initial 8 Gallery Photos if empty
        if (DB::table('galleries')->count() === 0) {
            $initialGalleries = [
                // BARIS 1 (Atas - Geser Kiri)
                [
                    'title' => 'Selebrasi Penganugerahan Juara Umum Bergilir',
                    'category_label' => 'Juara & Awarding',
                    'pill_class' => 'pill-gold',
                    'icon_class' => 'ri-trophy-fill',
                    'image_path' => '/assets/img/gallery/gallery_awarding.jpg',
                    'location' => 'Panggung Utama Semarak Aliyah',
                    'tag' => 'Grand Awarding Ceremony',
                    'description' => 'Momen puncak penyerahan piala bergilir kehormatan Kementerian Agama dan uang pembinaan kepada kontingen madrasah berprestasi.',
                    'row_position' => 1,
                    'sort_order' => 1,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'title' => 'Arena Grand Final Mobile Legends Championship',
                    'category_label' => 'E-Sport Arena',
                    'pill_class' => 'pill-purple',
                    'icon_class' => 'ri-gamepad-fill',
                    'image_path' => '/assets/img/gallery/gallery_esport.jpg',
                    'location' => 'E-Sport Center Maswahas',
                    'tag' => 'Mobile Legends Bang Bang',
                    'description' => 'Duel sengit perebutan tahta Land of Dawn antartim SMP/MTs terbaik se-Kabupaten Jember dengan tata panggung turnamen berstandar esports.',
                    'row_position' => 1,
                    'sort_order' => 2,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'title' => 'Konsentrasi Tinggi Babak Final Olimpiade Sains',
                    'category_label' => 'Olimpiade Sains',
                    'pill_class' => 'pill-blue',
                    'icon_class' => 'ri-flask-fill',
                    'image_path' => '/assets/img/gallery/gallery_olimpiade.jpg',
                    'location' => 'Auditorium Sains Terpadu',
                    'tag' => 'Olimpiade SMP/MTs',
                    'description' => 'Ratusan saintis belia adu ketelitian dan kecerdasan dalam menyelesaikan studi kasus sains, matematika terapan, dan logika analitis.',
                    'row_position' => 1,
                    'sort_order' => 3,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'title' => 'Penghayatan Pentas Storytelling & Pidato Bahasa',
                    'category_label' => 'Bahasa & Sastra',
                    'pill_class' => 'pill-pink',
                    'icon_class' => 'ri-mic-fill',
                    'image_path' => '/assets/img/gallery/gallery_storytelling.jpg',
                    'location' => 'Stage Kreasi Bahasa',
                    'tag' => 'Storytelling & Speech',
                    'description' => 'Penampilan memukau bertutur cerita bahasa Inggris dan pidato bahasa Arab penuh ekspresi, artikulasi tajam, dan kostum teatrikal tematik.',
                    'row_position' => 1,
                    'sort_order' => 4,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],

                // BARIS 2 (Bawah - Geser Kanan)
                [
                    'title' => 'Gema Shalawat Festival Al-Banjari Se-Jawa Timur',
                    'category_label' => 'Seni Islami & Budaya',
                    'pill_class' => 'pill-green',
                    'icon_class' => 'ri-music-2-fill',
                    'image_path' => '/assets/img/gallery/gallery_albanjari.jpg',
                    'location' => 'Panggung Terbuka Plaza',
                    'tag' => 'Festival Banjari Jatim',
                    'description' => 'Harmoni tabuhan terbang dan lantunan syair shalawat merdu menyatukan syiar Islam dan keindahan kesenian tradisi nusantara.',
                    'row_position' => 2,
                    'sort_order' => 5,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'title' => 'Smash Mematikan di Final Bola Voli Putri',
                    'category_label' => 'Sportivitas Voli',
                    'pill_class' => 'pill-orange',
                    'icon_class' => 'ri-football-fill',
                    'image_path' => '/assets/img/gallery/gallery_volleyball.jpg',
                    'location' => 'Lapangan Utama Maswahas',
                    'tag' => 'Turnamen Voli Pelajar',
                    'description' => 'Aksi heroik penyelamatan bola dan lonjakan spike tajam memukau ratusan pasang mata penonton di pinggir lapangan.',
                    'row_position' => 2,
                    'sort_order' => 6,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'title' => 'Vokal Emas Festival Solo Singer Lagu Islami',
                    'category_label' => 'Seni Vokal Islami',
                    'pill_class' => 'pill-purple',
                    'icon_class' => 'ri-voiceprint-fill',
                    'image_path' => '/assets/img/gallery/gallery_singer.jpg',
                    'location' => 'Teater Tertutup Maswahas',
                    'tag' => 'Solo Song Competition',
                    'description' => 'Penjiwaan mendalam dan teknik olah vokal spektakuler peserta solo singer dalam melantunkan tembang religi kontemporer.',
                    'row_position' => 2,
                    'sort_order' => 7,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'title' => 'Gemuruh Sorak & Yel-Yel Suporter Madrasah',
                    'category_label' => 'Suporter & Fairplay',
                    'pill_class' => 'pill-gold',
                    'icon_class' => 'ri-flag-fill',
                    'image_path' => '/assets/img/gallery/gallery_supporters.jpg',
                    'location' => 'Tribun Gelanggang Olahraga',
                    'tag' => 'Supporter of the Year',
                    'description' => 'Semangat kebersamaan dan yel-yel kreatif suporter madrasah yang terus bergelora memberikan energi tanpa henti bagi para atlet.',
                    'row_position' => 2,
                    'sort_order' => 8,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ];

            DB::table('galleries')->insert($initialGalleries);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('galleries');
    }
};
