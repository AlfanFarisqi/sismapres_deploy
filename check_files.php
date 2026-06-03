<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$mahasiswa = \App\Models\Mahasiswa::with('berkas')->get();
foreach ($mahasiswa as $m) {
    if ($m->berkas->count() > 0) {
        $status = $m->status_berkas;
        $missing = 0;
        $total = 0;
        foreach ($m->berkas as $b) {
            $total++;
            $path = storage_path('app/public/' . $b->file_path);
            if (!file_exists($path)) {
                $missing++;
            }
        }
        echo "Mahasiswa ID {$m->id} (Status: {$status}) - Files: {$total}, Missing: {$missing}\n";
    }
}
