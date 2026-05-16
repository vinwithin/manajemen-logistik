<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\GudangLansirHeader;

$header = GudangLansirHeader::with(['gudang', 'kendaraans.penerimas.pakans.kodePakan', 'kendaraans.penerimas.tujuan'])->first();

echo "=== Header ===\n";
var_dump($header->toArray());

echo "\n=== Gudang ===\n";
var_dump($header->gudang?->toArray());

echo "\n=== Kendaraans ===\n";
var_dump($header->kendaraans->toArray());

echo "\n=== Penerimas ===\n";
foreach ($header->kendaraans as $kendaraan) {
    var_dump($kendaraan->penerimas->toArray());
}

echo "\n=== Pakans ===\n";
foreach ($header->kendaraans as $kendaraan) {
    foreach ($kendaraan->penerimas as $penerima) {
        var_dump($penerima->pakans->toArray());
    }
}

echo "\n=== Kode Pakan ===\n";
foreach ($header->kendaraans as $kendaraan) {
    foreach ($kendaraan->penerimas as $penerima) {
        foreach ($penerima->pakans as $pakan) {
            var_dump($pakan->kodePakan?->toArray());
        }
    }
}
