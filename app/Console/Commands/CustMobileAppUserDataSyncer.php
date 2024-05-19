<?php

namespace App\Console\Commands;

use App\Jobs\ProcessCustomerData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CustMobileAppUserDataSyncer extends Command
{
    protected $signature = 'command:cust-mobile-app-user-data-syncer';
    protected $description = 'Command description';

    public function handle()
    {
        $count = (int) DB::connection('esupport')
            ->table('cust_mobileapp_users as cms')
            ->selectRaw("count(*) as count")
            ->where("pin_use_flag", 'Y')
            ->whereRaw("requested_date > sysdate - 30")
            ->first()->count;
        Log::info("Data count inside table cust_mobileapp_users: $count");

        $chunkSize = 1000;
        $numChunks = ceil($count / $chunkSize);
        Log::info("Total number of chunks: $numChunks");

        for ($i = 0; $i < $numChunks; $i++) {
            $offset = $i * $chunkSize;
            $limit = ($i === $numChunks - 1) ? $count % $chunkSize : $chunkSize;

            $chunkNumber = ($i + 1);
            exec("php artisan table:syncer $offset $limit $chunkNumber >/proc/1/fd/1 2>&1 >/dev/null &");
        }
    }
}
