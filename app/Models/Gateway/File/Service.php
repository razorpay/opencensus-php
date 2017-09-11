<?php

namespace RZP\Models\Gateway\File;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    public function create(array $input)
    {
        $input = $this->formatInput($input);

        $gatewayFiles = $this->core()->create($input);

        return $gatewayFiles->toArrayAdmin();
    }

    public function acknowledge(string $id, array $data)
    {
        $this->trace->info(TraceCode::GATEWAY_FILE_ACKNOWLEDGE_REQUEST, [
            'id'   => $id,
            'data' => $data,
        ]);

        $gatewayFile = $this->repo->gateway_file->findOrFailPublic($id);

        $gatewayFile = $this->core()->acknowledge($gatewayFile, $data);

        return $gatewayFile->toArrayAdmin();
    }

    public function retry(string $id)
    {
        $gatewayFile = $this->repo->gateway_file->findOrFailPublic($id);

        $this->core()->process($gatewayFile);

        return $gatewayFile->toArrayAdmin();
    }

    protected function formatInput(array $input): array
    {
        if ((isset($input['targets']) === false) or
            (is_sequential_array($input['targets']) === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                'targets are required and should be sent');
        }

        $targets = $input['targets'];
        unset($input['targets']);

        $data = [];

        foreach ($targets as $target)
        {
            $input[Entity::TARGET] = $target;

            $data[] = $input;
        }

        return $data;
    }
}
