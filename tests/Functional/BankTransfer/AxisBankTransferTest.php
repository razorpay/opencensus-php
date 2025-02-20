<?php

namespace Functional\BankTransfer;

use DB;
use Mail;
use Cache;
Use Mockery;
use RZP\Error\ErrorCode;
use RZP\Models\BankTransfer\Service;
use RZP\Models\Feature;
use RZP\Models\Pricing\Fee;
use RZP\Models\Terminal\Type;
use RZP\Models\VirtualAccount;
use RZP\Services\RazorXClient;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Payment\Gateway;
use RZP\Tests\Traits\MocksSplitz;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Models\Admin\Service as AdminService;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Tests\Functional\FundTransfer\AttemptTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Unit\Models\Invoice\Traits\CreatesInvoice;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;
use RZP\Tests\Functional\FundTransfer\AttemptReconcileTrait;
use RZP\Tests\Functional\Helpers\VirtualAccount\VirtualAccountTrait;

class AxisBankTransferTest extends TestCase
{
    use AttemptTrait;
    use CreatesInvoice;
    use FileHandlerTrait;
    use TestsWebhookEvents;
    use DbEntityFetchTrait;
    use VirtualAccountTrait;
    use AttemptReconcileTrait;
    use ReconTrait;
    use MocksSplitz;


    protected $virtualAccountId;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/BankTransferTestData.php';

        parent::setUp();

        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');
        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->fixtures->merchant->addFeatures(['virtual_accounts', 'bharat_qr']);

        $this->fixtures->merchant->createAccount('BankAccountMer');

        $this->fixtures->on('live')->merchant->edit('10000000000000', ['activated' => true, 'live' => true]);
        $this->fixtures->on('live')->merchant->edit('10000000000000', ['pricing_plan_id' => Fee::DEFAULT_PRICING_PLAN_ID]);

        $this->fixtures->on('live')->merchant->edit('BankAccountMer', ['activated' => true, 'live' => true]);
        $this->fixtures->on('live')->merchant->addFeatures(['virtual_accounts'], 'BankAccountMer');
        $this->fixtures->on('live')->merchant->enableMethod('BankAccountMer', 'bank_transfer');
        $this->fixtures->on('live')->merchant->edit('BankAccountMer', ['pricing_plan_id' => Fee::DEFAULT_PRICING_PLAN_ID]);

        $this->createTerminals();

        $this->bankAccount = $this->createVirtualAccount();

        $this->enableRazorXTreatmentForBanKTransferDisableGateway();
    }

    protected function ValidateExceptionThrownDuringCollectxBankTransferProcessing($errorMessage, &$isSuccess): void
    {
        $factoryMock = Mockery::mock('alias:\RZP\Models\BankTransfer\Collectx\Processor\Factory');

        $factoryMock->shouldReceive('getCollectxTransferProcessor')
            ->andReturnUsing(function($input, $provider, $requestPayload) use($errorMessage, &$isSuccess)
            {
                $processorMock = Mockery::mock(\RZP\Models\BankTransfer\Collectx\Processor\BankTransfer::class, [$input, $provider, $requestPayload])
                    ->makePartial();

                $processorMock->shouldAllowMockingProtectedMethods();

                $processorMock->shouldReceive('traceExceptionAndPushUnexpectedPaymentMetric')
                    ->andReturnUsing(function($ex, array $input, string $provider, $method) use ($errorMessage, &$isSuccess) {
                        if ($ex->getMessage() === $errorMessage)
                        {
                            $isSuccess = true;
                        }
                    }
                    )->once();

                return $processorMock;
            });
    }

    protected function validateExceptionThrownDuringCollectxUpiTransferProcessing($errorMessage, &$isSuccess): void
    {
        $factoryMock = Mockery::mock('overload:\RZP\Models\BankTransfer\Collectx\Processor\Factory');

        $factoryMock->shouldReceive('getCollectxTransferProcessor')
            ->andReturnUsing(function($input, $provider, $requestPayload) use($errorMessage, &$isSuccess)
            {
                $processorMock = Mockery::mock(\RZP\Models\BankTransfer\Collectx\Processor\UpiTransfer::class, [$input, $provider, $requestPayload])
                    ->makePartial();

                $processorMock->shouldAllowMockingProtectedMethods();

                $processorMock->shouldReceive('traceExceptionAndPushUnexpectedPaymentMetric')
                    ->andReturnUsing(function($ex, array $input, string $provider, $method) use ($errorMessage, &$isSuccess) {
                        if ($ex->getMessage() === $errorMessage)
                        {
                            $isSuccess = true;
                        }
                    }
                    )->once();

                return $processorMock;
            });
    }

    protected function createTerminals()
    {
        // Creating Fallback Terminals,
        // Fallback Terminals are those terminals which are created with just Root
        // and are assigned to the Shared Merchant to get unexpected payments.
        $terminalAttributes = [ 'id' => 'GENERICBANKACC', 'gateway_merchant_id2' => '', 'enabled' => false ];
        $this->fixtures->on('test')->create('terminal:shared_bank_account_terminal', $terminalAttributes);
        $terminalAttributes = ['id' => 'GENERICABNKACC', 'gateway_merchant_id2' => '', 'enabled' => false];
        $this->fixtures->on('test')->create('terminal:shared_bank_account_terminal_alpha_num', $terminalAttributes);

        $this->fixtures->on('test')->create('terminal:shared_bank_account_terminal');
        $this->fixtures->on('test')->create('terminal:shared_bank_account_terminal_alpha_num');

        $terminalAttributes = [ 'id' => 'GENERICBANKACC', 'gateway_merchant_id2' => '', 'enabled' => false, 'gateway' => Gateway::BT_YESBANK ];
        $this->fixtures->on('live')->create('terminal:shared_bank_account_terminal', $terminalAttributes);

        $terminalAttributes = [ 'gateway' => Gateway::BT_YESBANK ];
        $this->fixtures->on('live')->create('terminal:shared_bank_account_terminal', $terminalAttributes);

        $this->fixtures->on('test')->create('terminal:bharat_qr_terminal');
        $this->fixtures->on('test')->create('terminal:bharat_qr_terminal_upi');

        $this->fixtures->on('live')->create('terminal:bharat_qr_terminal');
        $this->fixtures->on('live')->create('terminal:bharat_qr_terminal_upi');

        $this->fixtures->on('live')->create('terminal:vpa_shared_terminal_icici');

        $this->fixtures->on('test');
    }

    public function enableRazorXTreatmentForBanKTransferDisableGateway()
    {
        $razorx = \Mockery::mock(RazorXClient::class)->makePartial();

        $this->app->instance('razorx', $razorx);

        $razorx->shouldReceive('getTreatment')
            ->andReturnUsing(function (string $id, string $featureFlag, string $mode)
            {
                if ($featureFlag === (RazorxTreatment::BANK_TRANSFER_DISABLE_GATEWAY))
                {
                    return 'on';
                }
                return 'control';
            });
    }

    protected function createVirtualAccount($mode = 'test', $merchantId = '10000000000000', $additionalFields = [])
    {
        $this->ba->privateAuth();

        if ($mode === 'live')
        {
            $this->ba->privateAuth('rzp_live_' . $merchantId);
        }

        $request = array_merge($this->testData[__FUNCTION__], $additionalFields);

        $response = $this->makeRequestAndGetContent($request);

        $this->virtualAccountId = $response['id'];

        $bankAccount = $response['receivers'][0];

        return $bankAccount;
    }

    protected function processBankTransfer($accountNumber, $ifsc, $utr = null, $amount = null, $mode = 'test')
    {
        $this->ba->proxyAuth();

        return $this->processOrNotifyBankTransfer($accountNumber, $ifsc, $utr, $amount, $mode);
    }

    protected function notifyBankTransfer($accountNumber, $ifsc, $utr = null)
    {
        $this->ba->kotakAuth();

        return $this->processOrNotifyBankTransfer($accountNumber, $ifsc, $utr);
    }

    protected function processOrNotifyBankTransfer($accountNumber, $ifsc, $utr, $amount = null, $mode = 'test')
    {
        $request = $this->testData[__FUNCTION__];

        $name = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];

        $request['url'] = $this->testData[$name]['url'];

        $request['content']['payee_account'] = $accountNumber;

        $request['content']['payee_ifsc'] = $ifsc;

        if (isset($this->testData[$name]['content']['payer_ifsc']) === true)
        {
            $request['content']['payer_ifsc'] = $this->testData[$name]['content']['payer_ifsc'];
        }

        $utr = $utr ?: strtoupper(random_alphanum_string(22));

        $request['content']['transaction_id'] = $utr;

        $request['content']['amount'] = $amount ?: 50000;

        if ($mode === 'live')
        {
            $request['url'] = '/ecollect/validate';

            $this->ba->yesbankAuth('live');
        }

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals($utr, $response['transaction_id']);

        return $response;
    }

    protected function createRefund($channel = null)
    {
        $accountNumber = $this->bankAccount['account_number'];
        $ifsc = $this->bankAccount['ifsc'];

        $this->fixtures->merchant->edit('10000000000000', ['channel' => $channel]);

        $response = $this->processBankTransfer($accountNumber, $ifsc);

        $utr = $response['transaction_id'];

        // Customer bank account created
        $bankAccount = $this->getDbLastEntity('bank_account');
        $bankAccount = $bankAccount->toArray();
        $this->assertEquals('HDFC0000001', $bankAccount['ifsc']);
        $this->assertEquals('9876543210123456789', $bankAccount['account_number']);
        $this->assertEquals('Name of account holder', $bankAccount['name']);

        $payment =  $this->getLastEntity('payment', true);

        $this->gateway = $payment['gateway'];

        // For scrooge flow tests
        $ftaData = [
            'is_fta'   => true,
            'fta_data' => [
                'bank_account' => [
                    'account_number'   => $bankAccount['account_number'],
                    'beneficiary_name' => $bankAccount['name'],
                    'ifsc_code'        => $bankAccount['ifsc'],
                ]
            ]
        ];

        $this->refundPayment($payment['id'], 4000000, $ftaData);

        // Payment is refunded
        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(4000000, $payment['amount_refunded']);

        // Refund is created
        $refund = $this->getLastEntity('refund', true);
        $this->assertEquals($payment['id'], $refund['payment_id']);
        $this->assertEquals('created', $refund['status']);
        $this->assertEquals(4000000, $refund['amount']);

        // Transaction is created for refund
        $transaction = $this->getLastEntity('transaction', true);
        $this->assertEquals('refund', $transaction['type']);
        $this->assertEquals($refund['id'], $transaction['entity_id']);

        // Fund transfer attempt created for refund
        $attempt = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertEquals('created', $attempt['status']);
        $this->assertEquals($refund['id'], $attempt['source']);
        $this->assertEquals('10000000000000', $attempt['merchant_id']);
        $this->assertStringEndsWith($utr, $attempt['narration']);
    }

    public function createCollectXVirtualAccount(
        $mode = 'test',
        $merchantID = '10000000000000',
        $receivers = ['bank_account'],
        $gateway = 'axis')
    {
        // enabling collectx feature for the merchant
        $this->fixtures->merchant->addFeatures([Feature\Constants::COLLECTX_ENABLED]);

        (new AdminService())->setConfigKeys([ConfigKey::COLLECTX_SERIES_PREFIX => [
            $merchantID => 'COLLECTX'
        ]]);

        // creating banking balance entity with type direct
        $this->fixtures->create(
            'balance',
            [
                'type'             => 'banking',
                'merchant_id'      => $merchantID,
                'balance'          => 0,
                'account_type'     => 'direct'
            ]);

        // adding minimum fee credit balance for collectx payment check
        $this->fixtures->create('credits', ['merchant_id' => $merchantID, 'value' => 500 , 'type' => 'fee']);

        // creating terminal for the merchant
        if (in_array('bank_account', $receivers)) {
            $bankTransferTerminalAttributes = [
                'id' => '10000000000001',
                'gateway' => "bt_" . $gateway,
                'merchant_id' => $merchantID,
                'gateway_merchant_id' => 'COLLECTX',
                'bank_transfer' => 1,
                'enabled' => 1,
                'type' => [
                    Type::NON_RECURRING => '1',
                    Type::NUMERIC_ACCOUNT => '1',
                    Type::DIRECT_SETTLEMENT_WITH_REFUND => '1'
                ],
            ];

            $this->fixtures->on('test')->create('terminal:bank_account_terminal', $bankTransferTerminalAttributes);
        }

        if (in_array('vpa', $receivers))
        {
            $upiTerminalAttributes = [
                'id'                            => '10000000000002',
                'gateway'                       => "upi_".$gateway,
                'merchant_id'                   => $merchantID,
                'gateway_merchant_id'           => 'CXTEST.',
                'upi'                           => 1,
                'virtual_upi_handle'            => $gateway."ltd",
                'enabled'                       => 1,
                'type'                          => [
                    Type::NON_RECURRING                 => '1',
                    Type::NUMERIC_ACCOUNT               => '1',
                    Type::DIRECT_SETTLEMENT_WITH_REFUND => '1'
                ],
            ];

            // creating virtual_vpa_prefix entity for vpa type VA use case
            $this->fixtures->create('virtual_vpa_prefix', [
                'merchant_id'   => $merchantID,
                'prefix'        => 'cxtest.',
                'terminal_id'   => '10000000000002']);

            $this->fixtures->on('test')->create('terminal:bank_account_terminal', $upiTerminalAttributes);
        }

        $request = [
            'url'     => '/virtual_accounts',
            'method'  => 'post',
            'content' => [
                'receivers' => [
                    'types' => $receivers
                ],
            ],
        ];

        $this->ba->privateAuth();

        return $this->makeRequestAndGetContent($request);
    }

    protected function enableSplitzExperiment($experimentName, $id, $variantName = 'enable', $requestData = null): void
    {
        $input = [
            "id" => $id,
            'experiment_name' => $experimentName
        ];

        if ($requestData != null) {
            $input['request_data'] = json_encode($requestData);
        }

        $output = [
            "response" => [
                "variant" => [
                    "name" => $variantName,
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);
    }

    public function testAxisValidationCallbackForCollectxForNewCorpCode()
    {
        $testData = $this->testData['testValidateBankTransferAxis'];

        $response = $this->createCollectXVirtualAccount(receivers: ['bank_account']);

        $beneAccountNo = $response['receivers'][0]['account_number'];

        $testData['request']['content']['Bene_acc_no'] = $beneAccountNo;

        $testData['request']['content']['Corp_code'] = 'MYNS';

        $testData['request']['content']['Req_dt_time'] = date("Y-m-d H:i:s");

        $this->testData[__FUNCTION__] = $testData;

        $merchantID = '10000000000000';

        $this->enableSplitzExperiment(
            experimentName: RazorxTreatment::COLLECTX_AXIS_PAYMENT_TRANSFER_RAMP_UP,
            id: $merchantID,
            requestData: ['id' => $merchantID]);

        $response = $this->startTest();

        $this->assertEquals('S', $response['Stts_flg']);
        $this->assertEquals('000', $response['Err_cd']);
        $this->assertEquals('Success', $response['message']);

        $bankTransferRequest = $this->getLastEntity('bank_transfer_request', true);

        $this->assertNotNull($bankTransferRequest);
        $this->assertFalse($bankTransferRequest['is_created']);
    }

    public function testAxisValidationCallbackForCollectx()
    {
        $testData = $this->testData['testValidateBankTransferAxis'];

        $response = $this->createCollectXVirtualAccount(receivers: ['bank_account']);

        $beneAccountNo = $response['receivers'][0]['account_number'];

        $testData['request']['content']['Bene_acc_no'] = $beneAccountNo;

        $testData['request']['content']['Corp_code'] = '9845';

        $testData['request']['content']['Req_dt_time'] = date("Y-m-d H:i:s");

        $this->testData[__FUNCTION__] = $testData;

        $merchantID = '10000000000000';

        $this->enableSplitzExperiment(
            experimentName: RazorxTreatment::COLLECTX_AXIS_PAYMENT_TRANSFER_RAMP_UP,
            id: $merchantID,
            requestData: ['id' => $merchantID]);

        $response = $this->startTest();

        $this->assertEquals('S', $response['Stts_flg']);
        $this->assertEquals('000', $response['Err_cd']);
        $this->assertEquals('Success', $response['message']);

        $bankTransferRequest = $this->getLastEntity('bank_transfer_request', true);

        $this->assertNotNull($bankTransferRequest);
        $this->assertFalse($bankTransferRequest['is_created']);
    }

    public function testAxisValidationCallbackForCollectxViaWorkerFlow()
    {
        $testData = $this->testData['testValidateBankTransferAxis'];

        $response = $this->createCollectXVirtualAccount(receivers: ['bank_account']);

        $beneAccountNo = $response['receivers'][0]['account_number'];

        $testData['request']['content']['Bene_acc_no'] = $beneAccountNo;

        $testData['request']['content']['Corp_code'] = '9845';

        $testData['request']['content']['Req_dt_time'] = date("Y-m-d H:i:s");

        $this->testData[__FUNCTION__] = $testData;

        $merchantID = '10000000000000';

        $this->enableSplitzExperiment(
            experimentName: RazorxTreatment::COLLECTX_AXIS_PAYMENT_TRANSFER_RAMP_UP,
            id: $merchantID,
            requestData: ['id' => $merchantID]);

        $this->fixtures->create('credits', ['merchant_id' => $merchantID, 'value' => 500 , 'type' => 'fee']);

        $response = $this->startTest();

        $this->assertEquals('S', $response['Stts_flg']);
        $this->assertEquals('000', $response['Err_cd']);
        $this->assertEquals('Success', $response['message']);

        $bankTransferRequest = $this->getLastEntity('bank_transfer_request', true);

        $this->assertNotNull($bankTransferRequest);
        $this->assertFalse($bankTransferRequest['is_created']);
    }

    public function testAxisValidationCallbackForCollectxViaWorkerFlowForTransferMode()
    {
        $testData = $this->testData['testValidateBankTransferAxis'];

        $response = $this->createCollectXVirtualAccount(receivers: ['bank_account']);

        $beneAccountNo = $response['receivers'][0]['account_number'];

        $testData['request']['content']['Bene_acc_no'] = $beneAccountNo;

        $testData['request']['content']['Corp_code'] = '9845';

        $testData['request']['content']['Pmode'] = 'TRANSFER';

        $testData['request']['content']['Req_dt_time'] = date("Y-m-d H:i:s");

        $this->testData[__FUNCTION__] = $testData;

        $merchantID = '10000000000000';

        $this->enableSplitzExperiment(
            experimentName: RazorxTreatment::COLLECTX_AXIS_PAYMENT_TRANSFER_RAMP_UP,
            id: $merchantID,
            requestData: ['id' => $merchantID]);

        $this->fixtures->create('credits', ['merchant_id' => $merchantID, 'value' => 500 , 'type' => 'fee']);

        $response = $this->startTest();

        $this->assertEquals('S', $response['Stts_flg']);
        $this->assertEquals('000', $response['Err_cd']);
        $this->assertEquals('Success', $response['message']);

        $bankTransferRequest = $this->getLastEntity('bank_transfer_request', true);

        $this->assertNotNull($bankTransferRequest);
        $this->assertFalse($bankTransferRequest['is_created']);
    }

    public function testAxisNotificationCallbackForCollectxViaWorkerFlow()
    {
        $testData = $this->testData['testValidateBankTransferAxis'];

        $response = $this->createCollectXVirtualAccount(receivers: ['bank_account']);

        $beneAccountNo = $response['receivers'][0]['account_number'];

        $testData['request']['content']['Bene_acc_no'] = $beneAccountNo;

        $testData['request']['content']['Req_type'] = 'notification';

        $testData['request']['content']['Corp_code'] = '9845';

        $testData['request']['content']['Req_dt_time'] = date("Y-m-d H:i:s");

        $this->testData[__FUNCTION__] = $testData;

        $merchantID = '10000000000000';

        $this->enableSplitzExperiment(
            experimentName: RazorxTreatment::COLLECTX_AXIS_PAYMENT_TRANSFER_RAMP_UP,
            id: $merchantID,
            requestData: ['id' => $merchantID]);

        $response = $this->startTest();

        $this->assertEquals('S', $response['Stts_flg']);
        $this->assertEquals('000', $response['Err_cd']);
        $this->assertEquals('Success', $response['message']);

        $bankTransferRequest = $this->getLastEntity('bank_transfer_request', true);

        $this->assertTrue($bankTransferRequest['is_created']);
        $this->assertNotNull($bankTransferRequest['payee_account']);

        $bankTransfer =  $this->getLastEntity('bank_transfer', true);

        $this->assertEquals($bankTransfer['narration'], $testData['request']['content']['UTR']);
        $this->assertEquals(200, $bankTransfer['amount']);
        $this->assertEquals('processed', $bankTransfer['status']);
        $this->assertEquals('NEFT', $bankTransfer['mode']);
        $this->assertEquals(true, $bankTransfer['expected']);
        $this->assertEquals(null, $bankTransfer['unexpected_reason']);
        $this->assertNotNull($bankTransfer['payment_id']);

        $payerBankAccount = $this->getEntityById('bank_account', $bankTransfer['payer_bank_account']['id'], true);
        $this->assertEquals($testData['request']['content']['Sndr_acnt'], $payerBankAccount['account_number']);

        $payment =  $this->getLastEntity('payment', true);

        $this->assertEquals(200, $payment['amount']);
        $this->assertEquals('bt_axis', $payment['gateway']);
        $this->assertEquals('10000000000001', $payment['terminal_id']);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals($bankTransfer['payment_id'], $payment['id']);
        $this->assertEquals('collectx', $payment['reference14']);
        $this->assertEquals('bank_account', $payment['receiver_type']);
        $this->assertTrue($payment['auto_captured']);

        $txn =  $this->getLastEntity('transaction', true);

        $this->assertEquals($payment['id'], $txn['entity_id']);
        $this->assertEquals(0, $txn['credit']);
    }

    public function testAxisValidationCallbackForCollectxViaWorkerFlow_WithModeTransferFailure()
    {
        $testData = $this->testData['testValidateBankTransferAxisWithLowFeeCredit_ApiMerchant'];

        $response = $this->createCollectXVirtualAccount(receivers: ['bank_account']);

        $beneAccountNo = $response['receivers'][0]['account_number'];

        $testData['request']['content']['Bene_acc_no'] = $beneAccountNo;

        $testData['request']['content']['Corp_code'] = '9845';

        $testData['request']['content']['Pmode'] = 'INVALID_MODE';

        $testData['request']['content']['Req_dt_time'] = date("Y-m-d H:i:s");

        $this->testData[__FUNCTION__] = $testData;

        $response = $this->startTest();

        $this->assertEquals('F', $response['Stts_flg']);
        $this->assertEquals('002', $response['Err_cd']);
        $this->assertEquals('Validation failed', $response['message']);
    }

    public function testAxisNotificationCallbackForCollectxViaWorkerFlow_WithLowFeeCredit()
    {
        $testData = $this->testData['testBankTransferAxisCallbackValidationFailure'];

        $response = $this->createCollectXVirtualAccount(receivers: ['bank_account']);

        $beneAccountNo = $response['receivers'][0]['account_number'];

        $testData['request']['content']['Bene_acc_no'] = $beneAccountNo;

        $testData['request']['content']['Corp_code'] = '9845';

        $testData['request']['content']['UTR'] = 'RAZP00010742429600013';

        $testData['request']['content']['Txn_amnt'] = '2';

        $testData['request']['content']['Req_type'] = 'notification';

        $testData['request']['content']['Req_dt_time'] = date("Y-m-d H:i:s");

        $this->testData[__FUNCTION__] = $testData;

        $merchantID = '10000000000000';

        $this->enableSplitzExperiment(
            experimentName: RazorxTreatment::COLLECTX_AXIS_PAYMENT_TRANSFER_RAMP_UP,
            id: $merchantID,
            requestData: ['id' => $merchantID]);

        $this->fixtures->create('credits', ['merchant_id' => $merchantID, 'value' => 1 , 'type' => 'fee']);

        $this->fixtures->edit('balance', '10000000000000', ['balance' => 100]);

//        $isSuccess = False;
//
//        $this->ValidateExceptionThrownDuringCollectxBankTransferProcessing(ErrorCode::COLLECTX_FEE_CREDITS_BELOW_THRESHOLD, $isSuccess);

        $response = $this->startTest();

        $this->assertEquals('F', $response['Stts_flg']);
        $this->assertEquals('002', $response['Err_cd']);
        $this->assertEquals('Validation failed', $response['message']);

        $bankTransferRequest = $this->getLastEntity('bank_transfer_request', true);

        $this->assertFalse($bankTransferRequest['is_created']);

        $this->assertEquals(ErrorCode::COLLECTX_FEE_CREDITS_BELOW_THRESHOLD ,$bankTransferRequest['error_message']);

//        $this->assertTrue($isSuccess);
    }

    public function testAxisNotificationCallbackForCollectxViaWorkerFlow_WithTransferMode()
    {
        $testData = $this->testData['testValidateBankTransferAxis'];

        $response = $this->createCollectXVirtualAccount(receivers: ['bank_account']);

        $beneAccountNo = $response['receivers'][0]['account_number'];

        $testData['request']['content']['Bene_acc_no'] = $beneAccountNo;

        $testData['request']['content']['Req_type'] = 'notification';

        $testData['request']['content']['Corp_code'] = '9845';

        $testData['request']['content']['Pmode'] = 'TRANSFER';

        $testData['request']['content']['Req_dt_time'] = date("Y-m-d H:i:s");

        $this->testData[__FUNCTION__] = $testData;

        $merchantID = '10000000000000';

        $this->enableSplitzExperiment(
            experimentName: RazorxTreatment::COLLECTX_AXIS_PAYMENT_TRANSFER_RAMP_UP,
            id: $merchantID,
            requestData: ['id' => $merchantID]);

        $response = $this->startTest();

        $this->assertEquals('S', $response['Stts_flg']);
        $this->assertEquals('000', $response['Err_cd']);
        $this->assertEquals('Success', $response['message']);

        $bankTransferRequest = $this->getLastEntity('bank_transfer_request', true);

        self::assertNotNull($bankTransferRequest);

        $this->assertFalse($bankTransferRequest['is_created']);
    }

    public function testAxisNotificationCallbackForCollectx_ClosedVaBankTransferTransfer_ViaWorkerFlow()
    {
        $testData = $this->testData['testBankTransferAxisCallbackValidationFailure'];

        $response = $this->createCollectXVirtualAccount(receivers: ['bank_account']);

        $beneAccountNo = $response['receivers'][0]['account_number'];

        $this->fixtures->edit('virtual_account', $response['id'], ['status' => 'closed']);

        $testData['request']['content']['Bene_acc_no'] = $beneAccountNo;

        $testData['request']['content']['Corp_code'] = '9845';

        $testData['request']['content']['UTR'] = 'RAZP00010742429600013';

        $testData['request']['content']['Txn_amnt'] = '2';

        $testData['request']['content']['Req_type'] = 'notification';

        $testData['request']['content']['Req_dt_time'] = date("Y-m-d H:i:s");

        $this->testData[__FUNCTION__] = $testData;

        $merchantID = '10000000000000';

        $this->enableSplitzExperiment(
            experimentName: RazorxTreatment::COLLECTX_AXIS_PAYMENT_TRANSFER_RAMP_UP,
            id: $merchantID,
            requestData: ['id' => $merchantID]);

//        $isSuccess = False;
//
//        $this->ValidateExceptionThrownDuringCollectxBankTransferProcessing(ErrorCode::COLLECTX_UNEXPECTED_PAYMENT_ON_CLOSED_VA, $isSuccess);

        $response = $this->startTest();

        $this->assertEquals('F', $response['Stts_flg']);
        $this->assertEquals('002', $response['Err_cd']);
        $this->assertEquals('Validation failed', $response['message']);

        $bankTransferRequest = $this->getLastEntity('bank_transfer_request', true);

        $this->assertFalse($bankTransferRequest['is_created']);

        $this->assertEquals(ErrorCode::COLLECTX_UNEXPECTED_PAYMENT_ON_CLOSED_VA ,$bankTransferRequest['error_message']);

//        $this->assertTrue($isSuccess);
    }

    public function testYesbankBankTransferValidationCallbackForCollectxViaWorkerFlow()
    {
        $testData = $this->testData['testValidateTransferYesbank'];

        $response = $this->createCollectXVirtualAccount(gateway: 'yesbank');

        $beneAccountNo = $response['receivers'][0]['account_number'];

        $testData['request']['content']['validate']['bene_account_no'] = $beneAccountNo;

        $testData['request']['content']['validate']['transfer_type'] = 'IMPS';

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->yesbankAuth();

        $response = $this->startTest();

        $this->assertEquals('pass', $response['validateResponse']['decision']);

        $bankTransferRequest = $this->getLastEntity('bank_transfer_request', true);

        $this->assertTrue($bankTransferRequest['is_created']);
        $this->assertNotNull($bankTransferRequest['payee_account']);

        $bankTransfer =  $this->getLastEntity('bank_transfer', true);

        $this->assertEquals($bankTransfer['narration'], $testData['request']['content']['UTR']);
        $this->assertEquals(700, $bankTransfer['amount']);
        $this->assertEquals('processed', $bankTransfer['status']);
        $this->assertEquals('IMPS', $bankTransfer['mode']);
        $this->assertEquals(true, $bankTransfer['expected']);
        $this->assertEquals(null, $bankTransfer['unexpected_reason']);
        $this->assertNotNull($bankTransfer['payment_id']);

        $payerBankAccount = $this->getEntityById('bank_account', $bankTransfer['payer_bank_account']['id'], true);
        $this->assertEquals($testData['request']['content']['validate']['rmtr_account_no'], $payerBankAccount['account_number']);

        $payment =  $this->getLastEntity('payment', true);

        $this->assertEquals(700, $payment['amount']);
        $this->assertEquals('collectx', $payment['reference14']);
        $this->assertEquals('bt_yesbank', $payment['gateway']);
        $this->assertEquals('10000000000001', $payment['terminal_id']);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals($bankTransfer['payment_id'], $payment['id']);
        $this->assertEquals('collectx', $payment['reference14']);
        $this->assertEquals('bank_account', $payment['receiver_type']);
        $this->assertTrue($payment['auto_captured']);

        $txn =  $this->getLastEntity('transaction', true);

        $this->assertEquals($payment['id'], $txn['entity_id']);
        $this->assertEquals(0, $txn['credit']);
    }

    public function testYesbankBankTransferValidationCallbackForCollectxViaWorkerFlowLowBalance()
    {
        $testData = $this->testData['testYesbankBankTransferValidationCallbackForCollectxViaWorkerFlowLowBalance'];

        $response = $this->createCollectXVirtualAccount(gateway: 'yesbank');

        $beneAccountNo = $response['receivers'][0]['account_number'];

        $testData['request']['content']['validate']['bene_account_no'] = $beneAccountNo;

        $testData['request']['content']['validate']['transfer_type'] = 'IMPS';

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->yesbankAuth();

        $this->fixtures->edit('balance', '10000000000000', ['balance' => 100]);

//        $isSuccess = False;
//
//        $this->ValidateExceptionThrownDuringCollectxBankTransferProcessing(ErrorCode::COLLECTX_FEE_CREDITS_BELOW_THRESHOLD, $isSuccess);

        $this->startTest();

        $bankTransferRequest = $this->getLastEntity('bank_transfer_request', true);

        $this->assertFalse($bankTransferRequest['is_created']);

        $this->assertEquals(ErrorCode::COLLECTX_FEE_CREDITS_BELOW_THRESHOLD ,$bankTransferRequest['error_message']);

//        $this->assertTrue($isSuccess);
    }

    public function testAxisNotificationCallbackForCollectx()
    {
        $testData = $this->testData['testValidateBankTransferAxis'];

        $response = $this->createCollectXVirtualAccount(receivers: ['bank_account']);

        $beneAccountNo = $response['receivers'][0]['account_number'];

        $testData['request']['content']['Bene_acc_no'] = $beneAccountNo;

        $testData['request']['content']['Req_type'] = 'notification';

        $testData['request']['content']['Corp_code'] = '9845';

        $testData['request']['content']['Req_dt_time'] = date("Y-m-d H:i:s");

        $this->testData[__FUNCTION__] = $testData;

        $merchantID = '10000000000000';

        $this->enableSplitzExperiment(
            experimentName: RazorxTreatment::COLLECTX_AXIS_PAYMENT_TRANSFER_RAMP_UP,
            id: $merchantID,
            requestData: ['id' => $merchantID]);

        $response = $this->startTest();

        $this->assertEquals('S', $response['Stts_flg']);
        $this->assertEquals('000', $response['Err_cd']);
        $this->assertEquals('Success', $response['message']);

        $bankTransferRequest = $this->getLastEntity('bank_transfer_request', true);

        $this->assertTrue($bankTransferRequest['is_created']);
        $this->assertNotNull($bankTransferRequest['payee_account']);

        $bankTransfer =  $this->getLastEntity('bank_transfer', true);

        $this->assertEquals($bankTransfer['narration'], $testData['request']['content']['UTR']);
        $this->assertEquals(200, $bankTransfer['amount']);
        $this->assertEquals('processed', $bankTransfer['status']);
        $this->assertEquals('NEFT', $bankTransfer['mode']);
        $this->assertEquals(true, $bankTransfer['expected']);
        $this->assertEquals(null, $bankTransfer['unexpected_reason']);
        $this->assertNotNull($bankTransfer['payment_id']);

        $payerBankAccount = $this->getEntityById('bank_account', $bankTransfer['payer_bank_account']['id'], true);
        $this->assertEquals($testData['request']['content']['Sndr_acnt'], $payerBankAccount['account_number']);

        $payment =  $this->getLastEntity('payment', true);

        $this->assertEquals(200, $payment['amount']);
        $this->assertEquals('bt_axis', $payment['gateway']);
        $this->assertEquals('10000000000001', $payment['terminal_id']);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals($bankTransfer['payment_id'], $payment['id']);
        $this->assertEquals('collectx', $payment['reference14']);
        $this->assertEquals('bank_account', $payment['receiver_type']);
        $this->assertTrue($payment['auto_captured']);

        $txn =  $this->getLastEntity('transaction', true);

        $this->assertEquals($payment['id'], $txn['entity_id']);
        $this->assertEquals(0, $txn['credit']);
    }

    public function testAxisValidationCallbackForCollectx_DuplicateBankTransfer()
    {
        $testData = $this->testData['testBankTransferAxisCallbackValidationFailure'];

        $response = $this->createCollectXVirtualAccount(receivers: ['bank_account']);

        $beneAccountNo = $response['receivers'][0]['account_number'];

        $this->fixtures->create('bank_transfer', [
            'utr'            => 'RAZP00010742429600013',
            'amount'         => 2,
            'payee_account'  => $beneAccountNo,
            'merchant_id'    => '10000000000000']);

        $testData['request']['content']['Bene_acc_no'] = $beneAccountNo;

        $testData['request']['content']['Corp_code'] = '9845';

        $testData['request']['content']['Req_dt_time'] = date("Y-m-d H:i:s");

        $testData['request']['content']['UTR'] = 'RAZP00010742429600013';

        $testData['request']['content']['Txn_amnt'] = '2';

        $testData['request']['content']['Req_type'] = 'validation';

        $this->testData[__FUNCTION__] = $testData;

        $merchantID = '10000000000000';

        $this->enableSplitzExperiment(
            experimentName: RazorxTreatment::COLLECTX_AXIS_PAYMENT_TRANSFER_RAMP_UP,
            id: $merchantID,
            requestData: ['id' => $merchantID]);

//        $isSuccess = False;

//        $this->ValidateExceptionThrownDuringCollectxBankTransferProcessing(ErrorCode::BAD_REQUEST_DUPLICATE_BANK_TRANSFER_CALLBACK, $isSuccess);

        $response = $this->startTest();

        $this->assertEquals('F', $response['Stts_flg']);
        $this->assertEquals('002', $response['Err_cd']);
        $this->assertEquals('Validation failed', $response['message']);

        $bankTransferRequest = $this->getLastEntity('bank_transfer_request', true);

        $this->assertNotNull($bankTransferRequest);

        $this->assertFalse($bankTransferRequest['is_created']);

        $this->assertEquals(ErrorCode::BAD_REQUEST_DUPLICATE_BANK_TRANSFER_CALLBACK ,$bankTransferRequest['error_message']);

//        $this->assertTrue($isSuccess);
    }

    public function testAxisNotificationCallbackForCollectx_DuplicateBankTransfer()
    {
        $testData = $this->testData['testBankTransferAxisCallbackValidationFailure'];

        $response = $this->createCollectXVirtualAccount(receivers: ['bank_account']);

        $beneAccountNo = $response['receivers'][0]['account_number'];

        $this->fixtures->create('bank_transfer', [
            'utr'            => 'RAZP00010742429600013',
            'amount'         => 2,
            'payee_account'  => $beneAccountNo,
            'merchant_id'    => '10000000000000']);

        $testData['request']['content']['Bene_acc_no'] = $beneAccountNo;

        $testData['request']['content']['Corp_code'] = '9845';

        $testData['request']['content']['Req_type'] = 'notification';

        $testData['request']['content']['UTR'] = 'RAZP00010742429600013';

        $testData['request']['content']['Txn_amnt'] = '2';

        $testData['request']['content']['Req_dt_time'] = date("Y-m-d H:i:s");

        $this->testData[__FUNCTION__] = $testData;

        $merchantID = '10000000000000';

        $this->enableSplitzExperiment(
            experimentName: RazorxTreatment::COLLECTX_AXIS_PAYMENT_TRANSFER_RAMP_UP,
            id: $merchantID,
            requestData: ['id' => $merchantID]);

//        $isSuccess = False;

//        $this->ValidateExceptionThrownDuringCollectxBankTransferProcessing(ErrorCode::BAD_REQUEST_DUPLICATE_BANK_TRANSFER_CALLBACK, $isSuccess);

        $response = $this->startTest();

        $this->assertEquals('F', $response['Stts_flg']);
        $this->assertEquals('002', $response['Err_cd']);
        $this->assertEquals('Validation failed', $response['message']);

        $bankTransferRequest = $this->getLastEntity('bank_transfer_request', true);

        $this->assertFalse($bankTransferRequest['is_created']);

        $this->assertEquals(ErrorCode::BAD_REQUEST_DUPLICATE_BANK_TRANSFER_CALLBACK ,$bankTransferRequest['error_message']);

//        $this->assertTrue($isSuccess);
    }

    public function testAxisNotificationCallbackForCollectx_ClosedVaBankTransferTransfer()
    {
        $testData = $this->testData['testBankTransferAxisCallbackValidationFailure'];

        $response = $this->createCollectXVirtualAccount(receivers: ['bank_account']);

        $beneAccountNo = $response['receivers'][0]['account_number'];

        $this->fixtures->edit('virtual_account', $response['id'], ['status' => 'closed']);

        $testData['request']['content']['Bene_acc_no'] = $beneAccountNo;

        $testData['request']['content']['Corp_code'] = '9845';

        $testData['request']['content']['UTR'] = 'RAZP00010742429600013';

        $testData['request']['content']['Txn_amnt'] = '2';

        $testData['request']['content']['Req_type'] = 'notification';

        $testData['request']['content']['Req_dt_time'] = date("Y-m-d H:i:s");

        $this->testData[__FUNCTION__] = $testData;

        $merchantID = '10000000000000';

        $this->enableSplitzExperiment(
            experimentName: RazorxTreatment::COLLECTX_AXIS_PAYMENT_TRANSFER_RAMP_UP,
            id: $merchantID,
            requestData: ['id' => $merchantID]);

//        $isSuccess = False;

//        $this->ValidateExceptionThrownDuringCollectxBankTransferProcessing(ErrorCode::COLLECTX_UNEXPECTED_PAYMENT_ON_CLOSED_VA, $isSuccess);

        $response = $this->startTest();

        $this->assertEquals('F', $response['Stts_flg']);
        $this->assertEquals('002', $response['Err_cd']);
        $this->assertEquals('Validation failed', $response['message']);

        $bankTransferRequest = $this->getLastEntity('bank_transfer_request', true);

        $this->assertFalse($bankTransferRequest['is_created']);

        $this->assertEquals(ErrorCode::COLLECTX_UNEXPECTED_PAYMENT_ON_CLOSED_VA ,$bankTransferRequest['error_message']);

//        $this->assertTrue($isSuccess);
    }

    public function testYesbankBankTransferValidationCallbackForCollectx()
    {
        $testData = $this->testData['testValidateTransferYesbank'];

        $response = $this->createCollectXVirtualAccount(gateway: 'yesbank');

        $beneAccountNo = $response['receivers'][0]['account_number'];

        $testData['request']['content']['validate']['bene_account_no'] = $beneAccountNo;

        $testData['request']['content']['validate']['transfer_type'] = 'IMPS';

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->yesbankAuth();

        $response = $this->startTest();

        $this->assertEquals('pass', $response['validateResponse']['decision']);

        $bankTransferRequest = $this->getLastEntity('bank_transfer_request', true);

        $this->assertTrue($bankTransferRequest['is_created']);
        $this->assertNotNull($bankTransferRequest['payee_account']);

        $bankTransfer =  $this->getLastEntity('bank_transfer', true);

        $this->assertEquals($bankTransfer['narration'], $testData['request']['content']['UTR']);
        $this->assertEquals(700, $bankTransfer['amount']);
        $this->assertEquals('processed', $bankTransfer['status']);
        $this->assertEquals('IMPS', $bankTransfer['mode']);
        $this->assertEquals(true, $bankTransfer['expected']);
        $this->assertEquals(null, $bankTransfer['unexpected_reason']);
        $this->assertNotNull($bankTransfer['payment_id']);

        $payerBankAccount = $this->getEntityById('bank_account', $bankTransfer['payer_bank_account']['id'], true);
        $this->assertEquals($testData['request']['content']['validate']['rmtr_account_no'], $payerBankAccount['account_number']);

        $payment =  $this->getLastEntity('payment', true);

        $this->assertEquals(700, $payment['amount']);
        $this->assertEquals('collectx', $payment['reference14']);
        $this->assertEquals('bt_yesbank', $payment['gateway']);
        $this->assertEquals('10000000000001', $payment['terminal_id']);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals($bankTransfer['payment_id'], $payment['id']);
        $this->assertEquals('collectx', $payment['reference14']);
        $this->assertEquals('bank_account', $payment['receiver_type']);
        $this->assertTrue($payment['auto_captured']);

        $txn =  $this->getLastEntity('transaction', true);

        $this->assertEquals($payment['id'], $txn['entity_id']);
        $this->assertEquals(0, $txn['credit']);
    }

    public function testYesbankBankTransferNotificationCallbackForCollectx()
    {
        $testData = $this->testData['testNotifyCollectxTransferYesbank'];

        $response = $this->createCollectXVirtualAccount(gateway: 'yesbank');

        $beneAccountNo = $response['receivers'][0]['account_number'];

        $testData['request']['content']['notify']['bene_account_no'] = $beneAccountNo;

        $testData['reque st']['content']['notify']['transfer_type'] = 'IMPS';

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->yesbankAuth();

        $response = $this->startTest();

        $this->assertEquals('ok', $response['notifyResult']['result']);
    }

    public function testYesbankUpiValidationCallbackForCollectx()
    {
        $testData = $this->testData['testValidateTransferYesbank'];

        $response = $this->createCollectXVirtualAccount(receivers: ['vpa'], gateway: 'yesbank');

        $beneAccountNo = $response['receivers'][0]['username'];

        $testData['request']['content']['validate']['bene_account_no'] = $beneAccountNo;

        $testData['request']['content']['validate']['transfer_type'] = 'UPI';

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->yesbankAuth();

        $response = $this->startTest();

        $this->assertEquals('pass', $response['validateResponse']['decision']);

        $upiTransferRequest = $this->getLastEntity('upi_transfer_request', true);

        $this->assertTrue($upiTransferRequest['is_created']);
        $this->assertNotNull($upiTransferRequest['payee_vpa']);

        $upiTransfer =  $this->getLastEntity('upi_transfer', true);

        $this->assertEquals($upiTransfer['narration'], $testData['request']['content']['UTR']);
        $this->assertEquals(700, $upiTransfer['amount']);
        $this->assertNotNull($upiTransfer['rrn']);
        $this->assertTrue($upiTransfer['expected']);
        $this->assertEquals(null, $upiTransfer['unexpected_reason']);
        $this->assertNotNull($upiTransfer['payment_id']);

        $payment =  $this->getLastEntity('payment', true);

        $this->assertEquals(700, $payment['amount']);
        $this->assertEquals('collectx', $payment['reference14']);
        $this->assertEquals('upi_yesbank', $payment['gateway']);
        $this->assertEquals('10000000000002', $payment['terminal_id']);
        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals($upiTransfer['payment_id'], $payment['id']);
        $this->assertEquals('collectx', $payment['reference14']);
        $this->assertEquals('vpa', $payment['receiver_type']);
        $this->assertTrue($payment['auto_captured']);

        $txn =  $this->getLastEntity('transaction', true);

        $this->assertEquals($payment['id'], $txn['entity_id']);
        $this->assertEquals(0, $txn['credit']);
    }

    public function testYesbankUpiValidationCallbackForCollectx_WithClosedVirtualAccount()
    {
        $testData = $this->testData[__FUNCTION__];

        $response = $this->createCollectXVirtualAccount(receivers: ['vpa'], gateway: 'yesbank');

        $this->fixtures->edit('virtual_account', $response['id'], ['status' => 'closed']);

        $beneAccountNo = $response['receivers'][0]['username'];

        $testData['request']['content']['validate']['bene_account_no'] = $beneAccountNo;

        $testData['request']['content']['validate']['transfer_type'] = 'UPI';

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->yesbankAuth();

//        $isSuccess = False;
//
//        $this->validateExceptionThrownDuringCollectxUpiTransferProcessing(ErrorCode::COLLECTX_UNEXPECTED_PAYMENT_ON_CLOSED_VA, $isSuccess);

        $response = $this->startTest();

        $this->assertEquals('reject', $response['validateResponse']['decision']);

        $upiTransferRequest = $this->getLastEntity('upi_transfer_request', true);

        $this->assertFalse($upiTransferRequest['is_created']);

        $this->assertEquals(ErrorCode::COLLECTX_UNEXPECTED_PAYMENT_ON_CLOSED_VA ,$upiTransferRequest['error_message']);

//        $this->assertTrue($isSuccess);
    }

    // TODO: Uncomment this test case once check is enabled for yesbank upi payments for collectx
//    public function testYesbankUpiValidationCallbackForCollectx_WithNonBankingBalanceType()
//    {
//        $testData = $this->testData['testYesbankUpiValidationCallbackForCollectx_WithClosedVirtualAccount'];
//
//        $response = $this->createCollectXVirtualAccount(receivers: ['vpa'], gateway: 'yesbank');
//
//        $virtualAccount = $this->getDbEntityById('virtual_account', $response['id']);
//
//        $this->fixtures->edit('balance', $virtualAccount['balance_id'], ['type' => 'primary']);
//
//        $beneAccountNo = $response['receivers'][0]['username'];
//
//        $testData['request']['content']['validate']['bene_account_no'] = $beneAccountNo;
//
//        $testData['request']['content']['validate']['transfer_type'] = 'UPI';
//
//        $this->testData[__FUNCTION__] = $testData;
//
//        $this->ba->yesbankAuth();
//
//        $response = $this->startTest();
//
//        $this->assertEquals('reject', $response['validateResponse']['decision']);
//    }

    // TODO: Uncomment this test case once check is enabled for yesbank upi payments for collectx
//    public function testYesbankUpiValidationCallbackForCollectx_WithNonDirectBalanceType()
//    {
//        $testData = $this->testData['testYesbankUpiValidationCallbackForCollectx_WithClosedVirtualAccount'];
//
//        $response = $this->createCollectXVirtualAccount(receivers: ['vpa'], gateway: 'yesbank');
//
//        $virtualAccount = $this->getDbEntityById('virtual_account', $response['id']);
//
//        $this->fixtures->edit('balance', $virtualAccount['balance_id'], ['account_type' => 'shared']);
//
//        $beneAccountNo = $response['receivers'][0]['username'];
//
//        $testData['request']['content']['validate']['bene_account_no'] = $beneAccountNo;
//
//        $testData['request']['content']['validate']['transfer_type'] = 'UPI';
//
//        $this->testData[__FUNCTION__] = $testData;
//
//        $this->ba->yesbankAuth();
//
//        $response = $this->startTest();
//
//        $this->assertEquals('reject', $response['validateResponse']['decision']);
//    }


    public function testAxisNotificationCallbackForCollectx_DedicatedTerminalNotFound()
    {
        $testData = $this->testData['testValidateBankTransferAxis'];

        $response = $this->createCollectXVirtualAccount(receivers: ['bank_account']);

        $beneAccountNo = $response['receivers'][0]['account_number'];

        $testData['request']['content']['Bene_acc_no'] = $beneAccountNo;

        $testData['request']['content']['Req_type'] = 'notification';

        $testData['request']['content']['Corp_code'] = '9845';

        $testData['request']['content']['Req_dt_time'] = date("Y-m-d H:i:s");

        $this->testData[__FUNCTION__] = $testData;

        $merchantID = '10000000000000';

        $this->enableSplitzExperiment(
            experimentName: RazorxTreatment::COLLECTX_AXIS_PAYMENT_TRANSFER_RAMP_UP,
            id: $merchantID,
            requestData: ['id' => $merchantID]);

        $this->fixtures->edit('terminal', '10000000000001', [
            'merchant_id'   => '100000Razorpay']);

        $response = $this->startTest();

        $this->assertEquals('S', $response['Stts_flg']);
        $this->assertEquals('000', $response['Err_cd']);
        $this->assertEquals('Success', $response['message']);

        $bankTransferRequest = $this->getLastEntity('bank_transfer_request', true);

        $this->assertFalse($bankTransferRequest['is_created']);
        $this->assertNotNull($bankTransferRequest['payee_account']);
        $this->assertEquals('No terminal found for bank transfer.', $bankTransferRequest['error_message']);
    }


    public function testValidateBankTransferAxis()
    {
        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['Bene_acc_no'] = $this->getAxisVaBankAccount();

        $this->ba->directAuth();

        $request = [
            'url' => '/ecollect/validate/axis/test',
            'method' => 'post',
            'server' => $testData['request']['server'],
            'content' => $testData['request']['content']
        ];

        $response = $this->makeRequestAndGetContent($request);
        $this->assertEquals('S', $response['Stts_flg']);
        $this->assertEquals('000', $response['Err_cd']);
        $this->assertEquals('Success', $response['message']);
    }

    public function testValidateBankTransferAxisForHSBC()
    {
        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['Bene_acc_no'] = $this->getAxisVaBankAccount();

        $this->ba->directAuth();

        $request = [
            'url' => '/ecollect/validate/axis/test',
            'method' => 'post',
            'server' => $testData['request']['server'],
            'content' => $testData['request']['content']
        ];

        $response = $this->makeRequestAndGetContent($request);

        // Test needs more assertions
        $this->assertEquals('S', $response['Stts_flg']);
        $this->assertEquals('000', $response['Err_cd']);
        $this->assertEquals('Success', $response['message']);
    }

    public function testAxisBankTransferValidateCustomerFeeMerchant()
    {
        $this->fixtures->merchant->enableConvenienceFeeModel('10000000000000');

        $this->fixtures->pricing->editDefaultPlan(['fee_bearer' => 'customer']);

        $testData = $this->testData['testValidateBankTransferAxis'];

        $testData['request']['content']['Bene_acc_no'] = $this->getAxisVaBankAccount();

        $this->fixtures->edit('virtual_account', $this->virtualAccountId, ['amount_expected' => '200']);

        $testData['request']['content']['Txn_amnt'] = '2.02';

        $this->ba->directAuth();

        $request = [
            'url' => '/ecollect/validate/axis/test',
            'method' => 'post',
            'server' => $testData['request']['server'],
            'content' => $testData['request']['content']
        ];

        $response = $this->makeRequestAndGetContent($request);
        $this->assertEquals('S', $response['Stts_flg']);
        $this->assertEquals('000', $response['Err_cd']);
        $this->assertEquals('Success', $response['message']);
    }

    public function testValidateBankTransferAxisForImps()
    {
        /*
         * For Axis, there is an additional validation call w/o IFSC code for IMPS payments.
         * The call is made with mode as TRANSFER.
         */
        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['Bene_acc_no'] = $this->getAxisVaBankAccount();

        $this->ba->directAuth();

        $request = [
            'url' => '/ecollect/validate/axis/test',
            'method' => 'post',
            'server' => $testData['request']['server'],
            'content' => $testData['request']['content']
        ];

        $response = $this->makeRequestAndGetContent($request);
        $this->assertEquals('S', $response['Stts_flg']);
        $this->assertEquals('000', $response['Err_cd']);
        $this->assertEquals('Success', $response['message']);
    }

    public function testBankTransferAxis()
    {
        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['Bene_acc_no'] = $this->getAxisVaBankAccount();
        var_dump($testData['request']['content']['Data']);
        $this->ba->directAuth();

        $this->startTest($testData);

        $bankTransferRequest = $this->getLastEntity('bank_transfer_request', true);

        $this->assertEquals(true, $bankTransferRequest['is_created']);
        $this->assertNotNull($bankTransferRequest['payee_account']);

        $bankTransfer =  $this->getLastEntity('bank_transfer', true);

        $this->assertEquals($bankTransfer['narration'], $testData['request']['content']['UTR']);
        $this->assertEquals(200, $bankTransfer['amount']);

        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals(200, $payment['amount']);
        $this->assertEquals('bt_axis', $payment['gateway']);

        $payerBankAccount = $this->getEntityById('bank_account', $bankTransfer['payer_bank_account']['id'], true);
        $this->assertEquals($testData['request']['content']['Sndr_acnt'], $payerBankAccount['account_number']);
    }

    public function testEcollectAxisBatchCreate()
    {
        $data = $this->testData['ecollectAxisBatchData'];

        $this->createExcelFile($data, 'filename', 'files/filestore', extension: 'xls');

        $this->ba->h2hAuth();

        $this->startTest();
    }

    public function testEcollectAxisCollectxBatchCreate()
    {
        $data = $this->testData['ecollectAxisCollectxBatchData'];

        $this->createExcelFile($data, 'filename', 'files/filestore', extension: 'xls');

        $this->ba->h2hAuth();

        $this->startTest();
    }

    public function testValidateBankTransferAxisDuplicate()
    {
        $testData = $this->testData['testBankTransferAxis'];

        $testData['request']['content']['Bene_acc_no'] = $this->getAxisVaBankAccount();

        $this->ba->directAuth();

        $this->startTest($testData);

        $bankTransferRequest = $this->getLastEntity('bank_transfer_request', true);

        $this->assertEquals(true, $bankTransferRequest['is_created']);
        $this->assertNotNull($bankTransferRequest['payee_account']);

        $bankTransfer =  $this->getLastEntity('bank_transfer', true);

        $this->assertEquals($bankTransfer['narration'], $testData['request']['content']['UTR']);
        $this->assertEquals(200, $bankTransfer['amount']);

        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals(200, $payment['amount']);
        $this->assertEquals('bt_axis', $payment['gateway']);

        $payerBankAccount = $this->getEntityById('bank_account', $bankTransfer['payer_bank_account']['id'], true);
        $this->assertEquals($testData['request']['content']['Sndr_acnt'], $payerBankAccount['account_number']);

        $testData['request']['content']['Req_type'] = 'validation';

        $this->ba->directAuth();

        $request = [
            'url' => '/ecollect/validate/axis/test',
            'method' => 'post',
            'server' => $testData['request']['server'],
            'content' => $testData['request']['content']
        ];

        $response = $this->makeRequestAndGetContent($request);
        $this->assertEquals('F', $response['Stts_flg']);
        $this->assertEquals('002', $response['Err_cd']);
        $this->assertEquals('Validation failed', $response['message']);
    }

    public function testBankTransferAxisDuplicate()
    {
        $testData = $this->testData['testBankTransferAxis'];

        $testData['request']['content']['Bene_acc_no'] = $this->getAxisVaBankAccount();

        $this->ba->directAuth();

        $this->startTest($testData);

        $bankTransferRequest = $this->getLastEntity('bank_transfer_request', true);

        $this->assertEquals(true, $bankTransferRequest['is_created']);
        $this->assertNotNull($bankTransferRequest['payee_account']);

        $bankTransfer =  $this->getLastEntity('bank_transfer', true);

        $this->assertEquals($bankTransfer['narration'], $testData['request']['content']['UTR']);
        $this->assertEquals(200, $bankTransfer['amount']);

        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals(200, $payment['amount']);
        $this->assertEquals('bt_axis', $payment['gateway']);

        $payerBankAccount = $this->getEntityById('bank_account', $bankTransfer['payer_bank_account']['id'], true);
        $this->assertEquals($testData['request']['content']['Sndr_acnt'], $payerBankAccount['account_number']);

        $request = [
            'url' => '/ecollect/validate/axis/test',
            'method' => 'post',
            'server' => $testData['request']['server'],
            'content' => $testData['request']['content']
        ];

        $response = $this->makeRequestAndGetContent($request);
        $this->assertEquals('F', $response['Stts_flg']);
        $this->assertEquals('002', $response['Err_cd']);
        $this->assertEquals('Validation failed', $response['message']);
    }

    protected function getAxisVaBankAccount()
    {
        $terminalAttributes = [ 'id' =>'GENERICBNKAXIS', 'gateway' => Gateway::BT_AXIS, 'gateway_merchant_id' => 'RAZP0001' ];
        $this->fixtures->on('live')->create('terminal:shared_bank_account_terminal', $terminalAttributes);
        $this->fixtures->on('test')->create('terminal:shared_bank_account_terminal', $terminalAttributes);

        $bankAccount = $this->createVirtualAccount();

        return $bankAccount['account_number'];
    }

    public function testBankTransferAxisImps()
    {
        $testData = $this->testData['testBankTransferAxis'];

        $testData['request']['content']['Bene_acc_no'] = $this->getAxisVaBankAccount();
        $testData['request']['content']['Pmode'] = 'IMPS';
        var_dump($testData['request']['content']['Data']);
        $this->ba->directAuth();

        $this->startTest($testData);

        $bankTransferRequest = $this->getLastEntity('bank_transfer_request', true);

        $this->assertEquals(true, $bankTransferRequest['is_created']);
        $this->assertNotNull($bankTransferRequest['payee_account']);

        $bankTransfer =  $this->getLastEntity('bank_transfer', true);

        $this->assertEquals($bankTransfer['narration'], $testData['request']['content']['UTR']);
        $this->assertEquals(200, $bankTransfer['amount']);

        $payerBankAccount = $this->getEntityById('bank_account', $bankTransfer['payer_bank_account']['id'], true);
        $this->assertEquals($testData['request']['content']['Sndr_acnt'], $payerBankAccount['account_number']);

        $this->assertEquals('IMPS', $bankTransfer['mode']);
        $this->assertEquals(true, $bankTransfer['expected']);
        $this->assertEquals(null, $bankTransfer['unexpected_reason']);
        $this->assertNotNull($bankTransfer['payment_id']);

        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals(200, $payment['amount']);

        $this->assertEquals('GENERICBNKAXIS', $payment['terminal_id']);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals($bankTransfer['payment_id'], $payment['id']);
        $this->assertEquals('bank_account', $payment['receiver_type']);
        $this->assertEquals('bt_axis', $payment['gateway']);
    }

    public function testBankTransferAxisCallbackValidationFailure()
    {
        $testData = $this->testData[__FUNCTION__];

        $this->ba->axisAuth();

        $this->startTest($testData);;
    }

    public function testBankTransferAxisWithEmptyPayeeAccount()
    {
        $testData = $this->testData[__FUNCTION__];

        $this->ba->axisAuth();

        $this->startTest($testData);
    }

    public function testBankTransferAxisUnexpected()
    {
        $testData = $this->testData['testBankTransferAxis'];
        $this->getAxisVaBankAccount();

        // Set beneficiary account to an unknown for creating unexpected payment
        $testData['request']['content']['Bene_acc_no'] = 'RAND123';

        $this->ba->axisAuth();
        $this->enableRazorXTreatmentForDisableRefundsUnexpectedPayment();

        $this->startTest($testData);

        $bankTransferRequest = $this->getLastEntity('bank_transfer_request', true);

        $this->assertEquals(true, $bankTransferRequest['is_created']);
        $this->assertNotNull($bankTransferRequest['payee_account']);

        $bankTransfer =  $this->getLastEntity('bank_transfer', true);

        $this->assertEquals($bankTransfer['narration'], $testData['request']['content']['UTR']);
        $this->assertEquals(200, $bankTransfer['amount']);

        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals(200, $payment['amount']);
        $this->assertEquals('bt_axis', $payment['gateway']);
        $this->assertEquals('authorized', $payment['status']);

        // refund_at should be NULL as we disabled refunds for unexpected payments
        $this->assertNull($payment['refund_at']);

        $payerBankAccount = $this->getEntityById('bank_account', $bankTransfer['payer_bank_account']['id'], true);
        $this->assertEquals($testData['request']['content']['Sndr_acnt'], $payerBankAccount['account_number']);

    }

    public function testRblToAxisMigration()
    {
        $this->fixtures->merchant->on('live')->createAccount('migrateMerchnt');
        $this->fixtures->merchant->on('live')->enableMethod('migrateMerchnt', 'bank_transfer');

        // Virtual Account's with a migratable bank account
        $virtualAccount = $this->fixtures->on('live')->create(
            'virtual_account',
            [
                'merchant_id' => 'migrateMerchnt',
                'status' => 'active',
            ]
        );
        $bankAccount = $this->fixtures->on('live')->create(
            'bank_account',
            [
                'merchant_id' => 'migrateMerchnt',
                'entity_id' => $virtualAccount->getId(),
                'type' => 'virtual_account',
                'account_number' => '2223330075206730',
                'ifsc_code' => 'RATN0VAAPIS',
            ]
        );

        $this->fixtures->on('live')->edit(
            'virtual_account',
            $virtualAccount->getId(),
            ['bank_account_id' => $bankAccount->getId()]
        );

        $virtualAccountCreatedAt = $virtualAccount->getAttribute('created_at');

        // Create Input body and trigger Migration Job
        $input = $this->testData[__FUNCTION__];
        $input['merchant_ids'] = ['migrateMerchnt'];
        $input['from_time'] = $virtualAccountCreatedAt;
        $input['to_time'] = $virtualAccountCreatedAt;

        $response = (new VirtualAccount\Core)->bulkMigrateRblBank($input);

        $this->assertNotNull($response);
        $this->assertTrue($response['Success']);
        $this->assertNotNull($response['next_after_id']);

        // Validate creation of bank_account_2 and attributes
        $migratedVirtualAccount = $this->getDbEntityById(
            'virtual_account',
            $virtualAccount->getAttribute('id'),
            'live'
        );


        $bankAccount2Id = $migratedVirtualAccount->getAttribute('bank_account_id_2');
        $this->assertNotNull($bankAccount2Id);

        $bankAccount2 = $this->getDbEntityById(
            'bank_account',
            $bankAccount2Id,
            'live'
        );

        // Assert Axis IFSC code to migrated account IFSC
        $this->assertEquals('UTIB000RAZP', $bankAccount2['ifsc_code']);


    }

    public function testRblToAxisMigrationWithInvalidAccountPrefix()
    {
        // As part of RBL to Axis migration, we only migrate bank accounts with certain prefixes
        // Valid Account number prefixes: "2223", "2224", "VAJSWCA"
        $this->fixtures->merchant->on('live')->createAccount('migrateMerchnt');
        $this->fixtures->merchant->on('live')->enableMethod('migrateMerchnt', 'bank_transfer');

        // Virtual Account's with a non-migratable bank account
        $virtualAccount = $this->fixtures->on('live')->create(
            'virtual_account',
            [
                'merchant_id' => 'migrateMerchnt',
                'status' => 'active',
            ]
        );

        $bankAccount = $this->fixtures->on('live')->create(
            'bank_account',
            [
                'merchant_id' => 'migrateMerchnt',
                'entity_id' => $virtualAccount->getId(),
                'type' => 'virtual_account',
                'account_number' => '7878780085114245',
                'ifsc_code' => 'RATN0VAAPIS',
            ]
        );

        $this->fixtures->on('live')->edit(
            'virtual_account',
            $virtualAccount->getId(),
            ['bank_account_id' => $bankAccount->getId()]
        );

        $virtualAccountCreatedAt = $virtualAccount->getAttribute('created_at');

        // Create Input body and trigger Migration Job
        $input = $this->testData['testRblToAxisMigration'];
        $input['merchantIds'] = ['migrateMerchnt'];
        $input['from_time'] = $virtualAccountCreatedAt;
        $input['to_time'] = $virtualAccountCreatedAt;

        $response = (new VirtualAccount\Core)->bulkMigrateRblBank($input);

        $this->assertNotNull($response);
        $this->assertTrue($response['Success']);
        $this->assertNotNull($response['next_after_id']);

        // Validate that non-eligible virtual account wasn't migrated
        $nonMigratedVirtualAccount = $this->getDbEntityById(
            'virtual_account',
            $virtualAccount->getAttribute('id'),
            'live'
        );

        $this->assertNull($nonMigratedVirtualAccount->getAttribute('bank_account_id_2'));
    }

    public function testAxisValidationCallbackForCollectxWithAllowedPayer()
    {
        $testData = $this->testData['testValidateBankTransferAxis'];

        $response = $this->createCollectXVirtualAccount(receivers: ['bank_account']);

        $url = '/virtual_accounts/'.$response['id']. '/allowed_payers';

        $request = [
            'url' => $url,
            'method' => 'post',
            'content' => [
                'type'         => 'bank_account',
                'bank_account' => [
                    'ifsc'           => 'HDFC0000522',
                    'account_number' => '910910910910910'
                ],
            ]
        ];

        $response = $this->makeRequestAndGetContent($request);

        $beneAccountNo = $response['receivers'][0]['account_number'];

        $testData['request']['content']['Bene_acc_no'] = $beneAccountNo;

        $testData['request']['content']['Corp_code'] = '9845';

        $testData['request']['content']['Req_dt_time'] = date("Y-m-d H:i:s");

        $this->testData[__FUNCTION__] = $testData;

        $merchantID = '10000000000000';

        $this->enableSplitzExperiment(
            experimentName: RazorxTreatment::COLLECTX_AXIS_PAYMENT_TRANSFER_RAMP_UP,
            id: $merchantID,
            requestData: ['id' => $merchantID]);

        $response = $this->startTest();

        $this->assertEquals('S', $response['Stts_flg']);
        $this->assertEquals('000', $response['Err_cd']);
        $this->assertEquals('Success', $response['message']);
    }

    public function testAxisValidationCallbackForCollectxWithNonAllowedPayer()
    {
        $testData = $this->testData['testBankTransferAxisCallbackValidationFailure'];

        $response = $this->createCollectXVirtualAccount(receivers: ['bank_account']);

        $url = '/virtual_accounts/'.$response['id']. '/allowed_payers';

        $request = [
            'url' => $url,
            'method' => 'post',
            'content' => [
                'type'         => 'bank_account',
                'bank_account' => [
                    'ifsc'           => 'SBIN0000002',
                    'account_number' => '765432123456789'
                ],
            ]
        ];

        $response = $this->makeRequestAndGetContent($request);

        $beneAccountNo = $response['receivers'][0]['account_number'];

        $testData['request']['content']['Bene_acc_no'] = $beneAccountNo;

        $testData['request']['content']['Corp_code'] = '9845';

        $testData['request']['content']['Req_dt_time'] = date("Y-m-d H:i:s");

        $testData['request']['content']['Req_type'] = 'validation';

        $testData['request']['content']['UTR'] = 'RAZP00010742429600013';

        $this->testData[__FUNCTION__] = $testData;

        $merchantID = '10000000000000';

        $this->enableSplitzExperiment(
            experimentName: RazorxTreatment::COLLECTX_AXIS_PAYMENT_TRANSFER_RAMP_UP,
            id: $merchantID,
            requestData: ['id' => $merchantID]);

//        $isSuccess = False;

//        $this->ValidateExceptionThrownDuringCollectxBankTransferProcessing(ErrorCode::COLLECTX_UNEXPECTED_PAYMENT_BY_NON_ALLOWED_PAYER, $isSuccess);

        $response = $this->startTest();

        $this->assertEquals('F', $response['Stts_flg']);
        $this->assertEquals('002', $response['Err_cd']);
        $this->assertEquals('Validation failed', $response['message']);

//        $this->assertTrue($isSuccess);
    }

    public function testAxisValidationCallbackForCollectxWithAllowedPayer_ForTransferModeEmptySenderIfsc()
    {
        $testData = $this->testData['testValidateBankTransferAxis'];

        $response = $this->createCollectXVirtualAccount(receivers: ['bank_account']);

    $url = '/virtual_accounts/'.$response['id']. '/allowed_payers';

    $request = [
        'url' => $url,
        'method' => 'post',
        'content' => [
            'type'         => 'bank_account',
            'bank_account' => [
                'ifsc'           => 'HDFC0000522',
                'account_number' => '910910910910910'
            ],
        ]
    ];

    $response = $this->makeRequestAndGetContent($request);

    $beneAccountNo = $response['receivers'][0]['account_number'];

    $testData['request']['content']['Bene_acc_no'] = $beneAccountNo;

    $testData['request']['content']['Corp_code'] = '9845';

    $testData['request']['content']['Sndr_ifsc'] = '';

    $testData['request']['content']['Req_dt_time'] = date("Y-m-d H:i:s");

    $this->testData[__FUNCTION__] = $testData;

    $merchantID = '10000000000000';

    $this->enableSplitzExperiment(
        experimentName: RazorxTreatment::COLLECTX_AXIS_PAYMENT_TRANSFER_RAMP_UP,
        id: $merchantID,
        requestData: ['id' => $merchantID]);

    $response = $this->startTest();

    $this->assertEquals('S', $response['Stts_flg']);
    $this->assertEquals('000', $response['Err_cd']);
    $this->assertEquals('Success', $response['message']);
}

    public function testAxisValidationCallbackForCollectxWithNonAllowedPayer_ForInvalidTransferMode()
    {
        $testData = $this->testData['testBankTransferAxisCallbackValidationFailure'];

        $response = $this->createCollectXVirtualAccount(receivers: ['bank_account']);

        $url = '/virtual_accounts/'.$response['id']. '/allowed_payers';

        $request = [
            'url' => $url,
            'method' => 'post',
            'content' => [
                'type'         => 'bank_account',
                'bank_account' => [
                    'ifsc'           => 'SBIN0000002',
                    'account_number' => '765432123456789'
                ],
            ]
        ];

        $response = $this->makeRequestAndGetContent($request);

        $beneAccountNo = $response['receivers'][0]['account_number'];

        $testData['request']['content']['Bene_acc_no'] = $beneAccountNo;

        $testData['request']['content']['Sndr_ifsc'] = '';

        $testData['request']['content']['Corp_code'] = '9845';

        $testData['request']['content']['Req_dt_time'] = date("Y-m-d H:i:s");

        $testData['request']['content']['Req_type'] = 'validation';

        $testData['request']['content']['UTR'] = 'RAZP00010742429600013';

        $this->testData[__FUNCTION__] = $testData;

        $merchantID = '10000000000000';

        $this->enableSplitzExperiment(
            experimentName: RazorxTreatment::COLLECTX_AXIS_PAYMENT_TRANSFER_RAMP_UP,
            id: $merchantID,
            requestData: ['id' => $merchantID]);

//        $isSuccess = False;

//        $this->ValidateExceptionThrownDuringCollectxBankTransferProcessing(ErrorCode::COLLECTX_UNEXPECTED_PAYMENT_BY_NON_ALLOWED_PAYER, $isSuccess);

        $response = $this->startTest();

        $this->assertEquals('F', $response['Stts_flg']);
        $this->assertEquals('002', $response['Err_cd']);
        $this->assertEquals('Validation failed', $response['message']);

//        $this->assertTrue($isSuccess);
    }
}
