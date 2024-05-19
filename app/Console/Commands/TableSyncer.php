<?php

namespace App\Console\Commands;

use App\Helpers\Encrypter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class TableSyncer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'table:syncer {offset} {limit} {chunkNumber}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $startTime = microtime(true); // Start time for the entire process

        $offset = $this->argument('offset');
        $limit = $this->argument('limit');
        $chunkNumber = $this->argument('chunkNumber');

        $this->logger(
            "STARTED",
            "Command running process for chunk number : $chunkNumber",
            null
        );

        $customers = DB::connection('esupport')
            ->table('cust_mobileapp_users as cms')
            ->selectRaw("
                cell_number,
                pin,
                token,
                pin_use_flag,
                account,
                account_address,
                vendor,
                device_name,
                token_try,
                requested_date,
                last_count_increased_at
            ")
            ->where("pin_use_flag", 'Y')
            ->whereRaw("requested_date > sysdate - 180")
            ->offset($offset)
            ->limit($limit)
            ->get();

        foreach ($customers as $customer) {
            $customerArray = (array) $customer;

            unset($customerArray['rn']);

            $customerArray["cell_number"] = Encrypter::handle($customerArray["cell_number"]);
            $customerArray["pin"] = Hash::make($customerArray["pin"]);

            DB::connection('pgsql')->table('cust_mobile_users')->insert($customerArray);
        }

        $this->logger(
            "FINISHED",
            "Command running process for chunk number : $chunkNumber",
            $startTime,
        );
    }

    public function logger($process, $message, $startTime = null)
    {
        $timeTaken = null;
        if ($startTime !== null) {
            $endTime = microtime(true);
            $timeTaken = $endTime - $startTime;
        }

        $logData = [
            'process' => $process,
            'message' => $message,
        ];

        if ($timeTaken !== null) {
            $formattedTimeTaken = number_format($timeTaken, 2); // Format time taken to 2 decimal places
            $logData['time_taken'] = $formattedTimeTaken . ' seconds';
        }

        $log = json_encode($logData);

        Log::info($log);
    }
}
