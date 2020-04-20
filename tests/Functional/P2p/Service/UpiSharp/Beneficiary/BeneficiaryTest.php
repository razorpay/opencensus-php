<?php

namespace RZP\Tests\P2p\Service\UpiSharp\Beneficiary;

use RZP\Gateway\P2p\Upi\Mock\Scenario;
use RZP\Tests\P2p\Service\UpiSharp\TestCase;

class BeneficiaryTest extends TestCase
{
    public function validateVpa()
    {
        $cases['Scenario#N0000'] = [Scenario::N0000];

        $cases['Scenario#VA701'] = [Scenario::VA701];
        $cases['Scenario#VA702'] = [Scenario::VA702];
        // TODO: Verified needs to be true
        $cases['Scenario#VA703'] = [Scenario::VA703, false];
        $cases['Scenario#VA704'] = [Scenario::VA704, false, ' Long(Name)'];

        return $cases;
    }

    /**
     * @dataProvider validateVpa
     */
    public function testValidateVpa($scenario, $verified = false, $name = '')
    {
        $helper = $this->getBeneficiaryHelper();

        $helper->withSchemaValidated();

        $helper->setScenarioInContext($scenario);

        $beneficiary = $helper->validateVpa();

        if ($helper->getScenarioInContext()->isSuccess() === false)
        {
            return;
        }

        $vpa = $this->getDbLastVpa();

        $this->assertTrue($vpa->isBeneficiary());
        $this->assertTrue($vpa->isValidated());
        $this->assertTrue($vpa->isVerified() === $verified);

        $this->assertArraySubset([
            'validated'         => true,
            'type'              => 'vpa',
            'id'                => $vpa->getPublicId(),
            'address'           => 'customer@razorhdfc',
            'beneficiary_name'  => 'Razorpay Customer' . $name,
        ], $beneficiary);
    }

    public function testValidateBankAccount()
    {
        $helper = $this->getBeneficiaryHelper();

        $helper->withSchemaValidated();

        $beneficiary = $helper->validateBankAccount();

        $bankAccount = $this->getDbLastBankAccount();

        $this->assertTrue($bankAccount->isBeneficiary());

        $this->assertArraySubset([
            'validated'         => true,
            'type'              => 'bank_account',
            'id'                => $bankAccount->getPublicId(),
            'address'           => '987654321000@HDFC0000001.ifsc.npci',
            'beneficiary_name'  => 'Razorpay Customer',
        ], $beneficiary);
    }

    public function testCreate()
    {
        $helper = $this->getBeneficiaryHelper();

        $beneficiary = $helper->validateVpa();

        $helper->withSchemaValidated();

        $beneficiary = $helper->create($beneficiary);

        $vpa = $this->getDbLastVpa();

        $this->assertTrue($vpa->isBeneficiary());
        $this->assertTrue($vpa->isValidated());
        $this->assertFalse($vpa->isVerified());

        $this->assertArraySubset([
            'validated'         => true,
            'type'              => 'vpa',
            'id'                => $vpa->getPublicId(),
            'address'           => 'customer@razorhdfc',
            'beneficiary_name'  => 'Razorpay Customer',
        ], $beneficiary);
    }

    public function testFetchAll()
    {
        $helper = $this->getBeneficiaryHelper();

        $beneficiary = $helper->validateVpa();
        $vpa = $helper->create($beneficiary);

        $beneficiary = $helper->validateBankAccount();
        $bankAccount = $helper->create($beneficiary);

        // TODO: Enable the schema validation, fix response
        //$helper->withSchemaValidated();

        $collection = $helper->fetch();

        $this->assertCollection($collection, 2, [
            [
                'validated'         => true,
                'type'              => 'bank_account',
                'id'                => $bankAccount['id'],
                'address'           => '987654321000@HDFC0000001.ifsc.npci',
                'beneficiary_name'  => 'Razorpay Customer',
            ],
            [
                'validated'         => true,
                'type'              => 'vpa',
                'id'                => $vpa['id'],
                'address'           => 'customer@razorhdfc',
                'beneficiary_name'  => 'Razorpay Customer',
            ]
        ]);
    }

    public function unblockBeneficiary()
    {
        $cases = [];

        $cases['Scenario#N0000'] = [Scenario::N0000];

        $cases['Scenario#VA801'] = [Scenario::VA801];
        $cases['Scenario#VA802'] = [Scenario::VA802];

        return $cases;
    }

    /**
     * @dataProvider unblockBeneficiary
     */
    public function testUnblockBeneficiary($scenario)
    {
        $helper = $this->getBeneficiaryHelper();

        $helper->withSchemaValidated();

        $helper->setScenarioInContext($scenario);

        $beneficiary = $helper->handle();

        if ($helper->getScenarioInContext()->isSuccess() === false)
        {
            return;
        }

        $this->assertSame([
            'username'          => 'customer',
            'handle'            => 'testpsp',
            'blocked'           => false,
            'spammed'           => false,
            'blocked_at'        => null,
            'entity'            => 'vpa',
            'address'           => 'customer@testpsp',
        ], $beneficiary);
    }

    public function fetchBlockedBeneficiaries()
    {
        $cases = [];

        // Default is 102, which will be picked for 000
        $cases['Scenario#N0000#000'] = [Scenario::N0000, '000', 2, [
            ['username' => 'blocked.0', 'spammed' => true],
            ['username' => 'blocked.1', 'spammed' => true],
        ]];

        // Failure Scenario
        $cases['Scenario#VA901#000'] = [Scenario::VA901];
        $cases['Scenario#VA902#000'] = [Scenario::VA902];

        // TODO: Verified needs to be true
        $cases['Scenario#VA903#003'] = [Scenario::VA903, '003', 3, [
            ['username' => 'blocked.0', 'spammed' => false],
            ['username' => 'blocked.1', 'spammed' => false],
            ['username' => 'blocked.2', 'spammed' => false],
        ]];

        $cases['Scenario#VA903#103'] = [Scenario::VA903, '103', 3, [
            ['username' => 'blocked.0', 'spammed' => true],
            ['username' => 'blocked.1', 'spammed' => true],
            ['username' => 'blocked.2', 'spammed' => true],
        ]];

        $cases['Scenario#VA903#303'] = [Scenario::VA903, '303', 3, [
            ['username' => 'very.long.blocked.0', 'spammed' => true],
            ['username' => 'very.long.blocked.1', 'spammed' => true],
            ['username' => 'very.long.blocked.2', 'spammed' => true],
        ]];

        return $cases;
    }

    /**
     * @dataProvider fetchBlockedBeneficiaries
     */
    public function testFetchBlockedBeneficiaries($scenario, $sub = '000', $count = 0, $items = [])
    {
        $helper = $this->getBeneficiaryHelper();

        $helper->setScenarioInContext($scenario, $sub);

        $response = $helper->fetchBlocked();

        if ($helper->getScenarioInContext()->isSuccess() === false)
        {
            return;
        }

        $this->assertCollection($response, $count, $items);
    }
}
