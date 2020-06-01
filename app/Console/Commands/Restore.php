<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

class Restore extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'restore';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restore database from backup';

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
        // Get a list of backup files
        $backups = Storage::disk('s3')->allFiles('/backups');
       
        // Which back should we restore
        $backup = $this->choice('Which backup would you like to restore?', $backups);

        // Is the backup encrypted
        $isEncrypted = $this->choice('The selected backup encrypted?', ['false','true']);

        // Are you sure you want to proceed
        if (!$this->confirm("Are you sure you want to restore backup {$backup}?")) {
            $this->error("Please confirm you wish to import backup {$backup}");
            // Lets try this again...
            $this->call('restore');
            exit();
        }

        // Get file contents
        $sql = Storage::disk('s3')->get($backup);

    }
}
