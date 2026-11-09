<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            // 1. Create or replace the trigger function
            DB::unprepared("
                CREATE OR REPLACE FUNCTION sync_school_name_to_registrations()
                RETURNS TRIGGER AS $$
                BEGIN
                    IF NEW.name IS DISTINCT FROM OLD.name THEN
                        -- 1. Update registrations
                        UPDATE registrations 
                        SET school_name = NEW.name 
                        WHERE LOWER(TRIM(school_name)) = LOWER(TRIM(OLD.name));

                        -- 2. Update registrants (portal accounts)
                        UPDATE registrants 
                        SET school_name = NEW.name 
                        WHERE LOWER(TRIM(school_name)) = LOWER(TRIM(OLD.name));

                        -- 3. Update users (admin/jury)
                        UPDATE users 
                        SET school_name = NEW.name 
                        WHERE LOWER(TRIM(school_name)) = LOWER(TRIM(OLD.name));
                    END IF;
                    RETURN NEW;
                END;
                $$ LANGUAGE plpgsql;
            ");

            // 2. Attach trigger to schools table
            DB::unprepared("
                DROP TRIGGER IF EXISTS trg_sync_school_name ON schools;
                CREATE TRIGGER trg_sync_school_name
                AFTER UPDATE OF name ON schools
                FOR EACH ROW
                EXECUTE FUNCTION sync_school_name_to_registrations();
            ");
        }

        // 3. Harmonize existing historical discrepancies (e.g. MTs Negeri 1 Jember -> MTsN 1 Jember)
        try {
            DB::table('registrations')
                ->whereRaw("LOWER(TRIM(school_name)) = 'mts negeri 1 jember'")
                ->update(['school_name' => 'MTsN 1 Jember']);

            DB::table('registrants')
                ->whereRaw("LOWER(TRIM(school_name)) = 'mts negeri 1 jember'")
                ->update(['school_name' => 'MTsN 1 Jember']);

            DB::table('users')
                ->whereRaw("LOWER(TRIM(school_name)) = 'mts negeri 1 jember'")
                ->update(['school_name' => 'MTsN 1 Jember']);
        } catch (\Throwable $e) {
            // Ignore if tables not yet populated
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::unprepared("DROP TRIGGER IF EXISTS trg_sync_school_name ON schools;");
            DB::unprepared("DROP FUNCTION IF EXISTS sync_school_name_to_registrations();");
        }
    }
};

