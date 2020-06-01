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

        // Attempt to decrypt database backup
        if($isEncrypted)
        {
            try {
                $sql = decrypt($sql);
            } catch (DecryptException $e) {
                // Unable to decrypt file. Either the file is not encrypted or
                // APP_KEY was not used to encrypt the file when the backup was created
                $this->error('Failed to decrypt backup. This may be because the APP_KEY specified in .env differs from the key used to encrypt the file or the backup is unencrypted.');
                
                // Lets try this again...
                $this->call('restore');
                exit();
            }
        }

        // Save plain text copy of backup
        file_put_contents(storage_path('app/backups/'.now()->format('Y-m-d_H-i').'.sql'),$sql);


        // DB::unprepared($sql);

        
    }
}
