<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            if (!Schema::hasColumn('competitions', 'participant_type')) {
                $table->string('participant_type', 20)->default('individu')->after('status');
            }
            if (!Schema::hasColumn('competitions', 'max_participants')) {
                $table->integer('max_participants')->default(1)->after('participant_type');
            }
            if (!Schema::hasColumn('competitions', 'registration_deadline')) {
                $table->string('registration_deadline', 100)->nullable()->after('max_participants');
            }
            if (!Schema::hasColumn('competitions', 'coordinators')) {
                $table->json('coordinators')->nullable()->after('contact_person');
            }
        });
    }

    public function down(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            $table->dropColumn(['participant_type', 'max_participants', 'registration_deadline', 'coordinators']);
        });
    }
};
