<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\Datatables\GudangLansirDatatableService;
use Illuminate\Http\Request;

$request = Request::create('/gudang/lansir', 'GET');
$service = new GudangLansirDatatableService();

$response = $service->getData($request);

echo "=== Datatable Response ===\n";
echo json_encode($response->getData(true), JSON_PRETTY_PRINT);
