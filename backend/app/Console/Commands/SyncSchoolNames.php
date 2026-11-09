<?php

namespace App\Console\Commands;

use App\Models\School;
use Illuminate\Console\Command;

class SyncSchoolNames extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'semarak:sync-school-names';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Harmonize and synchronize school names in registrations with official master schools database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting school names harmonization...');

        $result = School::harmonizeRegistrationsSchoolNames();

        $count = $result['updated_count'];

        if ($count === 0) {
            $this->info('All registrations are already in sync with official master schools.');
            return Command::SUCCESS;
        }

        $this->info("Successfully harmonized {$count} registration(s) with official master schools:");
        foreach ($result['details'] as $item) {
            $this->line(" - Reg #{$item['reg_id']} ({$item['reg_code']}): '{$item['from']}' -> '{$item['to']}'");
        }

        return Command::SUCCESS;
    }
}

