<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class EbillService
{
    public function getSupportZoneCustomerCountDetails()
    {
        return DB::connection('ebill')
            ->table('customer_ebill')
            ->selectRaw('support_zone, COUNT(*) as count')
            ->whereNotNull('support_zone')
            ->groupBy('support_zone')
            ->orderByRaw('count asc')
            ->pluck('count', 'support_zone')
            ->toArray();
    }

    public function getListOfAllBranches()
    {
        return DB::connection('ebill')
            ->table('ebill_branches')
            ->selectRaw('branch_id supportzone_id, branch_name support_zone')
            ->where('disable', 0)
            ->where('branch_type', 'normal')
            ->orderByRaw('supportzone_id asc')
            ->pluck('support_zone', 'supportzone_id')
            ->toArray();
    }
}
