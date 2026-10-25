<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'registrant_role')) {
                $table->string('registrant_role', 50)->nullable()->after('role'); // pendamping, koordinator, siswa_peserta
            }
            if (!Schema::hasColumn('users', 'gender')) {
                $table->string('gender', 20)->nullable()->after('registrant_role'); // Laki-laki / Perempuan
            }
            if (!Schema::hasColumn('users', 'school_name')) {
                $table->string('school_name')->nullable()->after('gender');
            }
            if (!Schema::hasColumn('users', 'school_district')) {
                $table->string('school_district')->nullable()->after('school_name');
            }
            if (!Schema::hasColumn('users', 'google_id')) {
                $table->string('google_id')->nullable()->after('school_district');
            }
            if (!Schema::hasColumn('users', 'wa_otp_code')) {
                $table->string('wa_otp_code', 10)->nullable()->after('google_id');
            }
            if (!Schema::hasColumn('users', 'wa_otp_expires_at')) {
                $table->timestamp('wa_otp_expires_at')->nullable()->after('wa_otp_code');
            }
        });

        Schema::table('registrations', function (Blueprint $table) {
            if (!Schema::hasColumn('registrations', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id')->index();
            }
        });

        // Seed initial setting for registration login requirement
        if (\Illuminate\Support\Facades\Schema::hasTable('settings')) {
            \App\Models\Setting::updateOrCreate(
                ['key' => 'registration_login_required'],
                ['value' => '0'] // Default '0' = Bebas login (langsung daftar)
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'registrant_role',
                'gender',
                'school_name',
                'school_district',
                'google_id',
                'wa_otp_code',
                'wa_otp_expires_at',
            ]);
        });

        Schema::table('registrations', function (Blueprint $table) {
            if (Schema::hasColumn('registrations', 'user_id')) {
                $table->dropColumn('user_id');
            }
        });
    }
};
