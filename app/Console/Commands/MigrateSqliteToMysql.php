<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MigrateSqliteToMysql extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:migrate-to-mysql 
        {--connection=mysql_migration : The target database connection name defined in config/database.php}
        {--no-migrate : Skip running migrations on the target connection before copying data}
        {--keep-existing : Do not truncate tables on the target connection before inserting data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Copy all data from the current SQLite database to a MySQL connection.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $sourceConnection = config('database.default', 'sqlite');
        $targetConnection = $this->option('connection');

        if (!Config::has("database.connections.{$targetConnection}")) {
            $this->error("Database connection '{$targetConnection}' is not defined in config/database.php.");
            return self::FAILURE;
        }

        $sourceDriver = config("database.connections.{$sourceConnection}.driver");
        if ($sourceDriver !== 'sqlite') {
            $this->warn("Default connection '{$sourceConnection}' is using driver '{$sourceDriver}'. This command is intended to copy from SQLite.");
        }

        $targetDriver = config("database.connections.{$targetConnection}.driver");
        if ($targetDriver !== 'mysql') {
            $this->error("Target connection '{$targetConnection}' uses driver '{$targetDriver}'. Please point to a MySQL/MariaDB connection.");
            return self::FAILURE;
        }

        if (!$this->option('no-migrate')) {
            $this->info("Running migrations on connection '{$targetConnection}'...");
            Artisan::call('migrate', [
                '--database' => $targetConnection,
                '--force' => true,
            ]);
            $this->line(Str::of(Artisan::output())->trim());
        }

        $sourceTables = $this->getSqliteTables($sourceConnection);
        if (empty($sourceTables)) {
            $this->warn('No tables discovered in the SQLite database. Nothing to migrate.');
            return self::SUCCESS;
        }

        $target = DB::connection($targetConnection);
        $source = DB::connection($sourceConnection);

        $this->comment('Disabling MySQL foreign key checks...');
        $target->statement('SET FOREIGN_KEY_CHECKS=0');

        $ignoreTables = ['sqlite_sequence'];
        $copied = 0;

        $this->line('');
        $this->info("Migrating " . count($sourceTables) . " table(s) from '{$sourceConnection}' to '{$targetConnection}'");
        $progress = $this->output->createProgressBar(count($sourceTables));
        $progress->start();

        foreach ($sourceTables as $table) {
            $progress->advance();

            if (in_array($table, $ignoreTables, true)) {
                continue;
            }

            try {
                $rows = $source->table($table)->get();
            } catch (\Throwable $e) {
                $this->warn("\nUnable to read table '{$table}' ({$e->getMessage()}). Skipping.");
                continue;
            }

            try {
                if (!$this->option('keep-existing')) {
                    $target->table($table)->truncate();
                }
            } catch (\Throwable $e) {
                $this->warn("\nUnable to truncate target table '{$table}' ({$e->getMessage()}). Skipping table.");
                continue;
            }

            if ($rows->isEmpty()) {
                $copied++;
                continue;
            }

            $chunks = array_chunk(
                $rows->map(fn ($row) => (array) $row)->all(),
                500
            );

            try {
                foreach ($chunks as $chunk) {
                    $target->table($table)->insert($chunk);
                }
                $copied++;
            } catch (\Throwable $e) {
                $this->warn("\nFailed inserting rows into '{$table}' ({$e->getMessage()}).");
            }
        }

        $progress->finish();
        $this->line('');
        $this->comment('Re-enabling MySQL foreign key checks...');
        $target->statement('SET FOREIGN_KEY_CHECKS=1');

        $this->line('');
        $this->info("Completed copying {$copied} table(s).");
        $this->info('Next steps:');
        $this->line('  1. Update your .env file so DB_CONNECTION=mysql (and matching credentials).');
        $this->line("  2. Run: php artisan optimize:clear");
        $this->line('  3. Verify the application against the MySQL database.');

        return self::SUCCESS;
    }

    /**
     * Return a list of tables in the SQLite database.
     *
     * @param  string  $connection
     * @return array<int, string>
     */
    protected function getSqliteTables(string $connection): array
    {
        $tables = DB::connection($connection)->select("
            SELECT name 
            FROM sqlite_master 
            WHERE type = 'table'
            AND name NOT LIKE 'sqlite_%'
        ");

        return collect($tables)
            ->pluck('name')
            ->filter()
            ->values()
            ->all();
    }
}
