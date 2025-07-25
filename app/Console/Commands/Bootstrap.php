<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class Bootstrap extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:bootstrap';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Downloads, imports and calculates data.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->call('app:download');
        $this->call('app:import');
        $this->call('app:calculate');
    }
}
