<?php

namespace RZP\Models\Gateway\File;

use RZP\Models\Base;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    public function create(array $input)
    {
        $gatewayFile = $this->core()->create($input);

        return $gatewayFile->toArrayAdmin();
    }

    public function acknowledge(string $id, array $data)
    {
        $this->trace->info(TraceCode::GATEWAY_ACKNOWLEDGE_REQUEST, [
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

    public function generateGatewayFiles(string $type, array $input)
    {
        $sources = $input['sources'];

        $from = $input[Entity::FROM];

        $to = $input[Entity::TO];

        $gatewayFiles = $this->core()->generateGatewayFiles($type, $sources, $from, $to);

        return $gatewayFiles->toArrayAdmin();
    }
}
