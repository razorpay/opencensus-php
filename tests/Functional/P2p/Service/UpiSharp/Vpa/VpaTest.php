<?php

namespace RZP\Tests\P2p\Service\UpiSharp\Vpa;

use RZP\Gateway\P2p\Upi\Mock\Scenario;
use RZP\Http\Controllers\P2p\Requests;
use RZP\Tests\P2p\Service\UpiSharp\TestCase;

class VpaTest extends TestCase
{
    public function testFetchHandles()
    {
        $helper = $this->getVpaHelper();

        $helper->withSchemaValidated();

        $handles = $helper->fetchHandles();

        $this->assertCollection($handles, 5);
    }

    public function testInitiateCreateVpa()
    {
        $helper = $this->getVpaHelper();

        $helper->withSchemaValidated();

        $request = $helper->intiateCreateVpa();

        $baId = $this->fixtures->bank_account->getPublicId();

        $this->assertRequestResponse(
            'redirect',
            [
                'time'  => $this->fixtures->device->getCreatedAt(),
            ],
            $this->expectedCallback(Requests::P2P_CUSTOMER_VPA_CREATE, [
                'username'          => 'random',
                'bank_account_id'   => $baId,
            ]),
            $request);
    }

    public function createVpa()
    {
        $cases = [];

        $cases['Scenario#N0000'] = [Scenario::N0000, null, []];

        $cases['Scenario#VA301'] = [Scenario::VA301, null, []];
        $cases['Scenario#VA302'] = [Scenario::VA302, null, []];
        $cases['Scenario#VA303'] = [Scenario::VA303, null, []];
        $cases['Scenario#VA304'] = [Scenario::VA304, null, []];
        $cases['Scenario#VA305'] = [Scenario::VA305, null, []];

        $cases['Scenario#N0000#Default'] = [
            Scenario::N0000,
            function(VpaTest $test)
            {
                $test->fixtures->vpa->forceDelete();
            },
            [
                'default' => true,
            ]];

        $cases['Scenario#N0000#NoDefault'] = [
            Scenario::N0000,
            function(VpaTest $test)
            {
                $test->fixtures->vpa->setDefault(false)->saveOrFail();
            },
            [
                'default' => true,
            ]];

        return $cases;
    }

    /**
     * @dataProvider createVpa
     */
    public function testCreateVpa($scenario, callable $preCallback = null, array $override = [])
    {
        $helper = $this->getVpaHelper();

        if (is_callable($preCallback))
        {
            $preCallback($this, $helper);
        }

        $request = $helper->intiateCreateVpa();

        $helper->withSchemaValidated();

        $helper->setScenarioInContext($scenario);

        $response = $helper->createVpa($request['callback']);

        if ($helper->getScenarioInContext()->isSuccess() === false)
        {
            return;
        }

        $vpa = array_replace_recursive([
            'address'       => 'random@razorsharp',
            'handle'        => 'razorsharp',
            'username'      => 'random',
            'bank_account'  => [
                'id'        => 'ba_ALC01bankAc001',
            ],
            'active'        => true,
            'validated'     => true,
            'verified'      => false,
            'default'       => false,
        ], $override);

        $this->assertArraySubset($vpa, $response, true);
    }

    public function testFetchVpa()
    {
        $vpaId = $this->fixtures->vpa->getPublicId();

        $helper = $this->getVpaHelper();

        $helper->withSchemaValidated();

        $vpa = $helper->fetchVpa($vpaId);

        $this->assertArraySubset([
            'id'            => 'vpa_ALC01custVpa01',
            'address'       => 'ALC01custVpa01@razorsharp',
            'handle'        => 'razorsharp',
            'username'      => 'ALC01custVpa01',
            'bank_account'  => [
                'id'        => 'ba_ALC01bankAc001',
            ],
            'active'        => true,
            'validated'     => true,
            'verified'      => true,
            'default'       => true,
        ], $vpa, true);
    }

    public function testFetchAllVpa()
    {
        $helper = $this->getVpaHelper();

        $helper->withSchemaValidated();

        $collection = $helper->fetchAllVpa();

        $this->assertCollection($collection, 1, [
            [
                'id'            => 'vpa_ALC01custVpa01',
                'address'       => 'ALC01custVpa01@razorsharp',
                'handle'        => 'razorsharp',
                'username'      => 'ALC01custVpa01',
                'bank_account'  => [
                    'id'        => 'ba_ALC01bankAc001',
                ],
                'active'        => true,
                'validated'     => true,
                'verified'      => true,
                'default'       => true,
            ]
        ]);
    }

    public function setDefault()
    {
        $cases = [];

        $cases['Scenario#N0000'] = [Scenario::N0000];
        $cases['Scenario#VA401'] = [Scenario::VA401];

        return $cases;
    }

    /**
     * @dataProvider setDefault
     */
    public function testSetDefault($scenario)
    {
        $default = $this->fixtures->vpa;

        $vpa = $this->fixtures->createVpa([
            'default' => false,
        ]);

        $this->assertTrue($default->isDefault());

        $helper = $this->getVpaHelper();

        $helper->withSchemaValidated();

        $helper->setScenarioInContext($scenario);

        $response = $helper->setDefault($vpa->getPublicId());

        if ($helper->getScenarioInContext()->isSuccess() === false)
        {
            return;
        }

        $this->assertTrue($response['default']);

        $this->assertTrue($vpa->refresh()->isDefault());

        $this->assertFalse($default->refresh()->isDefault());
    }

    public function assignBankAccount()
    {
        $cases = [];

        $cases['Scenario#N0000'] = [Scenario::N0000];
        $cases['Scenario#VA501'] = [Scenario::VA501];

        return $cases;
    }

    /**
     * @dataProvider assignBankAccount
     */
    public function testAssignBankAccount($scenario)
    {
        $this->fixtures->vpa->setBankAccountId('NA')->save();

        $vpaId = $this->fixtures->vpa->getPublicId();

        $bankAccountId = $this->fixtures->bank_account->getPublicId();

        $helper = $this->getVpaHelper();

        $helper->withSchemaValidated();

        $helper->setScenarioInContext($scenario);

        $helper->assignBankAccount($vpaId, $bankAccountId);

        if ($helper->getScenarioInContext()->isSuccess() === false)
        {
            return;
        }

        $this->assertSame($this->fixtures->bank_account->getId(),
                          $this->fixtures->vpa->reload()->getBankAccountId());
    }

    public function testInitiateCheckAvailability()
    {
        $helper = $this->getVpaHelper();

        $helper->withSchemaValidated();

        $request = $helper->initiateCheckVpaAvailable();

        $this->assertRequestResponse(
            'redirect',
            [
                'time'  => $this->fixtures->device->getCreatedAt(),
            ],
            $this->expectedCallback(Requests::P2P_CUSTOMER_VPA_CHECK_AVAILABILITY, [
                'username'          => 'random',
            ]),
            $request);
    }

    public function checkAvailability()
    {
        $cases = [];

        $cases['Scenario#N0000'] = [Scenario::N0000];

        $cases['Scenario#VA201'] = [Scenario::VA201];
        $cases['Scenario#VA202'] = [Scenario::VA202];
        $cases['Scenario#VA203'] = [Scenario::VA203];
        $cases['Scenario#VA204'] = [Scenario::VA204];
        $cases['Scenario#VA205'] = [Scenario::VA205];

        return $cases;
    }

    /**
     * @dataProvider checkAvailability
     */
    public function testCheckAvailability($scenario)
    {
        $helper = $this->getVpaHelper();

        $request = $helper->initiateCheckVpaAvailable();

        $helper->withSchemaValidated();

        $helper->setScenarioInContext($scenario);

        $response = $helper->checkAvailability($request['callback']);

        if ($helper->getScenarioInContext()->isSuccess() === false)
        {
            return;
        }

        $this->assertArraySubset([
            'available' => true,
            'username'  => 'random',
            'handle'    => 'razorsharp'
        ], $response, true);
    }

    public function deleteVpa()
    {
        $cases = [];

        $cases['Scenario#N0000'] = [Scenario::N0000];
        $cases['Scenario#VA601'] = [Scenario::VA601];

        return $cases;
    }

    /**
     * @dataProvider deleteVpa
     */
    public function testDeleteVpa($scenario)
    {
        $vpa = $this->fixtures->createVpa([
            'default' => false,
        ]);

        $helper = $this->getVpaHelper();

        $helper->withSchemaValidated();

        $helper->setScenarioInContext($scenario);

        $response = $helper->deleteVpa($vpa->getPublicId());

        if ($helper->getScenarioInContext()->isSuccess() === false)
        {
            return;
        }

        $this->assertSame([
            'success' => true,
            'id'        => $vpa->getPublicId(),
        ], $response);

        $this->assertTrue($vpa->refresh()->trashed());
    }
}
