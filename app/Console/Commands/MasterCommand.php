<?php

namespace App\Console\Commands;

use App\Helpers\RedisHelper;
use App\Services\EbillService;
use Illuminate\Console\Command;

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
            $set = $redis->setKey("-supportzone-$supportZone", $count);
            // $this->info("Redis store supportzone-$supportZone : Count $count -> $set");
        }

        $listOfBranches = $eBillService->getListOfAllBranches();

        foreach ($listOfBranches as $key => $value)
            exec("php artisan command:sync-support-zone-customers '$key' '$value' >/dev/null 2>&1 &");

        $this->info('DONE');
    }
}
