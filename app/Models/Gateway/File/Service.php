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

    public function retry(string $id)
    {
        $gatewayFile = $this->repo->gateway_file->findOrFailPublic($id);

        (new Core)->process($gatewayFile);

        return $gatewayFile->toArrayAdmin();
    }
}
