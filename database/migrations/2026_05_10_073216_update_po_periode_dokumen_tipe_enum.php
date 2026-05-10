<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Untuk MySQL, kita perlu mengubah enum dengan ALTER TABLE
        DB::statement("ALTER TABLE po_periode_dokumen MODIFY COLUMN tipe ENUM('ptsum', 'supplier', 'gudang_ptsum', 'gudang_supplier') DEFAULT 'ptsum'");
    }

    public function down(): void
    {
        // Rollback ke enum original
        DB::statement("ALTER TABLE po_periode_dokumen MODIFY COLUMN tipe ENUM('ptsum', 'supplier') DEFAULT 'ptsum'");
    }
};
