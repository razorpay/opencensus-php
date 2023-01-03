<?php

namespace RZP\Models\Customer\Truecaller\AuthRequest;

use RZP\Models\Base\Service as BaseService;
use RZP\Trace\TraceCode;

class Service extends BaseService
{
    /**
     * Creates the truecaller request entity and returns id to client
     *
     * @param array $input
     * @return mixed
     */
    public function create(array $input = [])
    {
        $this->trace->info(TraceCode::CREATE_TRUECALLER_ENTITY_REQUEST, [
            'input' => $input,
        ]);

        $this->trace->count(Metric::CREATE_TRUECALLER_ENTITY_REQUEST, [
            'status' => 'success',
        ]);

        return $this->core()->create($input);
    }

    /**
     * Handles the callback which truecaller posts to our endpoint
     *
     * @param $input
     * @return void
     */
    public function handleTruecallerCallback($input): void
    {
        $this->core()->handleTruecallerCallback($input);
    }
}
