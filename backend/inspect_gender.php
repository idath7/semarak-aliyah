<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "--- REGISTRANTS ---\n";
$regs = DB::table('registrants')->get();
foreach ($regs as $r) {
    echo "ID: {$r->id} | Name: {$r->name} | School: {$r->school_name} | Email: {$r->email}\n";
}
