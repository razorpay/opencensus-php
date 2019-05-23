<?php

namespace RZP\Tests\P2p\Service\UpiSharp\Beneficiary;

use RZP\Tests\P2p\Service\UpiSharp\TestCase;

class BeneficiaryTest extends TestCase
{
    public function testValidateVpa()
    {
        $helper = $this->getBeneficiaryHelper();

        $helper->withSchemaValidated();

        $helper->validateVpa();
    }

    public function testValidateBankAccount()
    {
        $helper = $this->getBeneficiaryHelper();

        $helper->withSchemaValidated();

        $helper->validateBankAccount();
    }

    public function testCreate()
    {
        $helper = $this->getBeneficiaryHelper();

        $vpa = $helper->validateVpa();

        $helper->withSchemaValidated();

        $helper->create($vpa);
    }

    public function testFetch()
    {
        $helper = $this->getBeneficiaryHelper();

        $helper->withSchemaValidated();

        $helper->fetch();
    }
}
