<?php

namespace RZP\Gateway\Esigner\Digio\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\Esigner\Digio;

class Gateway extends Digio\Gateway
{
    use Base\Mock\GatewayTrait;

    public function authorize(array $input)
    {
        $request = parent::authorize($input);

        if ($this->testing)
        {
            $url = $this->route->getUrlWithPublicAuth('mock_esigner_payment', ['signer' => 'digio']);

            $request['content'] .= '***'.$url.'***';
        }

        return $request;
    }
}
