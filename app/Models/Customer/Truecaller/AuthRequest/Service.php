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

        return $this->core()->create($input);
    }

    public function createTruecallerAuthRequestInternal()
    {
        try {
            $input = [
                'context' => $this->merchant->getId(),
                'service' => Constants::DEFAULT_SERVICE,
            ];

            $this->trace->info(TraceCode::CREATE_TRUECALLER_ENTITY_REQUEST, [
                'input' => $input,
            ]);

            $data =  $this->core()->create($input);

            $this->trace->count(Metric::CREATE_TRUECALLER_ENTITY_REQUEST, [
                'status' => 'success',
            ]);

            return $data;
        }
        catch (\Exception $exception)
        {
            $this->trace->error(TraceCode::FILL_TRUECALLER_DETAILS_ERROR, [
                'message'      => $exception->getMessage()
            ]);

            $this->trace->count(Metric::CREATE_TRUECALLER_ENTITY_REQUEST, [
                'status' => 'error',
            ]);

            throw $exception;
        }
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
