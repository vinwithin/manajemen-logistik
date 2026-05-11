<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DatabaseBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:backup {--keep-days=7 : Number of days to keep old backups}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backup database and clean up old backups';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $this->info('Starting database backup...');

            // Get database configuration
            $dbHost = config('database.connections.mysql.host');
            $dbPort = config('database.connections.mysql.port');
            $dbName = config('database.connections.mysql.database');
            $dbUser = config('database.connections.mysql.username');
            $dbPassword = config('database.connections.mysql.password');

            // Create backup directory if not exists
            $backupDir = storage_path('app/backups');
            if (!file_exists($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            // Generate backup filename with timestamp
            $timestamp = Carbon::now()->format('Y-m-d_His');
            $filename = "backup_{$dbName}_{$timestamp}.sql";
            $filepath = $backupDir . '/' . $filename;
            $gzFilepath = $filepath . '.gz';

            // Build mysqldump command
            $command = sprintf(
                'mysqldump --user=%s --host=%s --port=%s %s %s > %s',
                escapeshellarg($dbUser),
                escapeshellarg($dbHost),
                escapeshellarg($dbPort),
                $dbPassword ? '--password=' . escapeshellarg($dbPassword) : '',
                escapeshellarg($dbName),
                escapeshellarg($filepath)
            );

            // Execute backup
            $this->info("Backing up database: {$dbName}");
            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                throw new \Exception('Database backup failed with return code: ' . $returnCode);
            }

            // Check if backup file was created
            if (!file_exists($filepath) || filesize($filepath) === 0) {
                throw new \Exception('Backup file was not created or is empty');
            }

            // Compress the backup
            $this->info('Compressing backup...');
            exec("gzip -f " . escapeshellarg($filepath), $output, $returnCode);

            if ($returnCode !== 0) {
                $this->warn('Compression failed, keeping uncompressed backup');
                $finalFile = $filename;
            } else {
                $finalFile = $filename . '.gz';
            }

            $fileSize = $this->formatBytes(filesize($backupDir . '/' . $finalFile));
            $this->info("Backup created successfully: {$finalFile} ({$fileSize})");

            // Clean up old backups
            $keepDays = (int) $this->option('keep-days');
            $this->cleanupOldBackups($backupDir, $keepDays);

            // Log success
            Log::info('Database backup completed successfully', [
                'filename' => $finalFile,
                'size' => $fileSize,
                'database' => $dbName
            ]);

            $this->info('Database backup completed successfully!');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Backup failed: ' . $e->getMessage());
            Log::error('Database backup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Clean up old backup files
     */
    private function cleanupOldBackups($backupDir, $keepDays)
    {
        $this->info("Cleaning up backups older than {$keepDays} days...");

        $files = glob($backupDir . '/backup_*.sql*');
        $cutoffTime = Carbon::now()->subDays($keepDays)->timestamp;
        $deletedCount = 0;

        foreach ($files as $file) {
            if (filemtime($file) < $cutoffTime) {
                unlink($file);
                $deletedCount++;
                $this->line('Deleted: ' . basename($file));
            }
        }

        if ($deletedCount > 0) {
            $this->info("Deleted {$deletedCount} old backup(s)");
        } else {
            $this->info('No old backups to delete');
        }
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
