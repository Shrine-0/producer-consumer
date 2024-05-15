<?php

namespace App\Console\Commands;

use App\Helpers\Encrypter;
use App\Helpers\RedisHelper;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SyncSupportzoneCustomersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:sync-support-zone-customers {support_zone_id} {support_zone}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Support zone wise customer data sync.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $supportzone_id = $this->argument('support_zone_id');
        $support_zone = $this->argument('support_zone');

        $customerCount = $this->getCustomerCountFromRedis($support_zone) ?? 0;

        if ($customerCount > 0) {
            $this->processDataSync($supportzone_id, $support_zone, $customerCount);
        } else {
            // Log::info("REDUNDANT:: No customers found in branch $support_zone with support zone id $supportzone_id \n");
        }
    }

    public function processDataSync($supportzone_id, $support_zone, $customerCount)
    {
        $tableName = $this->buildTempTableName($support_zone);

        $startTime = microtime(true); // Start time for the entire process

        $this->logger(
            "STARTED",
            // "Process of support zone $support_zone with support zone id $supportzone_id, Total Customers in $support_zone : $customerCount",
            "Command execution with total customer : $customerCount",
            null,
            $supportzone_id,
            $support_zone
        );

        $this->createTable($tableName, $support_zone, $supportzone_id, $startTime); // create table

        $this->fetchDataAndInsert($customerCount, $supportzone_id, $tableName, $support_zone, $startTime); // fetch data from eBill and insert into temp table

        $this->mergeDataFromTempTable($tableName, $supportzone_id, $support_zone, $startTime); // merge temp table

        $this->dropTempTable($tableName, $support_zone, $supportzone_id, $startTime); // drop temp table

        $this->logger(
            "FINISHED",
            // "Copied customer data from temp table and synced data to main table successfully for $support_zone with total $customerCount customer",
            "Command completed with total customer : $customerCount",
            $startTime,
            $supportzone_id,
            $support_zone
        );
    }

    public function createTable($tableName, $support_zone, $supportzone_id, $startTime)
    {
        $this->logger(
            "STARTED",
            "Process for creating temp schema",
            null,
            $supportzone_id,
            $support_zone
        );

        DB::connection('pgsql')->statement("DROP TABLE IF EXISTS $tableName"); // drop if table exists

        $createSql = "
            CREATE TABLE IF NOT EXISTS $tableName
            (
                username character varying(255),
                client_name character varying(255),
                email_primary character varying(255),
                email_secondary character varying(255),
                account_status character varying(255),
                primary_number character varying(255),
                secondary_number character varying(255),
                account_type character varying(255),
                pay_plan character varying(4),
                plan_category_id character varying(8),
                supportzone_id integer,
                supportzone character varying(255),
                sync_date timestamp without time zone,
                sync_medium character varying(255),
                ownership character varying(255),
                member_start_date timestamp without time zone,
                expiry_date timestamp without time zone,
                created_at timestamp without time zone,
                updated_at timestamp without time zone
            )";

        DB::connection('pgsql')->statement($createSql);

        $this->logger(
            "FINISHED",
            "Process for creating temp schema",
            $startTime,
            $supportzone_id,
            $support_zone
        );
    }

    public function fetchDataAndInsert($customerCount, $supportzone_id, $tableName, $support_zone, $startTime)
    {
        $this->logger(
            "STARTED",
            "Process for fetching data from e-bill and inserting data in temp table",
            null,
            $supportzone_id,
            $support_zone
        );

        $chunkSize = 1000;
        $numChunks = ceil($customerCount / $chunkSize);

        for ($i = 0; $i < $numChunks; $i++) {

            $offset = $i * $chunkSize;
            $limit = $chunkSize;

            if ($i === $numChunks - 1) $limit = $customerCount % $chunkSize;

            $customers = $this->fetchEBillCustomer($supportzone_id, $offset, $limit)->toArray();

            foreach ($customers as $customer) {
                $customerArray = (array) $customer;

                unset($customerArray['rn']);

                $customerArray["primary_number"] = Encrypter::handle($customerArray["primary_number"]); // number encryption
                $customerArray["secondary_number"] = Encrypter::handle($customerArray["secondary_number"]); // number encryption

                $customerArray["sync_date"] = Carbon::now();
                $customerArray["sync_medium"] = "Master Command Sync";

                DB::connection('pgsql')->table($tableName)->insert($customerArray);
            }
        }

        $this->logger(
            "FINISHED",
            "Process for fetching data from e-bill and inserting data in temp table",
            $startTime,
            $supportzone_id,
            $support_zone
        );
    }

    public function fetchEBillCustomer($supportzone_id, $offset, $limit)
    {
        return DB::connection('ebill')
            ->table('customer_ebill as ce')
            ->join('customer_info as info', 'ce.user_name', '=', 'info.machine_name')
            ->selectRaw("
                ce.user_name AS username,
                info.per_client_name AS client_name,
                info.per_cont_email_primary AS email_primary,
                info.per_cont_email_secondary AS email_secondary,
                CASE WHEN ce.disable = 'N' THEN 'enable' ELSE 'disable' END AS account_status,
                info.per_cont_mobile AS primary_number,
                info.per_cont_mobile_secondary AS secondary_number,
                info.account_type,
                ce.pay_plan,
                ce.plan_category_id,
                ce.supportzone_id AS supportzone_id,
                ce.support_zone AS supportzone,
                CASE WHEN ce.pay_plan = '8000' THEN 'WIFINEPAL'
                        WHEN ce.pay_plan = '8001' THEN 'EASTLINK'
                        ELSE 'WORLDLINK' END AS ownership,
                ce.create_date AS member_start_date,
                ce.expiry_date AS expiry_date
            ")
            ->where("ce.supportzone_id", $supportzone_id)
            // ->orderBy('ce.user_name')
            ->offset($offset)
            ->limit($limit)
            ->get();
    }

    public function mergeDataFromTempTable($tempTableName, $supportzone_id, $support_zone, $startTime)
    {
        $main_table = "cust_mobile_base_infos"; // main table

        $this->logger(
            "STARTED",
            "Process for merging data from temp table to $main_table table",
            null,
            $supportzone_id,
            $support_zone
        );

        DB::connection('pgsql')->statement("
            INSERT INTO $main_table (username, client_name, email_primary, email_secondary, account_status, primary_number, secondary_number, account_type, pay_plan, plan_category_id, supportzone_id, supportzone, sync_date, sync_medium, ownership, member_start_date, expiry_date, created_at, updated_at)
            SELECT
                username, client_name, email_primary, email_secondary, account_status, primary_number, secondary_number, account_type, pay_plan, plan_category_id, supportzone_id, supportzone, sync_date, sync_medium, ownership, member_start_date, expiry_date, created_at, updated_at
            FROM $tempTableName
            ON CONFLICT (username) DO UPDATE SET
                client_name = EXCLUDED.client_name,
                email_primary = EXCLUDED.email_primary,
                email_secondary = EXCLUDED.email_secondary,
                account_status = EXCLUDED.account_status,
                primary_number = EXCLUDED.primary_number,
                secondary_number = EXCLUDED.secondary_number,
                account_type = EXCLUDED.account_type,
                pay_plan = EXCLUDED.pay_plan,
                plan_category_id = EXCLUDED.plan_category_id,
                supportzone_id = EXCLUDED.supportzone_id,
                supportzone = EXCLUDED.supportzone,
                sync_date = EXCLUDED.sync_date,
                sync_medium = EXCLUDED.sync_medium,
                ownership = EXCLUDED.ownership,
                member_start_date = EXCLUDED.member_start_date,
                expiry_date = EXCLUDED.expiry_date,
                created_at = EXCLUDED.created_at,
                updated_at = EXCLUDED.updated_at;
        ");

        $this->logger(
            "FINISHED",
            "Process for merging data from temp table to $main_table table",
            $startTime,
            $supportzone_id,
            $support_zone
        );
    }

    public function dropTempTable($tempTableName, $support_zone, $supportzone_id, $startTime)
    {
        $this->logger(
            "STARTED",
            "Process for temp table drop",
            null,
            $supportzone_id,
            $support_zone
        );

        Schema::connection('pgsql')->dropIfExists($tempTableName);

        $this->logger(
            "FINISHED",
            "Process for temp table drop",
            $startTime,
            $supportzone_id,
            $support_zone
        );
    }

    public function buildTempTableName($support_zone)
    {
        return "cb_tmp_" . strtolower(preg_replace("/[^A-Za-z0-9]/", '_', $support_zone));
    }

    public function getCustomerCountFromRedis($support_zone)
    {
        $redis = new RedisHelper();
        return $redis->getKey("-supportzone-$support_zone"); // redis prefix is attached default
    }

    public function logger($process, $message, $startTime = null, $supportzone_id = null, $support_zone = null)
    {
        $timeTaken = null;
        if ($startTime !== null) {
            $endTime = microtime(true);
            $timeTaken = $endTime - $startTime;
        }

        $logData = [
            'supportzone_id' => $supportzone_id,
            'support_zone' => $support_zone,
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
