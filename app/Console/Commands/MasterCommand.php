<?php

namespace App\Console\Commands;

use App\Helpers\RedisHelper;
use App\Services\EbillService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MasterCommand extends Command
{
    protected $signature = 'command:master';

    protected $description = 'Process support zones data';

    public function handle()
    {
        $eBillService = new EbillService();
        $redis = new RedisHelper();

        $supportZoneData = $eBillService->getSupportZoneCustomerCountDetails();

        foreach ($supportZoneData as $supportZone => $count) {
            $this->logger('STARTED', "Redis cache store total customer count $count", $supportZone);
            $set = $redis->setKey("-supportzone-$supportZone", $count);
            $this->logger('FINISHED', "Redis cache store total customer count $count with response $set", $supportZone);
        }

        $listOfBranches = $eBillService->getListOfAllBranches();

        foreach ($listOfBranches as $key => $value)
            exec("php artisan command:sync-support-zone-customers '$key' '$value' >/proc/1/fd/1 2>&1 >/dev/null &");

        $this->info('DONE');
    }

    public function logger($process, $message, $support_zone = null)
    {
        $logData = [
            'support_zone' => $support_zone,
            'process' => $process,
            'message' => strtoupper($message),
        ];
        Log::info(json_encode($logData));
    }
}
