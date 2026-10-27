<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\School;
use App\Models\Registration;
use App\Models\Registrant;
use App\Models\Competition;
use App\Models\Category;
use App\Models\TournamentMatch;
use App\Models\JuryScore;
use App\Models\WaLog;
use App\Models\User;

class ResetDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'semarak:reset-data 
                            {--scope=all : Lingkup pembersihan: pendaftar, sekolah, lomba, all, atau dummy-only}
                            {--seed : Jalankan seeder resmi kembali setelah pembersihan}
                            {--force : Jalankan langsung tanpa konfirmasi prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Membersihkan atau mereset data dummy pendaftar, database sekolah, dan cabang lomba secara aman.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $scope = strtolower($this->option('scope') ?: 'all');
        $force = $this->option('force');
        $withSeed = $this->option('seed');

        $this->info('======================================================================');
        $this->info('        SEMARAK ALIYAH XIV - PEMBERSIHAN / RESET DATA DUMMY          ');
        $this->info('======================================================================');
        $this->line("Lingkup Pembersihan: <comment>{$scope}</comment>");

        if (!$force) {
            $confirmed = $this->confirm("PERINGATAN: Tindakan ini akan menghapus data pada database sesuai scope '{$scope}'. Apakah Anda yakin ingin melanjutkan?", false);
            if (!$confirmed) {
                $this->warn('Pembersihan data dibatalkan.');
                return Command::SUCCESS;
            }
        }

        Schema::disableForeignKeyConstraints();

        try {
            switch ($scope) {
                case 'pendaftar':
                    $this->resetPendaftar();
                    break;

                case 'sekolah':
                    $this->resetSekolah();
                    break;

                case 'lomba':
                    $this->resetLomba();
                    break;

                case 'dummy-only':
                    $this->resetDummyOnly();
                    break;

                case 'all':
                    $this->resetPendaftar();
                    $this->resetLomba();
                    $this->resetSekolah();
                    break;

                default:
                    $this->error("Scope '{$scope}' tidak valid. Pilihan: pendaftar, sekolah, lomba, all, dummy-only.");
                    Schema::enableForeignKeyConstraints();
                    return Command::FAILURE;
            }

            if ($withSeed) {
                $this->newLine();
                $this->info('Memastikan Data Konten Tetap Siap (Galeri, FAQ, Sponsor)...');
                $this->call('db:seed', ['--class' => 'GallerySeeder']);
                $this->call('db:seed', ['--class' => 'FaqSeeder']);
                $this->call('db:seed', ['--class' => 'SponsorSeeder']);
                $this->info('Galeri, FAQ, dan Sponsor berhasil dimuat!');
            }

            $this->newLine();
            $this->info('✓ Proses pembersihan data berhasil diselesaikan dengan aman.');
        } catch (\Throwable $e) {
            $this->error('Terjadi kesalahan saat membersihkan data: ' . $e->getMessage());
            Schema::enableForeignKeyConstraints();
            return Command::FAILURE;
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        return Command::SUCCESS;
    }

    /**
     * Reset seluruh data pendaftar, akun peserta, match turnamen, dan skor.
     */
    protected function resetPendaftar(): void
    {
        $this->line('Membersihkan data pendaftar & turnamen...');

        $matchesCount = TournamentMatch::count();
        TournamentMatch::truncate();
        $this->line("  - {$matchesCount} data pertandingan turnamen dikosongkan.");

        $scoresCount = JuryScore::count();
        JuryScore::truncate();
        $this->line("  - {$scoresCount} data penilaian juri dikosongkan.");

        $regsCount = Registration::count();
        Registration::truncate();
        $this->line("  - {$regsCount} data registrasi delegasi lomba dikosongkan.");

        $registrantsCount = Registrant::count();
        Registrant::truncate();
        DB::table('registrant_password_reset_tokens')->truncate();
        $this->line("  - {$registrantsCount} data akun portal pendaftar dikosongkan.");

        $waCount = WaLog::count();
        WaLog::truncate();
        $this->line("  - {$waCount} riwayat log WhatsApp pendaftaran dikosongkan.");

        $activityCount = \App\Models\ActivityLog::count();
        \App\Models\ActivityLog::truncate();
        $this->line("  - {$activityCount} log aktivitas dikosongkan.");

        Competition::query()->update(['current_quota' => 0]);
        $this->line("  - Kuota terisi (current_quota) seluruh cabang lomba direset menjadi 0.");

        $this->info('✓ Data Pendaftar berhasil dikosongkan.');
    }

    /**
     * Reset database sekolah.
     */
    protected function resetSekolah(): void
    {
        $this->line('Membersihkan database sekolah...');
        $schoolCount = School::count();
        School::truncate();
        $this->line("  - {$schoolCount} data sekolah berhasil dikosongkan.");
        $this->info('✓ Database Sekolah berhasil dikosongkan.');
    }

    /**
     * Reset cabang lomba.
     */
    protected function resetLomba(): void
    {
        $this->line('Membersihkan data cabang lomba...');

        // Putuskan relasi user koordinator lomba jika ada
        User::whereNotNull('competition_id')->update(['competition_id' => null]);

        $compCount = Competition::count();
        Competition::truncate();
        $this->line("  - {$compCount} data cabang lomba dikosongkan.");

        $catCount = Category::count();
        Category::truncate();
        Category::ensureDefaultsExist();
        $this->line("  - {$catCount} kategori bidang lomba di-reset ke kategori default.");

        $this->info('✓ Data Cabang Lomba berhasil dikosongkan & kategori default dipulihkan.');
    }

    /**
     * Hapus hanya data yang terindikasi dummy / pengujian tanpa menghapus data asli.
     */
    protected function resetDummyOnly(): void
    {
        $this->line('Membersihkan data yang terindikasi dummy / testing...');

        // 1. Akun dummy testing
        $deletedRegistrants = Registrant::where(function ($q) {
            $q->where('email', 'like', '%test%')
              ->orWhere('email', 'like', '%example%')
              ->orWhere('email', 'like', '%dummy%')
              ->orWhere('phone', '6281277778888')
              ->orWhere('name', 'like', '%test%')
              ->orWhere('name', 'like', '%dummy%')
              ->orWhere('school_name', 'like', '%test%')
              ->orWhere('school_name', 'like', '%dummy%');
        })->delete();
        $this->line("  - {$deletedRegistrants} akun pendaftar dummy dihapus.");

        // 2. Data registrasi dummy
        $deletedRegs = Registration::where(function ($q) {
            $q->where('school_name', 'like', '%test%')
              ->orWhere('school_name', 'like', '%dummy%')
              ->orWhere('school_name', 'like', '%contoh%')
              ->orWhere('mentor_name', 'like', '%test%')
              ->orWhere('mentor_name', 'like', '%dummy%')
              ->orWhere('notes', 'like', '%dummy%');
        })->delete();
        $this->line("  - {$deletedRegs} data registrasi dummy dihapus.");

        // 3. Sekolah dummy
        $deletedSchools = School::where(function ($q) {
            $q->whereNull('name')
              ->orWhere('name', '')
              ->orWhere('name', 'like', '%test%')
              ->orWhere('name', 'like', '%contoh%')
              ->orWhere('name', 'like', '%dummy%');
        })->delete();
        $this->line("  - {$deletedSchools} data sekolah dummy dihapus.");

        $this->info('✓ Pembersihan data dummy spesifik selesai.');
    }
}
