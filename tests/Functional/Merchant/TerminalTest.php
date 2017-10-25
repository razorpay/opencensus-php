<?php

namespace RZP\Tests\Functional\Merchant;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class TerminalTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/TerminalData.php';

        parent::setUp();

        $this->ba->appAuth();
    }

    public function testAssignTerminal()
    {
        $merchant = $this->fixtures->create('merchant');

        $url = '/merchants/'.$merchant->getKey().'/terminals';
        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testAssignTerminalWithInvalidGatewayAcquirer()
    {
        $merchant = $this->fixtures->create('merchant');

        $url = '/merchants/'.$merchant->getKey().'/terminals';
        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testAddEmiTerminal()
    {
        $merchant = $this->getEntityById('merchant', '100000Razorpay', true);

        $url = '/merchants/'.$merchant['id'].'/terminals';

//        $url = '/merchants/100000Razorpay/terminals';
        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testReassignTerminalForSameGateway()
    {
        $this->startTest();
    }

    public function testAssignTerminalForDifferentGateway()
    {
        $this->startTest();
    }

    public function testCreateTerminalWithNetworkCategory()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreateTerminalWithInvalidNetworkCategory()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testDeleteTerminal()
    {
        $merchant = $this->fixtures
                         ->create('merchant_fluid', ['id' => '10abcdefghsdfs'])
                         ->addTerminal('atom', ['id' => 'testatomrandom'])
                         ->get();

        $content = $this->startTest();
    }

    public function testRestoreTerminal()
    {
        $terminal = $this->fixtures->create(
            'terminal:shared_hdfc_terminal', ['gateway_recon_password' => 'boo']);

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $payment = $this->defaultAuthPayment();

        $t = $this->deleteTerminal2('1000HdfcShared');
        $this->assertNotNull($t['deleted_at']);

        $t = $this->restoreTerminal('1000HdfcShared');
        $this->assertNull($t['deleted_at']);
    }

    public function testCopyTerminal()
    {
        $this->markTestSkipped();
        $terminal = $this->fixtures->create('terminal:ebs_terminal', ['used' => true]);

        $tid = $terminal['id'];
        $mid = $terminal['merchant_id'];

        $input = ['merchant_ids' => ['100000Razorpay']];

        $response = $this->copyTerminal($tid, $mid, $input);

        $newTerminal = $this->getEntityById('terminal', $response[0]['terminal'], true);

        $oldTerminal = $terminal->toArray();

        $unsetKeys = ['id', 'created_at', 'updated_at', 'merchant_id'];
        foreach ($unsetKeys as $key)
        {
            unset($oldTerminal[$key]);
        }

        $this->assertEquals('100000Razorpay', $newTerminal['merchant_id']);
        $this->assertEquals(false, $newTerminal['used']);
        $this->assertArraySelectiveEquals($oldTerminal, $newTerminal);
    }

    public function testCopySharedTerminal()
    {
        $this->markTestSkipped();
        $terminal = $this->fixtures->create('terminal:shared_axis_terminal', ['used' => true]);

        $tid = $terminal['id'];
        $mid = $terminal['merchant_id'];

        $input = ['merchant_ids' => ['100000Razorpay']];

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($tid, $mid, $input)
        {
            $this->copyTerminal($tid, $mid, $input);
        });
    }

    public function testEditAxisMigsTerminal()
    {
        $terminal = $this->fixtures->create(
            'terminal:shared_axis_terminal', ['used' => true]);

        $tid = $terminal['id'];

        $data = ['gateway_terminal_id' => 'random', 'gateway_terminal_password' => 'random'];

        $content = $this->editTerminal($tid, $data);

        $this->assertEquals($content['gateway_terminal_id'], 'random');
    }

    public function testEditHdfcTerminal()
    {
        $terminal = $this->fixtures->create(
            'terminal:shared_hdfc_terminal', ['used' => true, 'gateway_recon_password' => 'boo']);

        $tid = $terminal['id'];

        $data = ['gateway_recon_password' => 'random'];

        $content = $this->editTerminal($tid, $data);

        $this->assertEquals('random', $terminal->reload()->getGatewayReconPassword());
    }

    //For testing edit terminals with zero used count
    //but some authorised payments
    public function testEditHdfcTerminalWithPayments()
    {
        $terminal = $this->fixtures->create(
            'terminal:shared_hdfc_terminal', ['gateway_recon_password' => 'boo']);

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $payment = $this->defaultAuthPayment();

        $tid = $terminal['id'];

        $data = array('gateway_recon_password' => 'random');

        $content = $this->editTerminal($tid, $data);

        $this->assertEquals('random', $terminal->reload()->getGatewayReconPassword());
    }

    public function testEditUpiIciciTerminal()
    {
        $terminal = $this->fixtures->create(
            'terminal:shared_upi_icici_terminal', ['used' => true, 'upi' => 0]);

        $tid = $terminal['id'];

        $data = array('upi' => '1');

        $content = $this->editTerminal($tid, $data);

        $this->assertEquals(true, $terminal->reload()->upi);
    }

    public function testToggleTerminal()
    {
        $terminal = $this->fixtures->create(
            'terminal:shared_axis_terminal', ['used' => true, 'enabled' => '1']);

        $tid = $terminal['id'];

        $url = '/terminals/'.$tid.'/toggle';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();

    }

    public function testTerminalModeDual()
    {
        $this->startTest();
    }

    public function testTerminalModePurchase()
    {
        $this->startTest();
    }

    public function testTerminalModeAuthCapture()
    {
        $this->startTest();
    }

    public function testTerminalModeDualFailure()
    {
        $this->startTest();
    }

    public function testTerminalModePurchaseFailure()
    {
        $this->startTest();
    }

    public function testTerminalModeAuthCaptureFailure()
    {
        $this->startTest();
    }

    public function testTerminalTypeRecurringNon3DS()
    {
        $this->startTest();
    }

    public function testTerminalTypeRecurring3DS()
    {
        $this->startTest();
    }

    public function testTerminalTypeRecurringBoth()
    {
        $this->startTest();
    }

    public function testTerminalTypeIvr()
    {
        $this->startTest();
    }

    public function testEditTerminalTypeRecurringBoth()
    {
        $terminal = $this->fixtures->create(
            'terminal:shared_hdfc_terminal', ['used' => 1]);

        $tid = $terminal['id'];

        $data = [
            'type' => [
                'non_recurring'     => '0',
                'recurring_3ds'     => '1',
                'recurring_non_3ds' => '1',
            ]
        ];

        $content = $this->editTerminal($tid, $data);

        $types = [
            'recurring_3ds',
            'recurring_non_3ds'
        ];

        $this->assertEquals($types, $content['type']);
    }

    public function testTerminalCheckAutoDisable()
    {
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $terminal = $this->fixtures->create(
                        'terminal:shared_hdfc_terminal',
                        [
                            'id'          => '12HDFCTerminal',
                            'merchant_id' => '10000000000000'
                        ]);

        $this->mockServerContentFunction(function(&$content, $action)
        {
            if ($action === 'authorize')
            {
                $content['result'] = 'GW00154';
            }
        }, 'hdfc');

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function()
        {
            $this->defaultAuthPayment();
        });

        $this->assertFalse($terminal->reload()->isEnabled());
    }
}
