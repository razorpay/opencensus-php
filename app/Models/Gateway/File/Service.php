<?php

namespace RZP\Models\Gateway\File;

use RZP\Models\Base;

class Service extends Base\Service
{
    public function create(array $input)
    {
        $gatewayFile = (new Core)->create($input);

        return $gatewayFile->toArrayAdmin();
    }

    public function acknowledge(string $id, array $data)
    {
        $gatewayFile = (new Core)->acknowledge($id, $data);

        return $gatewayFile->toArrayAdmin();
    }

    public function retry(string $id)
    {
        $gatewayFile = $this->repo->gateway_file->findOrFailPublic($id);

        (new Core)->process($gatewayFile);

        return $gatewayFile->toArrayAdmin();
    }

    public function generateGatewayFiles(string $type, array $input)
    {
        $gatewayFiles = (new Core)->generateGatewayFiles($type, $input);

        return $gatewayFiles->toArrayAdmin();
    }
}
