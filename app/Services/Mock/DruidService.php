<?php

namespace RZP\Services\Mock;

use RZP\Services\DruidService as BaseDruidService;

class DruidService extends BaseDruidService
{
    public function getDataFromDruid(array $content, $timeout = 2)
    {
        $data = [
            'user_days_till_last_transaction'            => 45,
            'merchant_lifetime_gmv'                      => 100.0,
            'average_monthly_gmv'                        => 10.0,
            'primary_product_used'                       => 'payment_links',
            'ppc'                                        => 1,
            'mtu'                                        => false,
            'average_monthly_transactions'               => 3,
            'pg_only'                                    => false,
            'pl_only'                                    => true,
            'pp_only'                                    => false,
            'merchants_id'                               => '10000000000000',
            'merchant_fact_authorized_gmv_ltd'           => 45,
            'merchant_fact_authorized_payment_count_ltd' => 2,
        ];

        return [null, [$data]];
    }
}
