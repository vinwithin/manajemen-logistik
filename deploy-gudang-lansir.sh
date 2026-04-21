#!/bin/bash

# Deploy Gudang Lansir Nested Structure
# This script automates the deployment process

echo "🚀 Starting Gudang Lansir Deployment..."
echo ""

# Step 1: Backup old views
echo "📦 Step 1: Backing up old views..."
if [ -f "resources/views/pages/gudang/lansir/create.blade.php" ]; then
    mv resources/views/pages/gudang/lansir/create.blade.php resources/views/pages/gudang/lansir/create-old-backup.blade.php
    echo "✅ Backed up create.blade.php"
fi

if [ -f "resources/views/pages/gudang/lansir/show.blade.php" ]; then
    mv resources/views/pages/gudang/lansir/show.blade.php resources/views/pages/gudang/lansir/show-old-backup.blade.php
    echo "✅ Backed up show.blade.php"
fi
echo ""

# Step 2: Activate new views
echo "🔄 Step 2: Activating new views..."
if [ -f "resources/views/pages/gudang/lansir/create-new.blade.php" ]; then
    mv resources/views/pages/gudang/lansir/create-new.blade.php resources/views/pages/gudang/lansir/create.blade.php
    echo "✅ Activated create.blade.php"
fi

if [ -f "resources/views/pages/gudang/lansir/show-new.blade.php" ]; then
    mv resources/views/pages/gudang/lansir/show-new.blade.php resources/views/pages/gudang/lansir/show.blade.php
    echo "✅ Activated show.blade.php"
fi
echo ""

# Step 3: Run migrations
echo "🗄️  Step 3: Running migrations..."
php artisan migrate
echo ""

# Step 4: Clear caches
echo "🧹 Step 4: Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
echo "✅ All caches cleared"
echo ""

# Step 5: Regenerate autoload
echo "🔧 Step 5: Regenerating autoload..."
composer dump-autoload
echo ""

echo "✅ Deployment completed successfully!"
echo ""
echo "📋 Next steps:"
echo "1. Test create lansir: /gudang/lansir/create"
echo "2. Verify stock reduction after creating lansir"
echo "3. Check mutation records: /gudang/mutasi"
echo "4. Test view show and index pages"
echo ""
echo "🔄 To rollback, run: bash rollback-gudang-lansir.sh"
