<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\DbDumper\Databases\MySql;
use Spatie\DbDumper\Compressors\GzipCompressor;
use Illuminate\Support\Facades\Storage;

class Backup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup {--encrypt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create database backup';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $shouldEncrypt = $this->option('encrypt');
        $connection = config('database.connections.mysql');
        $date = now()->format('Y-m-d');

        $filename = "{$connection['database']}-{$date}.sql";
        $filepath = storage_path("app/backups/{$filename}");
        MySql::create()
            ->setHost($connection['host'])
            ->setDbName($connection['database'])
            ->setUserName($connection['username'])
            ->setPassword($connection['password'])
            ->useCompressor(new GzipCompressor())
            ->dumpToFile($filepath);
        $contents = file_get_contents($filepath);

        if ($shouldEncrypt) {
            $this->line("{$name}: Encrypting backup");
            $filename = explode('.', $filename);
            $filename = $filename[0].'.encrypted.'.$filename[1];
            $contents = encrypt($contents);
        }

        $uploaded = Storage::disk('s3')->put("backups/{$filename}", $contents);

        if($uploaded)
        {
            unlink($filepath);
        }
    }
}
