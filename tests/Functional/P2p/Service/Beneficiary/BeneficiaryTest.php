<?php

namespace RZP\Tests\P2p\Service\Beneficiary;

use RZP\Tests\P2p\Service\TestCase;

class BeneficiaryTest extends TestCase
{
    public function testCreate()
    {
        $helper = $this->getBeneficiaryHelper();

        $helper->withSchemaValidated();

        $helper->create();
    }

    public function testValidate()
    {
        $helper = $this->getBeneficiaryHelper();

        $helper->withSchemaValidated();

        $helper->validate();
    }

    public function testFetch()
    {
        $helper = $this->getBeneficiaryHelper();

        $helper->withSchemaValidated();

        $helper->fetch();
    }
}
