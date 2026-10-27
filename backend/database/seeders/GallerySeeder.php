<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Gallery;

class GallerySeeder extends Seeder
{
    public function run(): void
    {
        $galleries = [
            [
                'id' => 1,
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
            ],
            [
                'id' => 2,
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
            ],
            [
                'id' => 3,
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
            ],
            [
                'id' => 4,
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
            ],
            [
                'id' => 5,
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
            ],
            [
                'id' => 6,
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
            ],
            [
                'id' => 7,
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
            ],
            [
                'id' => 8,
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
            ],
        ];

        foreach ($galleries as $item) {
            Gallery::updateOrCreate(['id' => $item['id']], $item);
        }
    }
}
