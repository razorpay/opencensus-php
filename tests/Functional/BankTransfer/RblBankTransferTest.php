<?php

namespace Functional\BankTransfer;

use DB;
use Mail;
use Cache;
use Mockery;
use RZP\Models\Feature;
use RZP\Error\ErrorCode;
use RZP\Models\Pricing\Fee;
use RZP\Models\Terminal\Type;
use RZP\Services\RazorXClient;
use RZP\Models\Payment\Gateway;
use RZP\Models\Admin\ConfigKey;
use Illuminate\Http\UploadedFile;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Traits\MocksSplitz;
use RZP\Models\VirtualAccount\Provider;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Models\BankTransfer\Status as S;
use RZP\Reconciliator\RequestProcessor\Base;
use RZP\Models\Admin\Service as AdminService;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Tests\Functional\FundTransfer\AttemptTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Unit\Models\Invoice\Traits\CreatesInvoice;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;
use RZP\Tests\Functional\FundTransfer\AttemptReconcileTrait;
use RZP\Models\BankAccount\Constants as BankAccountConstants;
use RZP\Tests\Functional\Helpers\VirtualAccount\VirtualAccountTrait;

class RblBankTransferTest extends TestCase
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

    public function testBankTransferProcess()
    {
        $accountNumber = $this->bankAccount['account_number'];
        $ifsc = $this->bankAccount['ifsc'];

        // Process API always returns true
        $response = $this->processBankTransfer($accountNumber, $ifsc);
        $this->assertEquals(true, $response['valid']);
        $this->assertNull($response['message']);

        // Created bank transfer is an expected one
        $bankTransfer =  $this->getLastEntity('bank_transfer', true);
        $this->assertEquals($accountNumber, $bankTransfer['payee_account']);
        $this->assertEquals($ifsc, $bankTransfer['payee_ifsc']);
        $this->assertEquals(true, $bankTransfer['expected']);
        $this->assertEquals(null, $bankTransfer['unexpected_reason']);
        $this->assertEquals(S::PROCESSED, $bankTransfer['status']);

        $this->assertNotNull($bankTransfer['payment_id']);

        // Payment is automatically captured
        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals('SHRDBANKACC3DS', $payment['terminal_id']);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals($bankTransfer['payment_id'], $payment['id']);
        $this->assertEquals('bank_account', $payment['receiver_type']);
        $this->assertEquals('bt_dashboard', $payment['gateway']);

        // Customer bank account created
        $bankAccount = $this->getDbLastEntity('bank_account');
        $bankAccount = $bankAccount->toArray();
        $this->assertEquals('HDFC0000001', $bankAccount['ifsc']);
        $this->assertEquals('9876543210123456789', $bankAccount['account_number']);
        $this->assertEquals('Name of account holder', $bankAccount['name']);

        $this->runBankTransferRequestAssertions(
            true,
            '',
            [
                'intended_virtual_account_id'   => $bankTransfer['virtual_account_id'],
                'actual_virtual_account_id'     => $bankTransfer['virtual_account_id'],
                'merchant_id'                   => $bankTransfer['merchant_id'],
                'bank_transfer_id'              => $bankTransfer['id'],
                'payment_id'                    => $bankTransfer['payment_id'],
            ]
        );
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

    protected function enableRazorXTreatmentForPayeeAccountLengthValidation()
    {
        $razorx = \Mockery::mock(RazorXClient::class)->makePartial();

        $this->app->instance('razorx', $razorx);

        $razorx->shouldReceive('getTreatment')
            ->andReturnUsing(function (string $id, string $featureFlag, string $mode)
            {
                if ($featureFlag === (RazorxTreatment::PAYEE_ACCOUNT_LENGTH_VALIDATION))
                {
                    return 'on';
                }
                return 'off';
            });
    }

    public function testFetchPaymentsPostRblMigration()
    {
        // skipping as the rbl nodal account is close
        $this->markTestSkipped();

        $accountNumber = $this->getIciciVaBankAccount();

        $terminalAttributes = [ 'id' =>'GENERICBANKRBL', 'gateway' => Gateway::BT_RBL, 'gateway_merchant_id' => '0001046' ];
        $this->fixtures->on('live')->create('terminal:shared_bank_account_terminal', $terminalAttributes);
        $this->fixtures->on('test')->create('terminal:shared_bank_account_terminal', $terminalAttributes);

        $bankAccount1LastEntity = $this->getDbLastEntity('bank_account');

//       replicated current bank account and set IFSC to RBL.
        $bankAccount2 = $this->fixtures->create(
            'bank_account',
            [
                'merchant_id'       => '10000000000000',
                'entity_id'         => substr($this->virtualAccountId, 3, strlen($this->virtualAccountId)),
                'type'              => 'virtual_account',
                'account_number'    =>  $accountNumber,
                'ifsc_code'         => 'RATN0VAAPIS',
            ]
        );

        $this->fixtures->edit(
            'virtual_account',
            $this->virtualAccountId,
            ['bank_account_id_2' => $bankAccount2->getId()]
        );

        $virtualAccount = $this->getDbLastEntity('virtual_account');
        $this->assertTrue($virtualAccount->hasBankAccount2());
        $this->assertEquals($virtualAccount->getAttribute('bank_account_id_2'), $bankAccount2->getId());
        $this->assertEquals($virtualAccount->getAttribute('bank_account_id'), $bankAccount1LastEntity->getId());

//        bank Tranfer on RBL

        $testData = $this->testData['testBankTransferRbl'];

        $testData['request']['content']['Data'][0]['beneficiaryAccountNumber'] = $accountNumber;

        $this->ba->directAuth();

        $this->startTest($testData);

        $bankTransfer2 =  $this->getLastEntity('bank_transfer', true);

        $this->assertEquals($bankTransfer2['narration'], $testData['request']['content']['Data'][0]['UTRNumber']);
        $this->assertEquals(343946, $bankTransfer2['amount']);

        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals(343946, $payment['amount']);
        $this->assertEquals($bankAccount2->getId(), $payment['receiver_id']);
        $this->assertEquals('bt_rbl', $payment['gateway']);

        $this->ba->proxyAuth();
        $request = [
            'url' => '/virtual_accounts/'.$this->virtualAccountId.'/payments',
            'method' => 'get',
            'content' => []
        ];

        $response = $this->makeRequestAndGetContent($request);
        $this->assertEquals($response['items'][0]['id'], $payment['id']);
    }

    protected function getIciciVaBankAccount($mode = 'test')
    {
        $terminalAttributes = [ 'id' =>'GENERICBANKICI', 'gateway' => Gateway::BT_ICICI, 'gateway_merchant_id' => '2244' ];
        $this->fixtures->on('live')->create('terminal:shared_bank_account_terminal', $terminalAttributes);
        $this->fixtures->on('test')->create('terminal:shared_bank_account_terminal', $terminalAttributes);

        $bankAccount = $this->createVirtualAccount($mode);

        return $bankAccount['account_number'];
    }

    public function testPaymentProcessRblWithNoSenderName()
    {
        // skipping as the rbl nodal account is closed
        $this->markTestSkipped();

        $testData = $this->testData['testBankTransferRbl'];
        $testData['request']['content']['Data'][0]['beneficiaryAccountNumber'] = $this->getRblVaBankAccount();
        $testData['request']['content']['Data'][0]['senderName'] = '';

        $this->ba->directAuth();

        $this->startTest($testData);

        $bankTransfer =  $this->getDbLastEntityToArray('bank_transfer', 'test');
        $this->assertEquals($bankTransfer['narration'], $testData['request']['content']['Data'][0]['UTRNumber']);
        $this->assertEquals(343946, $bankTransfer['amount']);
        $payment =  $this->getDbLastEntityToArray('payment', 'test');
        $this->assertEquals(343946, $payment['amount']);
        $this->assertEquals('bt_rbl', $payment['gateway']);
        $this->assertEquals(S::PROCESSED, $bankTransfer['status']);
    }

    public function testBankTransferRbl()
    {
        // skipping as the rbl nodal account is closed
        $this->markTestSkipped();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['Data'][0]['beneficiaryAccountNumber'] = $this->getRblVaBankAccount();

        $this->ba->directAuth();

        $this->startTest($testData);

        $bankTransfer =  $this->getLastEntity('bank_transfer', true);

        $this->assertEquals($bankTransfer['narration'], $testData['request']['content']['Data'][0]['UTRNumber']);
        $this->assertEquals(343946, $bankTransfer['amount']);

        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals(343946, $payment['amount']);
        $this->assertEquals('bt_rbl', $payment['gateway']);

        $payerBankAccount = $this->getEntityById('bank_account', $bankTransfer['payer_bank_account']['id'], true);
        $this->assertEquals($testData['request']['content']['Data'][0]['senderAccountNumber'], $payerBankAccount['account_number']);
    }

    public function testBankTransferRblUnexpected()
    {
        // skipping as the rbl nodal account is closed
        $this->markTestSkipped();

        $testData = $this->testData[__FUNCTION__];

        $account = $this->getRblVaBankAccount();

        $testData['request']['content']['Data'][0]['beneficiaryAccountNumber'] = 'RAND123';

        $this->ba->directAuth();

        $this->enableRazorXTreatmentForDisableRefundsUnexpectedPayment();
        $this->startTest($testData);

        $bankTransfer =  $this->getLastEntity('bank_transfer', true);

        $this->assertEquals($bankTransfer['narration'], $testData['request']['content']['Data'][0]['UTRNumber']);
        $this->assertEquals(343946, $bankTransfer['amount']);

        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals(343946, $payment['amount']);
        $this->assertEquals('bt_rbl', $payment['gateway']);
        $this->assertEquals('authorized', $payment['status']);
        $this->assertNull($payment['refund_at']);

        $payerBankAccount = $this->getEntityById('bank_account', $bankTransfer['payer_bank_account']['id'], true);
        $this->assertEquals($testData['request']['content']['Data'][0]['senderAccountNumber'], $payerBankAccount['account_number']);

        // Do a recon and verify if refund_at is being updated
        $data[] = $this->testData['reconDataForRefundDelay'];

        $file = $this->writeToExcelFile($data, 'virtualAccRbl2', 'files/settlement','Sheet1');

        $uploadedFile = $this->createUploadedFile($file, 'Sale Approved.xlsx');
        $this->reconcile($uploadedFile, Base::VIRTUAL_ACC_RBL);

        $transactionEntity = $this->getDbLastEntity('transaction');
        $this->assertNotNull($transactionEntity['reconciled_at']);

        $payment =  $this->getLastEntity('payment', true);
        $this->assertNotNull($payment['refund_at']);

    }

    public function testBankTransferRblWithLongSenderAccNumber()
    {
        // skipping as the rbl nodal account is closed
        $this->markTestSkipped();

        $testData = $this->testData['testBankTransferRbl'];

        $testData['request']['content']['Data'][0]['beneficiaryAccountNumber'] = $this->getRblVaBankAccount();

        $testData['request']['content']['Data'][0]['senderAccountNumber'] = 'KISHORKUMARKISHORKUMAR12345';

        $this->ba->directAuth();

        $this->startTest($testData);

        $bankTransfer =  $this->getLastEntity('bank_transfer', true);

        $this->assertEquals($bankTransfer['narration'], $testData['request']['content']['Data'][0]['UTRNumber']);
        $this->assertEquals(343946, $bankTransfer['amount']);

        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals(343946, $payment['amount']);
        $this->assertEquals('bt_rbl', $payment['gateway']);

        $payerBankAccount = $this->getEntityById('bank_account', $bankTransfer['payer_bank_account']['id'], true);
        $this->assertEquals($testData['request']['content']['Data'][0]['senderAccountNumber'], $payerBankAccount['account_number']);
    }

    public function testBankTransferRblWithSenderAccNumberExceedingLimit()
    {
        // skipping as the rbl nodal account is closed
        $this->markTestSkipped();

        $testData = $this->testData['testBankTransferRbl'];

        $testData['request']['content']['Data'][0]['beneficiaryAccountNumber'] = $this->getRblVaBankAccount();

        $testData['request']['content']['Data'][0]['senderAccountNumber'] = 'KISHORKUMARKISHORKUMAR1234567890123456';

        $this->ba->directAuth();

        $this->startTest($testData);

        $bankTransfer =  $this->getLastEntity('bank_transfer', true);

        $this->assertEquals($bankTransfer['narration'], $testData['request']['content']['Data'][0]['UTRNumber']);
        $this->assertEquals(343946, $bankTransfer['amount']);

        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals(343946, $payment['amount']);
        $this->assertEquals('bt_rbl', $payment['gateway']);

        $payerBankAccount = $this->getEntityById('bank_account', $bankTransfer['payer_bank_account']['id'], true);
        $this->assertNotEquals($testData['request']['content']['Data'][0]['senderAccountNumber'], $payerBankAccount['account_number']);
    }

    public function createUploadedFile(string $url, $fileName = 'file.xlsx', $mime = null): UploadedFile
    {
        $mime = $mime ?? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        return new UploadedFile(
            $url,
            $fileName,
            $mime,
            null,
            true);
    }

    public function testBankTransferRblUnexpectedWithRazorXOff()
    {
        // skipping as the rbl nodal account is closed
        $this->markTestSkipped();

        $testData = $this->testData['testBankTransferRblUnexpected'];

        $this->getRblVaBankAccount();

        $testData['request']['content']['Data'][0]['beneficiaryAccountNumber'] = 'RAND123';

        $this->ba->directAuth();

        $this->startTest($testData);

        $bankTransfer =  $this->getLastEntity('bank_transfer', true);

        $this->assertEquals($bankTransfer['narration'], $testData['request']['content']['Data'][0]['UTRNumber']);
        $this->assertEquals(343946, $bankTransfer['amount']);

        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals(343946, $payment['amount']);
        $this->assertEquals('bt_rbl', $payment['gateway']);
        $this->assertEquals('authorized', $payment['status']);
        $this->assertNotNull($payment['refund_at']);

        $payerBankAccount = $this->getEntityById('bank_account', $bankTransfer['payer_bank_account']['id'], true);
        $this->assertEquals($testData['request']['content']['Data'][0]['senderAccountNumber'], $payerBankAccount['account_number']);
    }

    public  function testBankTransferRblViaScService()
    {
        // skipping as the rbl nodal account is closed
        $this->markTestSkipped();

        $this->enableRazorXTreatmentForRoutingApiToScService();

        $testData = $this->testData['testBankTransferRbl'];

        $testData['request']['content']['Data'][0]['beneficiaryAccountNumber'] = $this->getRblVaBankAccount();

        $this->ba->directAuth();

        $response = $this->makeRequestAndGetContent($testData['request']);

        $this->assertEquals("Success", $response['Status']);

        $this->ba->smartCollectAuth();

        $processInternalTestData = $this->testData[__FUNCTION__];

        $this->startTest($processInternalTestData);

        $bankTransfer =  $this->getLastEntity('bank_transfer', true);

        $this->assertEquals($bankTransfer['narration'], $testData['request']['content']['Data'][0]['UTRNumber']);
        $this->assertEquals(343946, $bankTransfer['amount']);

        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals(343946, $payment['amount']);
        $this->assertEquals('bt_rbl', $payment['gateway']);
    }

    protected function enableRazorXTreatmentForRoutingApiToScService()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->will($this->returnCallback(
                              function($mid, $feature, $mode) {
                                  if ($feature === RazorxTreatment::SMARTCOLLECT_SERVICE_BANK_TRANSFER)
                                  {
                                      return 'on';
                                  }

                                  return 'off';
                              }));
    }

    public function testBankTransferRblJSW()
    {
        $testData = $this->testData['testBankTransferRbl'];

        $testData['request']['content']['Data'][0]['beneficiaryAccountNumber'] = $this->getRblJSWVaBankAccount();

        $this->ba->directAuth();

        $this->startTest($testData);

        $bankTransfer =  $this->getLastEntity('bank_transfer', true);

        $this->assertEquals($bankTransfer['narration'], $testData['request']['content']['Data'][0]['UTRNumber']);
        $this->assertEquals(343946, $bankTransfer['amount']);
        $this->assertEquals(Provider::IFSC[Provider::RBL_JSW], $bankTransfer['payee_ifsc']);

        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals(343946, $payment['amount']);
        $this->assertEquals(Gateway::BT_RBL_JSW, $payment['gateway']);
    }

    /**
     * Account number is less than 16 characters in length for some RBL VAs.
     */
    public function testRblBankTransferWithShortPayeeAccount()
    {
        // skipping as the rbl nodal account is closed
        $this->markTestSkipped();

        $testData = $this->testData['testBankTransferRbl'];
        $this->getRblVaBankAccount();

        // beneficiaryAccountNumber length < 12
        $testData['request']['content']['Data'][0]['beneficiaryAccountNumber'] = '22233300433';

        // Expected: Bank Transfer Created
        $this->startTest($testData);
    }

    public function testRblBankTransferWithShortPayeeAccountRazorXTreatmentEnabled() {

        // Enable RazorX treatment to assert payee_account length >= 12
        $this->enableRazorXTreatmentForPayeeAccountLengthValidation();
        $this->getRblVaBankAccount();

        $testData = $this->testData[__FUNCTION__];

        // Expected: Bank Transfer Failed
        $this->startTest($testData);
    }

    /**
     * Account number is greater than 16 characters in length for some RBL VAs.
     */
    public function testRblBankTransferWithLongPayeeAccount()
    {
        // skipping as the rbl nodal account is closed
        $this->markTestSkipped();

        $testData = $this->testData['testBankTransferRbl'];
        $testData['request']['content']['Data'][0]['beneficiaryAccountNumber'] = '22233300433504890';

        $this->startTest($testData);
    }

    public function testRblBankTransferWithAlphanumericPayeeAccount()
    {
        // skipping as the rbl nodal account is closed
        $this->markTestSkipped();

        $testData = $this->testData['testBankTransferRbl'];
        $testData['request']['content']['Data'][0]['beneficiaryAccountNumber'] = '222333AB43350485';

        $this->startTest($testData);
    }

    public function testRblBankTransferWithEmptyPayeeAccount()
    {
        $this->startTest($this->testData[__FUNCTION__]);
    }

    public function testRblBankTransferWithNonAlphanumericPayeeAccount()
    {
        $testData = $this->testData['testRblBankTransferWithEmptyPayeeAccount'];
        $testData['request']['content']['Data'][0]['beneficiaryAccountNumber'] = '2223330_43350485';

        $this->startTest($testData);
    }

    public function testBankTransferRblRefund()
    {
        // skipping as the rbl nodal account is closed
        $this->markTestSkipped();

        $this->createRblRefund(__FUNCTION__);
    }

    public function testEcollectRblBatchCreate()
    {
        $data = $this->testData['ecollectRblBatchData'];

        $this->createExcelFile($data, 'filename', 'files/filestore');

        $this->ba->h2hAuth();

        $this->startTest();
    }

    public function testEcollectRblCollectxBatchCreate()
    {
        $data = $this->testData['ecollectRblCollectxBatchData'];

        $this->createExcelFile($data, 'filename', 'files/filestore', extension: 'xls');

        $this->ba->h2hAuth();

        $this->startTest();
    }

    public function testBankTransferRblImps()
    {
        // skipping as the rbl nodal account is closed
        $this->markTestSkipped();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['Data'][0]['beneficiaryAccountNumber'] = $this->getRblVaBankAccount();

        $this->ba->directAuth();

        $this->makeRequestAndGetContent($testData['request']);

        $bankTransfer =  $this->getLastEntity('bank_transfer', true);

        $this->assertEquals($bankTransfer['narration'], $testData['request']['content']['Data'][0]['UTRNumber']);
        $this->assertEquals($bankTransfer['utr'], '006713653919');

        $this->startTest($testData);

        $bankTransfer =  $this->getLastEntity('bank_transfer', true);
        $this->assertEquals(343946, $bankTransfer['amount']);

        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals(343946, $payment['amount']);
        $this->assertEquals('bt_rbl', $payment['gateway']);
    }

    public function testBankTransferRblUpi()
    {
        // skipping as the rbl nodal account is closed
        $this->markTestSkipped();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['Data'][0]['beneficiaryAccountNumber'] = $this->getRblVaBankAccount();

        $this->ba->directAuth();

        $this->makeRequestAndGetContent($testData['request']);

        $bankTransfer =  $this->getLastEntity('bank_transfer', true);

        $this->assertEquals($bankTransfer['narration'], $testData['request']['content']['Data'][0]['UTRNumber']);
        $this->assertEquals($bankTransfer['utr'], '006713070094');

        $this->startTest($testData);

        $bankTransfer =  $this->getLastEntity('bank_transfer', true);
        $this->assertEquals(343946, $bankTransfer['amount']);

        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals(343946, $payment['amount']);
        $this->assertEquals('bt_rbl', $payment['gateway']);

        $this->ba->privateAuth();
    }

    public function testBankTransferRblIft()
    {
        // skipping as the rbl nodal account is closed
        $this->markTestSkipped();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['Data'][0]['beneficiaryAccountNumber'] = $this->getRblVaBankAccount();

        $this->ba->directAuth();

        $this->makeRequestAndGetContent($testData['request']);

        $bankTransfer =  $this->getLastEntity('bank_transfer', true);

        $this->assertEquals($bankTransfer['narration'], $testData['request']['content']['Data'][0]['UTRNumber']);
        $this->assertEquals($bankTransfer['utr'], '006713070094');

        $this->startTest($testData);

        $bankTransfer =  $this->getLastEntity('bank_transfer', true);
        $this->assertEquals(343946, $bankTransfer['amount']);

        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals(343946, $payment['amount']);
        $this->assertEquals('bt_rbl', $payment['gateway']);
    }

    public function testBankTransferRblWithInvalidData()
    {
        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['Data'][0]['beneficiaryAccountNumber'] = $this->getRblVaBankAccount();

        $this->ba->directAuth();

        $this->startTest($testData);
    }

    public function testBankTransferRblWithMissingHeader()
    {
        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['Data'][0]['beneficiaryAccountNumber'] = $this->getRblVaBankAccount();

        $this->ba->directAuth();

        $this->startTest($testData);
    }

    public function testBankTransferRblWithDuplicateUtr()
    {
        // skipping as the rbl nodal account is closed
        $this->markTestSkipped();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['Data'][0]['beneficiaryAccountNumber'] = $this->getRblVaBankAccount();

        $this->ba->directAuth();

        $this->makeRequestAndGetContent($testData['request']);

        $this->startTest($testData);
    }

    public function testBankTransferRblWithInternalServerError()
    {
        $this->ba->directAuth();

        $this->startTest();
    }

    public function testBankTransferRblWithEmptyFields()
    {
        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['Data'][0]['beneficiaryAccountNumber'] = $this->getRblVaBankAccount();

        $this->ba->directAuth();

        $this->startTest($testData);
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

    public function createCollectXVirtualAccount(
        $mode = 'test',
        $merchantID = '10000000000000',
        $receivers = ['bank_account'],
        $gateway = 'rbl')
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
                'account_type'     => 'direct',
                'account_number'   => '1234567890',
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

    protected function createRblRefund($callee)
    {
        $testData = $this->testData[$callee];

        $testData['request']['content']['Data'][0]['beneficiaryAccountNumber'] = $this->getRblVaBankAccount();

        $this->ba->directAuth();

        $this->startTest($testData);

        $bankTransfer =  $this->getLastEntity('bank_transfer', true);

        $this->assertEquals($bankTransfer['narration'], $testData['request']['content']['Data'][0]['UTRNumber']);
        $this->assertEquals(343946, $bankTransfer['amount']);

        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals(343946, $payment['amount']);
        $this->assertEquals('bt_rbl', $payment['gateway']);

        $this->gateway = 'bt_rbl';

        $this->refundPayment($payment['id'], 343946, ['is_fta' => true]);

        // Payment is refunded
        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('refunded', $payment['status']);
        $this->assertEquals(343946, $payment['amount_refunded']);

        // Refund is created
        $refund = $this->getDBLastEntity('refund');
        $this->assertEquals($payment['id'], 'pay_' . $refund['payment_id']);
        $this->assertEquals('created', $refund['status']);
        $this->assertEquals(343946, $refund['amount']);

        // Transaction is created for refund
        $transaction = $this->getLastEntity('transaction', true);
        $this->assertEquals('refund', $transaction['type']);
        $this->assertEquals('rfnd_' . $refund['id'], $transaction['entity_id']);

        // Fund transfer attempt created for refund
        $attempt = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertEquals('created', $attempt['status']);
        $this->assertEquals('rfnd_' . $refund['id'], $attempt['source']);
        $this->assertEquals('10000000000000', $attempt['merchant_id']);
        $this->assertEquals('Test Merchant-CMS480098890', $attempt['narration']);
    }

    public function testRblFallbackTerminal()
    {
        // skipping as the rbl nodal account is closed
        $this->markTestSkipped();

        $testData = $this->testData[__FUNCTION__];

        $terminalAttributes = [
            'id'                    =>'RblBtFlbkTrmnl',
            'gateway'               => Gateway::BT_RBL,
            'gateway_merchant_id'   => '1112',
            'gateway_merchant_id2'  => '',
        ];

        $this->fixtures->on('live')->create('terminal:shared_bank_account_terminal', $terminalAttributes);
        $this->fixtures->on('test')->create('terminal:shared_bank_account_terminal', $terminalAttributes);

        $this->ba->directAuth();

        $this->startTest($testData);

        $bankTransfer = $this->getLastEntity('bank_transfer', true);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('va_ShrdVirtualAcc', $bankTransfer['virtual_account_id']);

        $this->assertEquals('bt_rbl', $payment['gateway']);

        $this->assertEquals('RblBtFlbkTrmnl', $payment['terminal_id']);

        $this->assertEquals(100000, $payment['amount']);
    }

    public function testRblBankTransferWithNoMatchingTerminal()
    {
        // skipping as the rbl nodal account is closed
        $this->markTestSkipped();

        $terminalAttributes = [
            'id'                    =>'RblBtShrdTrmnl',
            'gateway'               => Gateway::BT_RBL,
            'gateway_merchant_id'   => '111222',
            'gateway_merchant_id2'  => '',
            'shared'                => true,
            'bank_transfer'         => true,
        ];

        $this->fixtures->on('test')->create('terminal:shared_bank_account_terminal', $terminalAttributes);

        $this->startTest();

        $bankTransfer = $this->getLastEntity('bank_transfer', true);
        $this->assertEquals('va_ShrdVirtualAcc', $bankTransfer['virtual_account_id']);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('bt_rbl', $payment['gateway']);
        $this->assertEquals('authorized', $payment['status']);
        $this->assertEquals('RblBtShrdTrmnl', $payment['terminal_id']);
    }

    public function testRblDeletedReceiverDisallow()
    {
        $this->fixtures->merchant->on('live')->createAccount('TestMerchant00');
        $this->fixtures->merchant->on('live')->enableMethod('TestMerchant00', 'bank_transfer');

        $virtualAccount = $this->fixtures->on('live')->create(
            'virtual_account',
            [
                'merchant_id' => 'TestMerchant00',
                'status' => 'active',
            ]
        );

        $bankAccount = $this->fixtures->on('live')->create(
            'bank_account',
            [
                'merchant_id' => 'TestMerchant00',
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

        $bankAccount2 = $this->fixtures->on('live')->create(
            'bank_account',
            [
                'merchant_id' => 'TestMerchant00',
                'entity_id' => $virtualAccount->getId(),
                'type' => 'virtual_account',
                'account_number' => '2223780085114246',
                'ifsc_code' => 'UTIB000RAZP',
            ]
        );

        $this->fixtures->on('live')->edit(
            'virtual_account',
            $virtualAccount->getId(),
            ['bank_account_id_2' => $bankAccount2->getId()]
        );

        // Using reflection since VirtualAccount->getReceiversAttribute is protected.
        $reflection = new \ReflectionClass($virtualAccount);
        $method = $reflection->getMethod('getReceiversAttribute');
        $method->setAccessible(true);

        $virtualAccountUpdated = $this->getDbEntityById('virtual_account', $virtualAccount->getId(), 'live');

        $receivers = $method->invoke($virtualAccountUpdated);
        $this->assertCount(2, $receivers);

        // Soft delete RBL bank Account
        $this->fixtures->on('live')->edit(
            'bank_account',
            $bankAccount->getId(),
            ['deleted_at' => time()]
        );

        $virtualAccountUpdated = $this->getDbEntityById('virtual_account', $virtualAccount->getId(), 'live');
        $receivers = $method->invoke($virtualAccountUpdated);

        // RBL receiver disabled if its soft deleted.
        $this->assertCount(1, $receivers);
        $this->assertEquals("UTIB000RAZP", $receivers[0]['ifsc']);

    }

    protected function getRblVaBankAccount()
    {
        $terminalAttributes = [ 'id' =>'GENERICBANKRBL', 'gateway' => Gateway::BT_RBL, 'gateway_merchant_id' => '0001046' ];
        $this->fixtures->on('live')->create('terminal:shared_bank_account_terminal', $terminalAttributes);
        $this->fixtures->on('test')->create('terminal:shared_bank_account_terminal', $terminalAttributes);

        $bankAccount = $this->createVirtualAccount();

        return $bankAccount['account_number'];
    }

    protected function getRblJSWVaBankAccount()
    {
        $terminalAttributes = [ 'id' =>'GENERICBANKRBL', 'gateway' => Gateway::BT_RBL_JSW, 'gateway_merchant_id' => 'VAJSW' ];
        $this->fixtures->on('live')->create('terminal:shared_bank_account_terminal', $terminalAttributes);
        $this->fixtures->on('test')->create('terminal:shared_bank_account_terminal', $terminalAttributes);

        $bankAccount = $this->createVirtualAccount();

        return $bankAccount['account_number'];
    }

    protected function runBankTransferRequestAssertions(bool $isCreated, string $errorMessage, $expectedValues = [], $mode = 'test')
    {
        $bankTransferRequest = $this->getDbLastEntity('bank_transfer_request', $mode);

        $this->assertNotNull($bankTransferRequest['request_payload']);
        $this->assertEquals($isCreated, $bankTransferRequest['is_created']);
        $this->assertEquals($errorMessage, $bankTransferRequest['error_message']);

        if (empty($expectedValues) === true)
        {
            return;
        }

        $this->ba->adminAuth($mode);
        $testData = $this->testData['adminFetchBankTransferRequest'];
        $testData['request']['url'] .= $bankTransferRequest->getPublicId();
        $bankTransferRequest = $this->startTest($testData);

        $this->assertArraySelectiveEquals($expectedValues, $bankTransferRequest);
    }

    public function testRblCallbackForCollectx()
    {
        $this->app['config']->set('gateway.mock_bt_rbl', true);

        $testData = $this->testData['testBankTransferRblCollectx'];

        $merchantID = '10000000000000';

        $response = $this->createCollectXVirtualAccount(merchantID: $merchantID, receivers: ['bank_account']);

        $beneAccountNo = $response['receivers'][0]['account_number'];

        $testData['request']['content']['Data'][0]['beneficiaryAccountNumber'] = $beneAccountNo;

        $this->testData[__FUNCTION__] = $testData;

        $this->enableSplitzExperiment(
            experimentName: RazorxTreatment::COLLECTX_RBL_PAYMENT_TRANSFER_RAMP_UP,
            id: $merchantID,
            requestData: ['id' => $merchantID]);

        $response = $this->startTest();

        $this->assertEquals('Success', $response['Status']);

        $bankTransferRequest = $this->getLastEntity('bank_transfer_request', true);

        $this->assertTrue($bankTransferRequest['is_created']);
        $this->assertNotNull($bankTransferRequest['payee_account']);

        $bankTransfer =  $this->getLastEntity('bank_transfer', true);

        $this->assertEquals($bankTransfer['narration'], $testData['request']['content']['Data'][0]['UTRNumber']);
        $this->assertEquals(200, $bankTransfer['amount']);
        $this->assertEquals('processed', $bankTransfer['status']);
        $this->assertEquals('RTGS', $bankTransfer['mode']);
        $this->assertTrue($bankTransfer['expected']);
        $this->assertEquals(null, $bankTransfer['unexpected_reason']);
        $this->assertNotNull($bankTransfer['payment_id']);

        $payerBankAccount = $this->getEntityById('bank_account', $bankTransfer['payer_bank_account']['id'], true);
        $this->assertEquals($testData['request']['content']['Data'][0]['senderAccountNumber'], $payerBankAccount['account_number']);

        $payment =  $this->getLastEntity('payment', true);

        $this->assertEquals(200, $payment['amount']);
        $this->assertEquals('bt_rbl', $payment['gateway']);
        $this->assertEquals('10000000000001', $payment['terminal_id']);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals($bankTransfer['payment_id'], $payment['id']);
        $this->assertEquals('bank_account', $payment['receiver_type']);
        $this->assertEquals('collectx', $payment['reference14']);
        $this->assertTrue($payment['auto_captured']);

        $txn =  $this->getLastEntity('transaction', true);

        $this->assertEquals($payment['id'], $txn['entity_id']);
        $this->assertEquals(0, $txn['credit']);
    }

    public function testRblCallbackForCollectxWithNonAllowedPayer()
    {
        $this->app['config']->set('gateway.mock_bt_rbl', true);

        $testData = $this->testData['testBankTransferRblCollectx'];

        $merchantID = '10000000000000';

        $response = $this->createCollectXVirtualAccount(merchantID: $merchantID, receivers: ['bank_account']);

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

        $testData['request']['content']['Data'][0]['beneficiaryAccountNumber'] = $beneAccountNo;

        $this->testData[__FUNCTION__] = $testData;

        $this->enableSplitzExperiment(
            experimentName: RazorxTreatment::COLLECTX_RBL_PAYMENT_TRANSFER_RAMP_UP,
            id: $merchantID,
            requestData: ['id' => $merchantID]);

        $response = $this->startTest();

        $this->assertEquals('Success', $response['Status']);

        $bankTransferRequest = $this->getLastEntity('bank_transfer_request', true);

        $this->assertTrue($bankTransferRequest['is_created']);
        $this->assertNotNull($bankTransferRequest['payee_account']);

        $bankTransfer =  $this->getLastEntity('bank_transfer', true);

        $this->assertEquals($bankTransfer['narration'], $testData['request']['content']['Data'][0]['UTRNumber']);
        $this->assertEquals(200, $bankTransfer['amount']);
        $this->assertEquals('processed', $bankTransfer['status']);
        $this->assertEquals('RTGS', $bankTransfer['mode']);
        $this->assertTrue($bankTransfer['expected']);
        $this->assertEquals(null, $bankTransfer['unexpected_reason']);
        $this->assertNotNull($bankTransfer['payment_id']);

        $payerBankAccount = $this->getEntityById('bank_account', $bankTransfer['payer_bank_account']['id'], true);
        $this->assertEquals($testData['request']['content']['Data'][0]['senderAccountNumber'], $payerBankAccount['account_number']);

        $payment =  $this->getLastEntity('payment', true);

        $this->assertEquals(200, $payment['amount']);
        $this->assertEquals('bt_rbl', $payment['gateway']);
        $this->assertEquals('10000000000001', $payment['terminal_id']);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals($bankTransfer['payment_id'], $payment['id']);
        $this->assertEquals('bank_account', $payment['receiver_type']);
        $this->assertEquals('collectx', $payment['reference14']);
        $this->assertTrue($payment['auto_captured']);

        $txn =  $this->getLastEntity('transaction', true);

        $this->assertEquals($payment['id'], $txn['entity_id']);
        $this->assertEquals(0, $txn['credit']);
    }

    // If we received a webhook from bank that was for collectx but our check failed to identify it, the request will be processed as normal fundloading
    // transfer (because balance type is banking). Payment entity won't be created, but BT and BTR both will be created and will be in processed state
    public function testRblCallbackForCollectx_WithExperimentDisabled()
    {
        // skipping as the rbl nodal account is closed
        $this->markTestSkipped();

        $this->app['config']->set('gateway.mock_bt_rbl', true);

        $testData = $this->testData['testBankTransferRblCollectx'];

        $merchantID = '10000000000000';

        $response = $this->createCollectXVirtualAccount(merchantID: $merchantID, receivers: ['bank_account']);

        $beneAccountNo = $response['receivers'][0]['account_number'];

        $testData['request']['content']['Data'][0]['beneficiaryAccountNumber'] = $beneAccountNo;

        $this->testData[__FUNCTION__] = $testData;

        $response = $this->startTest();

        $this->assertEquals('Success', $response['Status']);

        $bankTransferRequest = $this->getLastEntity('bank_transfer_request', true);

        $this->assertTrue($bankTransferRequest['is_created']);
        $this->assertNotNull($bankTransferRequest['payee_account']);

        $bankTransfer =  $this->getLastEntity('bank_transfer', true);

        $this->assertEquals($bankTransfer['narration'], $testData['request']['content']['Data'][0]['UTRNumber']);
        $this->assertEquals(200, $bankTransfer['amount']);
        $this->assertEquals('processed', $bankTransfer['status']);
        $this->assertEquals('RTGS', $bankTransfer['mode']);
        $this->assertTrue($bankTransfer['expected']);
        $this->assertEquals(null, $bankTransfer['unexpected_reason']);
        $this->assertNull($bankTransfer['payment_id']);
    }

    public function testRblCallbackForCollectx_IMPS_Mode_UPI_Type_Transfer()
    {
        $this->app['config']->set('gateway.mock_bt_rbl', true);

        $testData = $this->testData['testBankTransferRblCollectxUpi'];

        $response = $this->createCollectXVirtualAccount(receivers: ['bank_account']);

        $beneAccountNo = $response['receivers'][0]['account_number'];

        $testData['request']['content']['Data'][0]['beneficiaryAccountNumber'] = $beneAccountNo;

        $this->testData[__FUNCTION__] = $testData;

        $merchantID = '10000000000000';

        $this->enableSplitzExperiment(
            experimentName: RazorxTreatment::COLLECTX_RBL_PAYMENT_TRANSFER_RAMP_UP,
            id: $merchantID,
            requestData: ['id' => $merchantID]);

        $response = $this->startTest();

        $this->assertEquals('Success', $response['Status']);

        $bankTransferRequest = $this->getLastEntity('bank_transfer_request', true);

        $this->assertTrue($bankTransferRequest['is_created']);
        $this->assertNotNull($bankTransferRequest['payee_account']);

        $bankTransfer =  $this->getLastEntity('bank_transfer', true);

        $this->assertEquals($bankTransfer['narration'], $testData['request']['content']['Data'][0]['UTRNumber']);
        $this->assertEquals(200, $bankTransfer['amount']);
        $this->assertEquals('processed', $bankTransfer['status']);
        $this->assertEquals('IMPS', $bankTransfer['mode']);
        $this->assertTrue($bankTransfer['expected']);
        $this->assertEquals(null, $bankTransfer['unexpected_reason']);
        $this->assertNotNull($bankTransfer['payment_id']);

        $payerBankAccount = $this->getEntityById('bank_account', $bankTransfer['payer_bank_account']['id'], true);
        $this->assertEquals($testData['request']['content']['Data'][0]['senderAccountNumber'], $payerBankAccount['account_number']);

        $payment =  $this->getLastEntity('payment', true);

        $this->assertEquals(200, $payment['amount']);
        $this->assertEquals('bt_rbl', $payment['gateway']);
        $this->assertEquals('10000000000001', $payment['terminal_id']);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals($bankTransfer['payment_id'], $payment['id']);
        $this->assertEquals('bank_account', $payment['receiver_type']);
        $this->assertEquals('collectx', $payment['reference14']);
        $this->assertTrue($payment['auto_captured']);

        $txn =  $this->getLastEntity('transaction', true);

        $this->assertEquals($payment['id'], $txn['entity_id']);
        $this->assertEquals(0, $txn['credit']);
    }

    public function testRblCallbackForCollectx_DuplicateBankTransfer()
    {
        Mockery::close();

        $this->app['config']->set('gateway.mock_bt_rbl', true);

        $testData = $this->testData['testBankTransferRblCollectx'];

        $response = $this->createCollectXVirtualAccount(receivers: ['bank_account']);

        $beneAccountNo = $response['receivers'][0]['account_number'];

        $this->fixtures->create('bank_transfer', [
            'utr'            => 'CMS480098890',
            'amount'         => 2,
            'payee_account'  => $beneAccountNo,
            'merchant_id'    => '10000000000000']);

        $testData['request']['content']['Data'][0]['beneficiaryAccountNumber'] = $beneAccountNo;

        $testData['request']['content']['Data'][0]['messageType'] = 'rtgs';

        $testData['response']['content']['Status'] = 'Failure.';

        $testData['response']['status_code'] = 400;

        $this->testData[__FUNCTION__] = $testData;

        $merchantID = '10000000000000';

        $this->enableSplitzExperiment(
            experimentName: RazorxTreatment::COLLECTX_RBL_PAYMENT_TRANSFER_RAMP_UP,
            id: $merchantID,
            requestData: ['id' => $merchantID]);

//        $isSuccess = false;
//
//        $this->ValidateExceptionThrownDuringCollectxBankTransferProcessing(ErrorCode::BAD_REQUEST_DUPLICATE_BANK_TRANSFER_CALLBACK, $isSuccess);

        $response = $this->startTest();

        $this->assertEquals('Failure.', $response['Status']);

        $bankTransferRequest = $this->getLastEntity('bank_transfer_request', true);

        $this->assertFalse($bankTransferRequest['is_created']);

        $this->assertEquals(ErrorCode::BAD_REQUEST_DUPLICATE_BANK_TRANSFER_CALLBACK ,$bankTransferRequest['error_message']);

//        $this->assertTrue($isSuccess);

        Mockery::close();
    }

    public function testRblCallbackForCollectx_ClosedVaBankTransferTransfer()
    {
        Mockery::close();

        $this->app['config']->set('gateway.mock_bt_rbl', true);

        $testData = $this->testData['testBankTransferRblCollectx'];

        $response = $this->createCollectXVirtualAccount(receivers: ['bank_account']);

        $beneAccountNo = $response['receivers'][0]['account_number'];

        $this->fixtures->edit('virtual_account', $response['id'], ['status' => 'closed']);

        $testData['request']['content']['Data'][0]['beneficiaryAccountNumber'] = $beneAccountNo;

        $testData['request']['content']['Data'][0]['messageType'] = 'rtgs';

        $testData['response']['content']['Status'] = 'Failure.';

        $testData['response']['status_code'] = 400;

        $this->testData[__FUNCTION__] = $testData;

        $merchantID = '10000000000000';

        $this->enableSplitzExperiment(
            experimentName: RazorxTreatment::COLLECTX_RBL_PAYMENT_TRANSFER_RAMP_UP,
            id: $merchantID,
            requestData: ['id' => $merchantID]);

        $this->testData[__FUNCTION__] = $testData;

//        $isSuccess = false;
//
//        $this->ValidateExceptionThrownDuringCollectxBankTransferProcessing(ErrorCode::COLLECTX_UNEXPECTED_PAYMENT_ON_CLOSED_VA, $isSuccess);

        $response = $this->startTest();

        $this->assertEquals('Failure.', $response['Status']);

        $bankTransferRequest = $this->getLastEntity('bank_transfer_request', true);

        $this->assertFalse($bankTransferRequest['is_created']);

        $this->assertEquals(ErrorCode::COLLECTX_UNEXPECTED_PAYMENT_ON_CLOSED_VA ,$bankTransferRequest['error_message']);

//        $this->assertTrue($isSuccess);

        Mockery::close();
    }

    public function testRblCallbackForCollectx_VaNotFound()
    {
        Mockery::close();

        // skipping as the rbl nodal account is closed
        $this->markTestSkipped();

        $this->app['config']->set('gateway.mock_bt_rbl', true);

        $testData = $this->testData['testBankTransferRblCollectx'];

        $response = $this->createCollectXVirtualAccount(receivers: ['bank_account']);

        $beneAccountNo = 'InvalidAccountNumber';

        $testData['request']['content']['Data'][0]['beneficiaryAccountNumber'] = $beneAccountNo;

        $this->testData[__FUNCTION__] = $testData;

//        $isSuccess = false;
//
//        $this->ValidateExceptionThrownDuringCollectxBankTransferProcessing(ErrorCode::COLLECTX_VIRTUAL_ACCOUNT_NOT_FOUND, $isSuccess);

        $response = $this->startTest();

        $this->assertEquals('Success', $response['Status']);

        $bankTransferRequest = $this->getLastEntity('bank_transfer_request', true);

        $this->assertFalse($bankTransferRequest['is_created']);

        $this->assertNotNull($bankTransferRequest['payee_account']);

        $bankTransferRequest = $this->getLastEntity('bank_transfer_request', true);

        $this->assertFalse($bankTransferRequest['is_created']);

        $this->assertEquals(ErrorCode::COLLECTX_VIRTUAL_ACCOUNT_NOT_FOUND ,$bankTransferRequest['error_message']);

//        $this->assertTrue($isSuccess);

        Mockery::close();
    }

    public function testRblCallbackForCollectx_MigratedVABankTransfer()
    {
        $this->app['config']->set('gateway.mock_bt_rbl', true);

        $testData = $this->testData['testBankTransferRblCollectx'];

        $response = $this->createCollectXVirtualAccount(receivers: ['bank_account']);

        $beneAccountNo = $response['receivers'][0]['account_number'];

        $testData['request']['content']['Data'][0]['beneficiaryAccountNumber'] = $beneAccountNo;

        $this->testData[__FUNCTION__] = $testData;

        $merchantID = '10000000000000';

        $this->enableSplitzExperiment(
            experimentName: RazorxTreatment::COLLECTX_RBL_PAYMENT_TRANSFER_RAMP_UP,
            id: $merchantID,
            requestData: ['id' => $merchantID]);

        $bankTransferTerminalAttributes = [
            'id' => '10000000000004',
            'gateway' => "bt_rbl",
            'merchant_id' => '10000000000000',
            'gateway_merchant_id' => 'DUMMY_SERIES',
            'bank_transfer' => 1,
            'enabled' => 1,
            'type' => [
                Type::NON_RECURRING => '1',
                Type::NUMERIC_ACCOUNT => '1',
                Type::DIRECT_SETTLEMENT_WITH_REFUND => '1'
            ],
        ];

        $this->fixtures->on('test')->create('terminal:bank_account_terminal', $bankTransferTerminalAttributes);

        (new AdminService())->setConfigKeys([ConfigKey::COLLECTX_SERIES_PREFIX => [
            '10000000000000' => 'DUMMY_SERIES'
        ]]);

        $response = $this->startTest();

        $this->assertEquals('Success', $response['Status']);

        $bankTransferRequest = $this->getLastEntity('bank_transfer_request', true);

        $this->assertTrue($bankTransferRequest['is_created']);
        $this->assertNotNull($bankTransferRequest['payee_account']);

        $bankTransfer =  $this->getLastEntity('bank_transfer', true);

        $this->assertEquals($bankTransfer['narration'], $testData['request']['content']['Data'][0]['UTRNumber']);
        $this->assertEquals(200, $bankTransfer['amount']);
        $this->assertEquals('processed', $bankTransfer['status']);
        $this->assertEquals('RTGS', $bankTransfer['mode']);
        $this->assertTrue($bankTransfer['expected']);
        $this->assertEquals(null, $bankTransfer['unexpected_reason']);
        $this->assertNotNull($bankTransfer['payment_id']);

        $payerBankAccount = $this->getEntityById('bank_account', $bankTransfer['payer_bank_account']['id'], true);
        $this->assertEquals($testData['request']['content']['Data'][0]['senderAccountNumber'], $payerBankAccount['account_number']);

        $payment =  $this->getLastEntity('payment', true);

        $this->assertEquals(200, $payment['amount']);
        $this->assertEquals('bt_rbl', $payment['gateway']);
        $this->assertEquals('10000000000004', $payment['terminal_id']);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals($bankTransfer['payment_id'], $payment['id']);
        $this->assertEquals('bank_account', $payment['receiver_type']);
        $this->assertTrue($payment['auto_captured']);

        $txn =  $this->getLastEntity('transaction', true);

        $this->assertEquals($payment['id'], $txn['entity_id']);
        $this->assertEquals(0, $txn['credit']);
    }

    public function testRblCallbackForCollectx_MigratedVABankTransferTransfer_TerminalNotFound()
    {
        $this->app['config']->set('gateway.mock_bt_rbl', true);

        $testData = $this->testData['testBankTransferRblCollectx'];

        $response = $this->createCollectXVirtualAccount(receivers: ['bank_account']);

        $beneAccountNo = $response['receivers'][0]['account_number'];

        $testData['request']['content']['Data'][0]['beneficiaryAccountNumber'] = $beneAccountNo;

        $testData['request']['content']['Data'][0]['creditAccountNumber'] = '1234567890';

        $this->testData[__FUNCTION__] = $testData;

        $merchantID = '10000000000000';

        $this->enableSplitzExperiment(
            experimentName: RazorxTreatment::COLLECTX_RBL_PAYMENT_TRANSFER_RAMP_UP,
            id: $merchantID,
            requestData: ['id' => $merchantID]);

        (new AdminService())->setConfigKeys([ConfigKey::COLLECTX_SERIES_PREFIX => [
            '10000000000000' => 'DUMMY_SERIES'
        ]]);

        $response = $this->startTest();

        $this->assertEquals('Success', $response['Status']);

        $bankTransferRequest = $this->getLastEntity('bank_transfer_request', true);

        $this->assertFalse($bankTransferRequest['is_created']);
    }
}
