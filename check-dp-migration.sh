#!/bin/bash

# Script untuk mengecek status migration DP Supplier
# Jalankan dengan: bash check-dp-migration.sh

echo "========================================="
echo "CEK STATUS MIGRATION DP SUPPLIER"
echo "========================================="
echo ""

# Warna untuk output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# 1. Cek apakah file migration ada
echo "1. Cek file migration..."
if [ -f "database/migrations/2026_05_10_090000_add_dp_fields_to_po_kendaraan.php" ]; then
    echo -e "${GREEN}✓ File migration ditemukan${NC}"
else
    echo -e "${RED}✗ File migration TIDAK ditemukan${NC}"
    echo "   Lokasi: database/migrations/2026_05_10_090000_add_dp_fields_to_po_kendaraan.php"
    exit 1
fi

echo ""

# 2. Cek status migration
echo "2. Cek status migration di database..."
php artisan migrate:status | grep "add_dp_fields_to_po_kendaraan" > /dev/null 2>&1

if [ $? -eq 0 ]; then
    STATUS=$(php artisan migrate:status | grep "add_dp_fields_to_po_kendaraan")
    if echo "$STATUS" | grep -q "Ran"; then
        echo -e "${GREEN}✓ Migration sudah dijalankan${NC}"
        echo "   $STATUS"
    else
        echo -e "${YELLOW}⚠ Migration belum dijalankan${NC}"
        echo "   $STATUS"
        echo ""
        echo "Jalankan migration dengan:"
        echo "   php artisan migrate"
        exit 1
    fi
else
    echo -e "${YELLOW}⚠ Tidak dapat mengecek status migration${NC}"
    echo "   Mungkin migration belum pernah dijalankan"
    echo ""
    echo "Jalankan migration dengan:"
    echo "   php artisan migrate"
    exit 1
fi

echo ""

# 3. Cek kolom di database
echo "3. Cek kolom DP di tabel po_kendaraan..."

# Ambil database credentials dari .env
DB_DATABASE=$(grep DB_DATABASE .env | cut -d '=' -f2)
DB_USERNAME=$(grep DB_USERNAME .env | cut -d '=' -f2)
DB_PASSWORD=$(grep DB_PASSWORD .env | cut -d '=' -f2)

# Cek kolom dp_nominal
COLUMN_CHECK=$(mysql -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" -e "SHOW COLUMNS FROM po_kendaraan LIKE 'dp_nominal';" 2>/dev/null | wc -l)

if [ $COLUMN_CHECK -gt 1 ]; then
    echo -e "${GREEN}✓ Kolom DP ditemukan di database${NC}"
    echo ""
    echo "Kolom DP yang tersedia:"
    mysql -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" -e "SHOW COLUMNS FROM po_kendaraan WHERE Field LIKE 'dp_%';" 2>/dev/null
else
    echo -e "${RED}✗ Kolom DP TIDAK ditemukan di database${NC}"
    echo "   Migration mungkin gagal dijalankan"
    echo ""
    echo "Coba jalankan migration lagi:"
    echo "   php artisan migrate"
    exit 1
fi

echo ""
echo "========================================="
echo -e "${GREEN}SEMUA CEK BERHASIL!${NC}"
echo "========================================="
echo ""
echo "Fitur DP Supplier siap digunakan."
echo ""
