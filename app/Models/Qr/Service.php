<?php

namespace RZP\Models\Qr;

use RZP\Models\Base;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;
    }

    public function processPayment(array $input): array
    {
        $this->trace->info(
            TraceCode::QR_PAYMENT_PROCESS_REQUEST,
            $input
        );

        $valid = $this->core->processPayment($input);

        return [
            'valid'          => $valid,
            'message'        => null,
            'transaction_id' => $input[Entity::REQ_UTR],
        ];
    }
}

