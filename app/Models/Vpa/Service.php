<?php

namespace RZP\Models\Vpa;

use RZP\Models\Base;
use RZP\Models\Customer;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    public function __construct()
    {
        parent::__construct();

        $this->core = new Customer\Core;
    }

    public function create(string $customerId, array $input)
    {
        $this->trace->info(
            TraceCode::VPA_CREATE_FOR_CUSTOMER_REQUEST,
            [
                'input'       => $input,
                'customer_id' => $customerId
            ]);

        $customer = $this->repo->customer->findByPublicIdAndMerchant($customerId, $this->merchant);

        $vpa = (new Core)->createVpa($input, $this->merchant, $customer);

        $this->trace->info(TraceCode::VPA_CREATED, ['vpa' => $vpa->toArray()]);

        return $vpa->toArrayPublic();
    }
}
