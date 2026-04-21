#!/bin/bash

# Rollback Gudang Lansir Nested Structure
# This script reverts the deployment

echo "⚠️  Starting Gudang Lansir Rollback..."
echo ""

# Step 1: Rollback migrations
echo "🗄️  Step 1: Rolling back migrations..."
php artisan migrate:rollback --step=2
echo ""

# Step 2: Restore old views
echo "🔄 Step 2: Restoring old views..."
if [ -f "resources/views/pages/gudang/lansir/create-old-backup.blade.php" ]; then
    mv resources/views/pages/gudang/lansir/create-old-backup.blade.php resources/views/pages/gudang/lansir/create.blade.php
    echo "✅ Restored create.blade.php"
fi

if [ -f "resources/views/pages/gudang/lansir/show-old-backup.blade.php" ]; then
    mv resources/views/pages/gudang/lansir/show-old-backup.blade.php resources/views/pages/gudang/lansir/show.blade.php
    echo "✅ Restored show.blade.php"
fi
echo ""

# Step 3: Clear caches
echo "🧹 Step 3: Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
echo "✅ All caches cleared"
echo ""

# Step 4: Regenerate autoload
echo "🔧 Step 4: Regenerating autoload..."
composer dump-autoload
echo ""

echo "✅ Rollback completed successfully!"
echo ""
echo "⚠️  The old gudang lansir structure has been restored."
echo "📋 Verify the application is working correctly."
