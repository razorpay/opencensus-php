<?php

namespace RZP\Tests\P2p\Service\UpiAxis\Beneficiary;

use RZP\Models\P2p\Beneficiary;
use RZP\Tests\P2p\Service\UpiAxis\TestCase;

class BeneficiaryMerchantTest extends TestCase
{
    public function testValidateVpa()
    {
        $helper = $this->getBeneficiaryHelper();

        $helper->setMerchantOnAuth(true);

        $helper->withSchemaValidated();

        $response = $helper->validateVpa();

        $this->assertArraySubset([
            'validated'         => true,
            'type'              => 'vpa',
            'address'           => 'customer@razorhdfc',
            'beneficiary_name'  => 'Razorpay Customer',
        ], $response);
    }
}
