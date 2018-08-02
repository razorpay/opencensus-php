<?php

namespace RZP\Gateway\Mpi\Blade\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\Mpi\Blade;
use RZP\Tests\Functional\TestCase;

class Gateway extends Blade\Gateway
{
    use Base\Mock\GatewayTrait;

    const ROOT_CERT_FINGERPRINTS = [
        // This is the mastercard root, but we add it
        // in tests because there are edgecases copied
        // from production in tests
        '32dfd35574d8811bb90ebe33846dd3a0b945e0d9',
        // CN = pit-root
        '31c7b33b3585fa84887c3df36b11ac2593bd79b9',
    ];

    protected function validateParesSignature($paresXml)
    {
        return $paresXml;
    }
}
