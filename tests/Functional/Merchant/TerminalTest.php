<?php

namespace RZP\Tests\Functional\Merchant;

use Event;
use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Cache\Events\KeyForgotten;

use RZP\Models\Terminal;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use \RZP\Models\Terminal\Shared;
use RZP\Exception;

class TerminalTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/TerminalData.php';

        parent::setUp();

        $this->ba->adminAuth();
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

    public function testAssignHitachiTerminal()
    {
        $merchant = $this->fixtures->create('merchant');

        $url = '/merchants/'.$merchant->getKey().'/terminals';
        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testAssignBankAccountTerminal()
    {
        $merchant = $this->fixtures->create('merchant', ['id' => '100001Razorpay']);

        $url = '/merchants/'.$merchant->getKey().'/terminals';
        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testBankAccountTerminalValidationRules()
    {
        $merchant = $this->fixtures->create('merchant');

        $url = '/merchants/'.$merchant->getKey().'/terminals';
        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreateSameRootBankAccountTerminalWithDifferentMerchant()
    {
        $merchant = $this->fixtures->create('merchant', ['id' => '100001Razorpay']);

        $attributes = [
            'merchant_id' => $merchant->getKey(),
            'used'        => true,
        ];
        // Create a numeric terminal
        $this->fixtures->create(
            'terminal:bank_account_terminal', $attributes);

        $merchant = $this->fixtures->create('merchant', ['id' => '100002Razorpay']);

        // Now try creating another numeric terminal for different merchant and gateway
        $url = '/merchants/'.$merchant->getKey().'/terminals';
        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testEditUsedBankAccountTerminal()
    {
        $merchant = $this->fixtures->create('merchant', ['id' => '100001Razorpay']);

        $attributes = [
            'merchant_id'   => $merchant->getKey(),
            'used'          => true,
        ];
        $terminal   = $this->fixtures->create(
            'terminal:bank_account_terminal', $attributes);

        $this->testData[__FUNCTION__]['request']['url'] = '/terminals/'.$terminal['id'];;

        $this->startTest();
    }

    public function testEditBankAccountTerminal()
    {
        $merchant = $this->fixtures->create('merchant', ['id' => '100001Razorpay']);

        $attributes = [
            'merchant_id'   => $merchant->getKey(),
            'used'          => false,
        ];

        $terminal   = $this->fixtures->create(
            'terminal:bank_account_terminal', $attributes);

        $this->testData[__FUNCTION__]['request']['url'] = '/terminals/'.$terminal['id'];;

        $this->startTest();
    }

    public function testAssignDifferentTypeBankAccountTerminalForSameMerchant()
    {
        $merchant = $this->fixtures->create('merchant', ['id' => '100001Razorpay']);

        $attributes = [
            'merchant_id' => $merchant->getKey(),
            'used'        => true,
        ];
        // Create a numeric terminal
        $this->fixtures->create(
            'terminal:bank_account_terminal', $attributes);

        // Now try creating an alpha numeric terminal for same merchant and gateway
        $url = '/merchants/'.$merchant->getKey().'/terminals';
        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testAssignSameTypeBankAccountTerminalForSameMerchant()
    {
        $merchant = $this->fixtures->create('merchant', ['id' => '100001Razorpay']);

        $attributes = [
            'merchant_id' => $merchant->getKey(),
            'used'        => true,
        ];
        // Create a numeric terminal
        $this->fixtures->create(
            'terminal:bank_account_terminal', $attributes);

        // Now try creating a numeric terminal again for same merchant and gateway with different root and handle
        $url = '/merchants/'.$merchant->getKey().'/terminals';
        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testAssignSameRootAndSameTypeBankAccountTerminalAfterSharedTerminal()
    {
        $merchant = $this->fixtures->create('merchant', ['id' => '100001Razorpay']);

        $attributes = [
            'shared'      => 1,
            'used'        => true,
        ];
        // Create a numeric terminal with shared merchant
        $this->fixtures->create(
            'terminal:bank_account_terminal', $attributes);

        // Now try creating the same numeric terminal against the merchant
        $url = '/merchants/'.$merchant->getKey().'/terminals';
        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testAssignHitachiTerminalWithInvalidGatewayAcquirer()
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

    public function testAddBharatQrTerminal()
    {
        $this->startTest();
    }

    public function testAddUpiMindgateBharatQrTerminal()
    {
        $this->startTest();
    }

    public function testAssignHitachiBharatQrTerminal()
    {
        $this->startTest();
    }

    public function testAddBharatQrTerminalWithExpected()
    {
        $request = $this->testData['testAddBharatQrTerminal'];

        $request['request']['content']['expected'] = true;

        $this->startTest($request);
    }

    public function testReassignBharatQrTerminal()
    {
        $this->fixtures->create('terminal:bharat_qr_terminal');

        $this->startTest();
    }

    public function testAddUpiBharatQrTerminal()
    {
        $this->startTest();
    }

    public function testReassignUpiBharatQrTerminal()
    {
        $this->fixtures->create('terminal:bharat_qr_terminal_upi');

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

    public function testCreateHitachiDebitRecurringTerminal()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreateUpiCollectTerminal()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreateGooglePayTerminal()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreateTpvTerminalWithInvalidMethod()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreateTpvTerminal()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreatePaytmTerminal()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreateDirectSettlemtTerminal()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreateDirectSettlementTerminalValidationFailure()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreateDirectSettlemtTerminalFailure()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreateCardlessEmiTerminal()
    {
        $url = '/merchants/10000000000000/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreatePayLaterTerminal()
    {
        $url = '/merchants/10000000000000/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreateUpiAirtelTerminal()
    {
        $url = '/merchants/10000000000000/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testDeleteTerminal()
    {
        $merchant = $this->fixtures
                         ->create('merchant_fluid', ['id' => '10abcdefghsdfs'])
                         ->addTerminal('atom', ['id' => 'testatomrandom'])
                         ->get();

        $this->ba->getAdmin()->merchants()->attach($merchant);

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

    public function testDeleteTerminalWithSubMerchantAssigned()
    {
        $terminal = $this->fixtures->create(
            'terminal:shared_hdfc_terminal', ['gateway_recon_password' => 'boo']);

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $terminal->merchants()->attach('10000000000000');

        $payment = $this->defaultAuthPayment();

        $t = $this->deleteTerminal2('1000HdfcShared');
        $this->assertNotNull($t['deleted_at']);

        $dt = Terminal\Entity::withTrashed()->findOrFail($t['id']);

        $this->assertEquals(0, $dt->merchants->count());
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

    public function testEditHitachiDebitRecurringTerminal()
    {
        $attributes = [
            'used' => true,
            'type' => [
                'recurring_non_3ds' => '1',
                'recurring_3ds'     => '1',
            ],
        ];
        $terminal   = $this->fixtures->create(
            'terminal:hitachi_recurring_terminal_with_both_recurring_types', $attributes);

        $tid = $terminal['id'];

        $data = [
            'gateway' => 'hitachi',
            'type'    => [
                'recurring_non_3ds' => '1',
                'recurring_3ds'     => '1',
                'debit_recurring'   => '1',
            ],
        ];

        $content = $this->editTerminal($tid, $data);

        $this->assertEquals($content['type'], ['recurring_3ds', 'recurring_non_3ds', 'debit_recurring']);
    }

    public function testEditCollectTerminal()
    {
        $this->fixtures->create('terminal:shared_upi_icici_terminal', ['used' => true]);

        $tid = Terminal\Shared::UPI_ICICI_RAZORPAY_TERMINAL;

        $data = [
            'gateway'                   => 'upi_icici',
            'type'                      => [
                'collect' => '1',
            ],
        ];

        $content = $this->editTerminal($tid, $data);

        $this->assertEquals($content['type'], ['non_recurring', 'collect']);
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

    public function testEditWalletAirtelmoneyTerminalWithNotRequiredFields()
    {
        $terminal = $this->fixtures->create('terminal:shared_airtelmoney_terminal');

        $tid = $terminal['id'];

        $data = [
            'gateway_secure_code'       => 'test_random_id',
        ];

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function() use ($tid, $data)
        {
            $this->editTerminal($tid, $data);
        });
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

    public function testAddAmazonPayTerminal()
    {
        $this->startTest();
    }

    public function testAssignIsgBharatQrTerminal()
    {
        $this->startTest();
    }

    public function testAddIsgBharatQrTerminalWithExpected()
    {
        $request = $this->testData['testAssignIsgBharatQrTerminal'];

        $request['request']['content']['expected'] = true;

        $this->startTest($request);
    }

    public function testAddIsgBharatQrTerminalFailed()
    {
        $this->startTest();
    }

    public function testAddHulkTerminalWithAppAuth()
    {
        $this->startTest();
    }

    public function testGetTerminalBanks()
    {
        $terminal = $this->fixtures->create('terminal:shared_atom_terminal');

        $url = '/terminals/' . $terminal['id'] . '/banks';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testGetTerminalBanksForBilldesk()
    {
        $terminal = $this->fixtures->create('terminal:shared_billdesk_terminal');

        $url = '/terminals/' . $terminal['id'] . '/banks';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testGetTerminalBanksForDirectNetbankingTerminal()
    {
        $terminal = $this->fixtures->create('terminal:shared_netbanking_hdfc_terminal');

        $url = '/terminals/' . $terminal['id'] . '/banks';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testGetTpvTerminalBanks()
    {
        $terminal = $this->fixtures->create('terminal:shared_atom_tpv_terminal');

        $url = '/terminals/' . $terminal['id'] . '/banks';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testGetCorpTerminalBanks()
    {
        $terminal = $this->fixtures->create('terminal:shared_netbanking_axis_corp_terminal');

        $url = '/terminals/' . $terminal['id'] . '/banks';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testGetTerminalBanksForNonNetbankingTerminal()
    {
        $terminal = $this->fixtures->create('terminal:shared_fss_terminal');

        $url = '/terminals/' . $terminal['id'] . '/banks';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testSetBanksForTerminal()
    {
        $terminal = $this->fixtures->create('terminal:shared_atom_terminal');

        $url = '/terminals/' . $terminal['id'] . '/banks';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testSetBanksForDirectNetbankingTerminal()
    {
        $terminal = $this->fixtures->create('terminal:shared_netbanking_hdfc_terminal');

        $url = '/terminals/' . $terminal['id'] . '/banks';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testSetUnsupportedBankForTerminal()
    {
        $terminal = $this->fixtures->create('terminal:shared_atom_terminal');

        $url = '/terminals/' . $terminal['id'] . '/banks';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testSetBanksForNonNetbankingGateway()
    {
        $terminal = $this->fixtures->create('terminal:shared_fss_terminal');

        $url = '/terminals/' . $terminal['id'] . '/banks';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testSetBanksWithIncorrectInput()
    {
        $terminal = $this->fixtures->create('terminal:shared_atom_terminal');

        $url = '/terminals/' . $terminal['id'] . '/banks';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testCreateAllahabadTpvTerminal()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testAssignUpiYesbankTerminal()
    {
        $merchant = $this->fixtures->create('merchant');

        $url = '/merchants/'.$merchant->getKey().'/terminals';
        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testBulkTerminalUpdateForBankUnsupportedMethod()
    {
        $url = '/terminals/banks/bulk';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->expectException(Exception\BadRequestValidationFailureException::class);

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testBulkTerminalUpdateForBankWithTerminalNotExist()
    {
        $url = '/terminals/banks/bulk';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testBulkTerminalUpdateForBankRemoveMethod()
    {
        $url = '/terminals/banks/bulk';

        $this->fixtures->create('terminal:multiple_netbanking_terminals');

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->adminAuth();

        $response = $this->startTest();

        $atomTermId = Shared::ATOM_RAZORPAY_TERMINAL;
        $ebsTermId = Shared::EBS_RAZORPAY_TERMINAL;

        $this->assertArrayNotHasKey('ANDB', $response[$atomTermId]);

        $this->assertArrayNotHasKey('ANDB', $response[$ebsTermId]);
    }

    public function testBulkTerminalUpdateForBankAddMethod()
    {
        $url = '/terminals/banks/bulk';

        $atomTermId = Shared::ATOM_RAZORPAY_TERMINAL;

        $ebsTermId = Shared::EBS_RAZORPAY_TERMINAL;

        $this->fixtures->create('terminal:multiple_netbanking_terminals');
        $this->fixtures->terminal->setEnabledBanks($atomTermId);
        $this->fixtures->terminal->setEnabledBanks($ebsTermId);

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->adminAuth();

        $this->startTest();

    }

    public function testBulkTerminalUpdateForUnsupportedBankAddMethod()
    {
        $url = '/terminals/banks/bulk';

        $atomTermId = Shared::ATOM_RAZORPAY_TERMINAL;

        $ebsTermId = Shared::EBS_RAZORPAY_TERMINAL;

        $this->fixtures->create('terminal:multiple_netbanking_terminals');
        $this->fixtures->terminal->setEnabledBanks($atomTermId);
        $this->fixtures->terminal->setEnabledBanks($ebsTermId);

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->adminAuth();

        $this->startTest();

    }

    public function testBulkTerminalUpdateForInvalidBankCode()
    {
        $url = '/terminals/banks/bulk';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->expectException(Exception\BadRequestValidationFailureException::class);

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testQueryCacheforTerminals()
    {
        config(['app.query_cache.mock' => false]);

        Event::fake(false);

        $url = '/merchants/10000000000000/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest($this->testData);

        Event::assertDispatched(KeyForgotten::class);

        $this->defaultAuthPayment();

        Event::assertDispatched(CacheMissed::class, function ($e)
        {
            foreach ($e->tags as $tag)
            {
                if (starts_with($tag, 'terminal') === true)
                {
                    $this->assertEquals('terminal_10000000000000', $tag);
                }
            }

            return true;
        });

        Event::assertDispatched(KeyWritten::class, function ($e)
        {
            foreach ($e->tags as $tag)
            {
                if (starts_with($tag, 'terminal') === true)
                {
                    $this->assertEquals('terminal_10000000000000', $tag);
                }
            }

            return true;
        });

        Event::assertNotDispatched(CacheHit::class);

        $this->defaultAuthPayment();

        Event::assertDispatched(CacheHit::class, function ($e)
        {
            foreach ($e->tags as $tag)
            {
                if (starts_with($tag, 'terminal') === true)
                {
                    $this->assertEquals('terminal_10000000000000', $tag);
                }
            }

            return true;
        });
    }

    public function testCreateWalletPhonepeTerminal()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreateNetbankingSibTerminal()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreateNetbankingYesbTerminal()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreateBilldeskTerminal()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testEditBilledeskTerminal()
    {
        $terminal = $this->fixtures->create('terminal:shared_billdesk_terminal');

        $tid = $terminal['id'];

        $data = [
            'gateway_access_code'       => 'random'
        ];

        $this->editTerminal($tid, $data);

        $terminal = $this->getEntityById('terminal', $tid, true);

        $this->assertEquals('random', $terminal['gateway_access_code']);
    }

    public function testCreateNetbankingCanaraTerminal()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreateWorldlineTerminal()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }
}
