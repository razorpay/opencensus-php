<?php

namespace RZP\Tests\P2p\Service\UpiAxis\Beneficiary;

use RZP\Models\P2p\Beneficiary;
use RZP\Tests\P2p\Service\UpiAxis\TestCase;

class BeneficiaryTest extends TestCase
{
    public function testValidateVpa()
    {
        $helper = $this->getBeneficiaryHelper();

        $helper->withSchemaValidated();

        $response = $helper->validateVpa();

        $this->assertArraySubset([
            'validated'         => true,
            'type'              => 'vpa',
            'address'           => 'customer@razorhdfc',
            'beneficiary_name'  => 'Razorpay Customer',
        ], $response);
    }

    public function testValidateVpaInvalid()
    {
        $helper = $this->getBeneficiaryHelper();

        $this->mockActionContentFunction([
            Beneficiary\Action::VALIDATE => function(& $content)
            {
                $content['payload']['isCustomerVpaValid'] = false;
                $content['payload']['customerName'] = '';
            }]);

        $helper->withSchemaValidated();

        $response = $helper->validateVpa();

        $this->assertArraySubset([
            'validated'         => false,
            'type'              => 'vpa',
        ], $response);
    }

    public function testValidateVpaOnus()
    {
        $helper = $this->getBeneficiaryHelper();

        $helper->withSchemaValidated();

        $vpa = $this->fixtures->vpa(self::DEVICE_2);

        $response = $helper->validateVpa([
            'username'  => $vpa->getUsername(),
            'handle'    => $vpa->getHandle(),
        ]);

        $this->assertArraySubset([
            'validated'         => true,
            'type'              => 'vpa',
            'id'                => $vpa->getPublicId(),
            'address'           => $vpa->getAddress(),
            'beneficiary_name'  => $vpa->getBeneficiaryName(),
        ], $response);
    }

    public function testValidateVpaExisting()
    {
        $helper = $this->getBeneficiaryHelper();

        $helper->withSchemaValidated();

        $helper->validateVpa();

        $vpa = $this->getDbLastVpa();

        $this->assertTrue($vpa->isBeneficiary());

        // Ensuring case insensitivity on username
        $response = $helper->validateVpa([
            'username' => 'Customer'
        ]);

        $this->assertArraySubset([
            'validated'         => true,
            'type'              => 'vpa',
            'id'                => $vpa->getPublicId(),
            'address'           => $vpa->getAddress(),
            'beneficiary_name'  => $vpa->getBeneficiaryName(),
        ], $response);
    }
}
