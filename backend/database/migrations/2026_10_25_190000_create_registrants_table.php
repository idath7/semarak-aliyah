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
        // 1. Buat tabel terpisah khusus akun pendaftar (registrants)
        if (!Schema::hasTable('registrants')) {
            Schema::create('registrants', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('phone', 30)->nullable()->index();
                $table->string('password');
                $table->string('gender', 20)->nullable();
                $table->string('school_name')->nullable();
                $table->string('school_district')->nullable();
                $table->string('registrant_role', 50)->default('pendamping'); // pendamping, koordinator, kapten, siswa_peserta
                $table->string('google_id')->nullable()->index();
                $table->string('wa_otp_code', 10)->nullable();
                $table->timestamp('wa_otp_expires_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->string('avatar')->nullable();
                $table->rememberToken();
                $table->timestamps();
            });
        }

        // 2. Buat tabel password reset tokens khusus pendaftar
        if (!Schema::hasTable('registrant_password_reset_tokens')) {
            Schema::create('registrant_password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        // 3. Tambahkan kolom registrant_id pada tabel registrations
        Schema::table('registrations', function (Blueprint $table) {
            if (!Schema::hasColumn('registrations', 'registrant_id')) {
                $table->unsignedBigInteger('registrant_id')->nullable()->after('user_id')->index();
            }
        });

        // 4. Migrasi data pendaftar dari tabel users ke tabel registrants
        $registrantUsers = DB::table('users')
            ->whereIn('role', ['pendaftar', 'peserta'])
            ->get();

        foreach ($registrantUsers as $ru) {
            DB::table('registrants')->updateOrInsert(
                ['id' => $ru->id],
                [
                    'name' => $ru->name,
                    'email' => $ru->email,
                    'phone' => $ru->phone,
                    'password' => $ru->password,
                    'gender' => $ru->gender ?? null,
                    'school_name' => $ru->school_name ?? null,
                    'school_district' => $ru->school_district ?? null,
                    'registrant_role' => $ru->registrant_role ?? 'pendamping',
                    'google_id' => $ru->google_id ?? null,
                    'wa_otp_code' => $ru->wa_otp_code ?? null,
                    'wa_otp_expires_at' => $ru->wa_otp_expires_at ?? null,
                    'is_active' => $ru->is_active ?? true,
                    'avatar' => $ru->avatar ?? null,
                    'created_at' => $ru->created_at ?? now(),
                    'updated_at' => $ru->updated_at ?? now(),
                ]
            );
        }

        // Salin user_id ke registrant_id di tabel registrations
        DB::statement("UPDATE registrations SET registrant_id = user_id WHERE registrant_id IS NULL AND user_id IS NOT NULL");

        // Hapus akun pendaftar dari tabel users agar tabel users murni hanya berisi Panitia / Pengelola
        DB::table('users')->whereIn('role', ['pendaftar', 'peserta'])->delete();

        // Update sequence PostgreSQL untuk tabel registrants jika sequence tersedia
        try {
            DB::statement("SELECT setval('registrants_id_seq', COALESCE((SELECT MAX(id) FROM registrants), 1), true)");
        } catch (\Throwable $e) {
            // Abaikan jika database bukan PostgreSQL atau sequence bernama lain
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Pulihkan pendaftar kembali ke tabel users jika di-rollback
        $registrants = DB::table('registrants')->get();
        foreach ($registrants as $reg) {
            DB::table('users')->updateOrInsert(
                ['id' => $reg->id],
                [
                    'name' => $reg->name,
                    'email' => $reg->email,
                    'phone' => $reg->phone,
                    'password' => $reg->password,
                    'role' => 'pendaftar',
                    'registrant_role' => $reg->registrant_role,
                    'gender' => $reg->gender,
                    'school_name' => $reg->school_name,
                    'school_district' => $reg->school_district,
                    'google_id' => $reg->google_id,
                    'wa_otp_code' => $reg->wa_otp_code,
                    'wa_otp_expires_at' => $reg->wa_otp_expires_at,
                    'is_active' => $reg->is_active,
                    'avatar' => $reg->avatar,
                    'created_at' => $reg->created_at,
                    'updated_at' => $reg->updated_at,
                ]
            );
        }

        Schema::table('registrations', function (Blueprint $table) {
            if (Schema::hasColumn('registrations', 'registrant_id')) {
                $table->dropColumn('registrant_id');
            }
        });

        Schema::dropIfExists('registrant_password_reset_tokens');
        Schema::dropIfExists('registrants');
    }
};
