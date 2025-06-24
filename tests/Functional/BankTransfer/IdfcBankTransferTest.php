<?php

namespace Functional\BankTransfer;

use DateTime;
use DB;
use Mail;
use Cache;
use RZP\Http\Controllers\BankTransferController;
use RZP\Models\Admin\Service;
use RZP\Models\BankTransfer\Entity;
use RZP\Models\BankTransfer\Processor;
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
use RZP\Models\BankTransfer\Service as BankTransferService;
use RZP\Trace\TraceCode;


class IdfcBankTransferTest extends TestCase
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

        // Add the missing method to the xpayrollMock
        $this->xpayrollMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['sendPayrollTpvRequestAndGetResponse'])
            ->getMock();

        $this->traceMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['info'])
            ->getMock();

        $this->processor = $this->getMockBuilder(Processor::class)
            ->disableOriginalConstructor()
            ->addMethods(['app'])
            ->getMock();

        // Use reflection to set protected properties
        $reflection = new \ReflectionClass($this->processor);

        $traceProperty = $reflection->getProperty('trace');
        $traceProperty->setAccessible(true);
        $traceProperty->setValue($this->processor, $this->traceMock);

        $appProperty = $reflection->getProperty('app');
        $appProperty->setAccessible(true);
        $appProperty->setValue($this->processor, ['xpayroll' => $this->xpayrollMock]);
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

    protected function getIdfcVaBankAccount($series ='5678')
    {
        $terminalAttributes = [ 'id' =>'GENERICBNKIDFC', 'gateway' => Gateway::BT_IDFC, 'gateway_merchant_id' => $series ];
        $this->fixtures->on('live')->create('terminal:shared_bank_account_terminal', $terminalAttributes);
        $this->fixtures->on('test')->create('terminal:shared_bank_account_terminal', $terminalAttributes);

        $bankAccount = $this->createVirtualAccount();

        return $bankAccount['account_number'];
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

    // We won't be sending the IFSC details here
    public function testValidateBankTransferIdfcForIMPSMode()
    {
        $testData = $this->testData['testValidateBankTransferIdfc'];
        $accountNumber = $this->getIdfcVaBankAccount('3141');
        $testData['request']['content']['VANum'] = $accountNumber;
        $testData['request']['content']['remitterBankifsc'] = "";
        $balance1 = $this->getDbEntity('balance',
            [
                'merchant_id' => '10000000000000',
            ], 'live');

        // This makes sure that the refunds for failed fund loadings on X happen via X
        (new Service)->setConfigKeys([ConfigKey::RX_FUND_LOADING_REFUNDS_VIA_X => true]);

        $this->fixtures->on('test')->edit('balance', $balance1->getId(), [
            'type'           => 'banking',
            'account_number' => $accountNumber,
        ]);

        $ba = $this->fixtures->on('test')->create('bank_account',
            [
                'merchant_id'    => '10000000000000',
                'entity_id'      => '100000000000va',
                'type'           => 'virtual_account',
                'ifsc_code'      => 'IDFB0020101',  //validate if its correct ifsc code
                'account_number' => $accountNumber,
            ]);

        $this->fixtures->on('test')->create('virtual_account',
            [
                'id'              => '100000000000va',
                'merchant_id'     => '10000000000000',
                'status'          => 'active',
                'bank_account_id' => $ba->getId(),
                'balance_id'      => $balance1->getId(),
            ]);

        $this->fixtures->on('test')->create('banking_account_tpv',
            [
                'balance_id' => $balance1->getId(),
                'status'     => 'approved',
                'payer_ifsc' => 'HDFC0003981',
                'payer_account_number' => '45612345678900987'
            ]);

        $bankTransferService = new BankTransferService();
        $iv = random_string_special_chars(16);
        $encryptedData = $bankTransferService->encryptIdfcBankCallbackData($testData['request']['content'], $iv);

        $this->ba->directAuth();

        $request = [
            'url' => '/ecollect/validate/idfc/test',
            'method' => 'POST',
            'server' => $testData['request']['server'],
            'raw' => $encryptedData, // Raw data goes here
            'headers' => [
                'Content-Type' => 'text/plain'
            ],
        ];

        $encryptedFullResponse = $this->makeRequestAndGetRawContent($request);
        $response = $bankTransferService->decryptIdfcBankCallbackData((string)$encryptedFullResponse->getContent());

        $this->assertEquals($testData['response']['content']['status'],$response['status']);
        $this->assertEquals($testData['response']['content']['statusDesc'],$response['statusDesc']);
        $this->assertEquals($testData['response']['content']['errorCode'], $response['errorCode']);
        $this->assertEquals($testData['response']['content']['errorDesc'], $response['errorDesc']);
        $this->assertEquals($testData['request']['content']['VANum'],$response['vANum']);
        $this->assertEquals($testData['request']['content']['bankRef'],$response['bankRef']);
    }

    public function testValidateBankTransferIdfcForIMPSModeNonPayrollSeries()
    {
        $testData = $this->testData['testValidateBankTransferIdfc'];
        $accountNumber = $this->getIdfcVaBankAccount('5678');
        $testData['request']['content']['VANum'] = $accountNumber;
        $testData['request']['content']['remitterBankifsc'] = "";
        $balance1 = $this->getDbEntity('balance',
            [
                'merchant_id' => '10000000000000',
            ], 'live');

        // This makes sure that the refunds for failed fund loadings on X happen via X
        (new Service)->setConfigKeys([ConfigKey::RX_FUND_LOADING_REFUNDS_VIA_X => true]);

        $this->fixtures->on('test')->edit('balance', $balance1->getId(), [
            'type'           => 'banking',
            'account_number' => $accountNumber,
        ]);

        $ba = $this->fixtures->on('test')->create('bank_account',
            [
                'merchant_id'    => '10000000000000',
                'entity_id'      => '100000000000va',
                'type'           => 'virtual_account',
                'ifsc_code'      => 'IDFB0020101',  //validate if its correct ifsc code
                'account_number' => $accountNumber,
            ]);

        $this->fixtures->on('test')->create('virtual_account',
            [
                'id'              => '100000000000va',
                'merchant_id'     => '10000000000000',
                'status'          => 'active',
                'bank_account_id' => $ba->getId(),
                'balance_id'      => $balance1->getId(),
            ]);

        $this->fixtures->on('test')->create('banking_account_tpv',
            [
                'balance_id' => $balance1->getId(),
                'status'     => 'approved',
                'payer_ifsc' => 'HDFC0003981',
                'payer_account_number' => '45612345678900987'
            ]);

        $bankTransferService = new BankTransferService();
        $iv = random_string_special_chars(16);
        $encryptedData = $bankTransferService->encryptIdfcBankCallbackData($testData['request']['content'], $iv);

        $this->ba->directAuth();

        $request = [
            'url' => '/ecollect/validate/idfc/test',
            'method' => 'POST',
            'server' => $testData['request']['server'],
            'raw' => $encryptedData, // Raw data goes here
            'headers' => [
                'Content-Type' => 'text/plain'
            ],
        ];

        $encryptedFullResponse = $this->makeRequestAndGetRawContent($request);
        $response = $bankTransferService->decryptIdfcBankCallbackData((string)$encryptedFullResponse->getContent());

        $this->assertEquals($testData['response']['content']['status'],$response['status']);
        $this->assertEquals($testData['response']['content']['statusDesc'],$response['statusDesc']);
        $this->assertEquals($testData['response']['content']['errorCode'], $response['errorCode']);
        $this->assertEquals($testData['response']['content']['errorDesc'], $response['errorDesc']);
        $this->assertEquals($testData['request']['content']['VANum'],$response['vANum']);
        $this->assertEquals($testData['request']['content']['bankRef'],$response['bankRef']);
    }

    // We won't be sending the IFSC details here
    public function testValidateBankTransferIdfcForNEFTMode()
    {
        $testData = $this->testData['testValidateBankTransferIdfc'];
        $accountNumber = $this->getIdfcVaBankAccount('3141');
        $testData['request']['content']['VANum'] = $accountNumber;

        $balance1 = $this->getDbEntity('balance',
            [
                'merchant_id' => '10000000000000',
            ], 'live');

        // This makes sure that the refunds for failed fund loadings on X happen via X
        (new Service)->setConfigKeys([ConfigKey::RX_FUND_LOADING_REFUNDS_VIA_X => true]);

        $this->fixtures->on('test')->edit('balance', $balance1->getId(), [
            'type'           => 'banking',
            'account_number' => $accountNumber,
        ]);

        $ba = $this->fixtures->on('test')->create('bank_account',
            [
                'merchant_id'    => '10000000000000',
                'entity_id'      => '100000000000va',
                'type'           => 'virtual_account',
                'ifsc_code'      => 'IDFB0020101',  //validate if its correct ifsc code
                'account_number' => $accountNumber,
            ]);

        $this->fixtures->on('test')->create('virtual_account',
            [
                'id'              => '100000000000va',
                'merchant_id'     => '10000000000000',
                'status'          => 'active',
                'bank_account_id' => $ba->getId(),
                'balance_id'      => $balance1->getId(),
            ]);

        $this->fixtures->on('test')->create('banking_account_tpv',
            [
                'balance_id' => $balance1->getId(),
                'status'     => 'approved',
                'payer_ifsc' => 'HDFC0003981',
                'payer_account_number' => '45612345678900987'
            ]);

        $bankTransferService = new BankTransferService();
        $iv = random_string_special_chars(16);
        $encryptedData = $bankTransferService->encryptIdfcBankCallbackData($testData['request']['content'], $iv);

        $this->ba->directAuth();

        $request = [
            'url' => '/ecollect/validate/idfc/test',
            'method' => 'POST',
            'server' => $testData['request']['server'],
            'raw' => $encryptedData, // Raw data goes here
            'headers' => [
                'Content-Type' => 'text/plain'
            ],
        ];

        $encryptedFullResponse = $this->makeRequestAndGetRawContent($request);
        $response = $bankTransferService->decryptIdfcBankCallbackData((string)$encryptedFullResponse->getContent());

        $this->assertEquals($testData['response']['content']['status'],$response['status']);
        $this->assertEquals($testData['response']['content']['statusDesc'],$response['statusDesc']);
        $this->assertEquals($testData['response']['content']['errorCode'], $response['errorCode']);
        $this->assertEquals($testData['response']['content']['errorDesc'], $response['errorDesc']);
        $this->assertEquals($testData['request']['content']['VANum'],$response['vANum']);
        $this->assertEquals($testData['request']['content']['bankRef'],$response['bankRef']);
    }

    // account type is bank_account
    public function testProcessBankTransferIdfcForImps()
    {
        $testData = $this->testData['testProcessBankTransferIdfc'];

        $accountNumber = $this->getIdfcVaBankAccount('3141');
        $testData['request']['content']['vaNumber'] = $accountNumber;
        $testData['request']['content']['productCode'] = "IIMPS";
        $current_time = new DateTime();
        $formatted_time = $current_time->format('d-M-y h.i.s.u A');
        $testData['request']['content']['creditGenerationTime'] = $formatted_time;
        $testData['request']['content']['ifscCode'] = "";

        $balance1 = $this->getDbEntity('balance',
            [
                'merchant_id' => '10000000000000',
            ], 'live');

        // This makes sure that the refunds for failed fund loadings on X happen via X
        (new Service)->setConfigKeys([ConfigKey::RX_FUND_LOADING_REFUNDS_VIA_X => true]);

        $this->fixtures->on('test')->edit('balance', $balance1->getId(), [
            'type'           => 'banking',
            'account_number' => $accountNumber,
        ]);

        $ba = $this->fixtures->on('test')->create('bank_account',
            [
                'merchant_id'    => '10000000000000',
                'entity_id'      => '100000000000va',
                'type'           => 'virtual_account',
                'ifsc_code'      => 'IDFB0020101',  //validate if its correct ifsc code
                'account_number' => $accountNumber,
            ]);

        $this->fixtures->on('test')->create('virtual_account',
            [
                'id'              => '100000000000va',
                'merchant_id'     => '10000000000000',
                'status'          => 'active',
                'bank_account_id' => $ba->getId(),
                'balance_id'      => $balance1->getId(),
            ]);
        $this->fixtures->on('test')->create('banking_account_tpv',
            [
                'balance_id' => $balance1->getId(),
                'status'     => 'approved',
                'payer_ifsc' => 'HDFC0003981',
                'payer_account_number' => '45612345678900987'
            ]);

        $bankTransferService = new BankTransferService();
        $iv = random_string_special_chars(16);
        $encryptedData = $bankTransferService->encryptIdfcBankCallbackData($testData['request']['content'], $iv);

        $this->ba->directAuth();

        $request = [
            'url' => '/ecollect/notify/idfc/test',
            'method' => 'POST',
            'server' => $testData['request']['server'],
            'raw' => $encryptedData, // Raw data goes here
            'headers' => [
                'Content-Type' => 'text/plain'
            ],
        ];

        $encryptedFullResponse = $this->makeRequestAndGetRawContent($request);
        $response = $bankTransferService->decryptIdfcBankCallbackData((string)$encryptedFullResponse->getContent());

        $this->assertEquals($testData['response']['content']['statusDesc'],$response['statusDesc']);
        $this->assertEquals($testData['response']['content']['status'], $response['status']);
        $this->assertEquals($testData['response']['content']['errorDesc'], $response['errorDesc']);
        $this->assertEquals($testData['response']['content']['errorCode'], $response['errorCode']);
        $this->assertEquals($accountNumber, $response['corRefNo']);

        $bankTransferRequest = $this->getLastEntity('bank_transfer_request', true);

        $this->assertEquals(true, $bankTransferRequest['is_created']);
        $this->assertEquals('imps',  $bankTransferRequest['mode']);
        $this->assertEquals('',  $bankTransferRequest['payer_ifsc']);
        $this->assertEquals($accountNumber, $bankTransferRequest['payee_account']);

        $bankTransfer =  $this->getLastEntity('bank_transfer', true);

        $this->assertEquals($bankTransfer['utr'], $testData['request']['content']['utrNo']);
        $this->assertEquals($bankTransfer['narration'], $testData['request']['content']['utrNo']);
        $this->assertEquals(50000, $bankTransfer['amount']);
        $this->assertEquals('imps',  $bankTransferRequest['mode']);
        $this->assertEquals('',  $bankTransferRequest['payer_ifsc']);

        $payerBankAccount = $this->getEntityById('bank_account', $bankTransfer['payer_bank_account']['id'], true);
        $this->assertEquals($testData['request']['content']['remitterAccountNumber'], $payerBankAccount['account_number']);
        $this->assertEquals("", $payerBankAccount['ifsc']);
    }

    public function testProcessBankTransferIdfcForImpsNonPayrollSeries()
    {
        $testData = $this->testData['testProcessBankTransferIdfc'];

        $accountNumber = $this->getIdfcVaBankAccount('5678');
        $testData['request']['content']['vaNumber'] = $accountNumber;
        $testData['request']['content']['productCode'] = "IIMPS";
        $current_time = new DateTime();
        $formatted_time = $current_time->format('d-M-y h.i.s.u A');
        $testData['request']['content']['creditGenerationTime'] = $formatted_time;
        $testData['request']['content']['ifscCode'] = "";

        $balance1 = $this->getDbEntity('balance',
            [
                'merchant_id' => '10000000000000',
            ], 'live');

        // This makes sure that the refunds for failed fund loadings on X happen via X
        (new Service)->setConfigKeys([ConfigKey::RX_FUND_LOADING_REFUNDS_VIA_X => true]);

        $this->fixtures->on('test')->edit('balance', $balance1->getId(), [
            'type'           => 'banking',
            'account_number' => $accountNumber,
        ]);

        $ba = $this->fixtures->on('test')->create('bank_account',
            [
                'merchant_id'    => '10000000000000',
                'entity_id'      => '100000000000va',
                'type'           => 'virtual_account',
                'ifsc_code'      => 'IDFB0020101',  //validate if its correct ifsc code
                'account_number' => $accountNumber,
            ]);

        $this->fixtures->on('test')->create('virtual_account',
            [
                'id'              => '100000000000va',
                'merchant_id'     => '10000000000000',
                'status'          => 'active',
                'bank_account_id' => $ba->getId(),
                'balance_id'      => $balance1->getId(),
            ]);
        $this->fixtures->on('test')->create('banking_account_tpv',
            [
                'balance_id' => $balance1->getId(),
                'status'     => 'approved',
                'payer_ifsc' => 'HDFC0003981',
                'payer_account_number' => '45612345678900987'
            ]);

        $bankTransferService = new BankTransferService();
        $iv = random_string_special_chars(16);
        $encryptedData = $bankTransferService->encryptIdfcBankCallbackData($testData['request']['content'], $iv);

        $this->ba->directAuth();

        $request = [
            'url' => '/ecollect/notify/idfc/test',
            'method' => 'POST',
            'server' => $testData['request']['server'],
            'raw' => $encryptedData, // Raw data goes here
            'headers' => [
                'Content-Type' => 'text/plain'
            ],
        ];

        $encryptedFullResponse = $this->makeRequestAndGetRawContent($request);
        $response = $bankTransferService->decryptIdfcBankCallbackData((string)$encryptedFullResponse->getContent());

        $this->assertEquals($testData['response']['content']['statusDesc'],$response['statusDesc']);
        $this->assertEquals($testData['response']['content']['status'], $response['status']);
        $this->assertEquals($testData['response']['content']['errorDesc'], $response['errorDesc']);
        $this->assertEquals($testData['response']['content']['errorCode'], $response['errorCode']);
        $this->assertEquals($accountNumber, $response['corRefNo']);

        $bankTransferRequest = $this->getLastEntity('bank_transfer_request', true);

        $this->assertEquals(true, $bankTransferRequest['is_created']);
        $this->assertEquals('imps',  $bankTransferRequest['mode']);
        $this->assertEquals('',  $bankTransferRequest['payer_ifsc']);
        $this->assertEquals($accountNumber, $bankTransferRequest['payee_account']);

        $bankTransfer =  $this->getLastEntity('bank_transfer', true);

        $this->assertEquals($bankTransfer['utr'], $testData['request']['content']['utrNo']);
        $this->assertEquals($bankTransfer['narration'], $testData['request']['content']['utrNo']);
        $this->assertEquals(50000, $bankTransfer['amount']);
        $this->assertEquals('imps',  $bankTransferRequest['mode']);
        $this->assertEquals('',  $bankTransferRequest['payer_ifsc']);

        $payerBankAccount = $this->getEntityById('bank_account', $bankTransfer['payer_bank_account']['id'], true);
        $this->assertEquals($testData['request']['content']['remitterAccountNumber'], $payerBankAccount['account_number']);
        $this->assertEquals("", $payerBankAccount['ifsc']);
    }

    public function testProcessBankTransferIdfcForNEFT()
    {
        $testData = $this->testData['testProcessBankTransferIdfc'];

        $accountNumber = $this->getIdfcVaBankAccount('3141');
        $testData['request']['content']['vaNumber'] = $accountNumber;
        $testData['request']['content']['productCode'] = "INEFT";
        $current_time = new DateTime();
        $formatted_time = $current_time->format('d-M-y h.i.s.u A');
        $testData['request']['content']['creditGenerationTime'] = $formatted_time;

        $balance1 = $this->getDbEntity('balance',
            [
                'merchant_id' => '10000000000000',
            ], 'live');

        // This makes sure that the refunds for failed fund loadings on X happen via X
        (new Service)->setConfigKeys([ConfigKey::RX_FUND_LOADING_REFUNDS_VIA_X => true]);

        $this->fixtures->on('test')->edit('balance', $balance1->getId(), [
            'type'           => 'banking',
            'account_number' => $accountNumber,
        ]);

        $ba = $this->fixtures->on('test')->create('bank_account',
            [
                'merchant_id'    => '10000000000000',
                'entity_id'      => '100000000000va',
                'type'           => 'virtual_account',
                'ifsc_code'      => 'IDFB0020101',  //validate if its correct ifsc code
                'account_number' => $accountNumber,
            ]);

        $this->fixtures->on('test')->create('virtual_account',
            [
                'id'              => '100000000000va',
                'merchant_id'     => '10000000000000',
                'status'          => 'active',
                'bank_account_id' => $ba->getId(),
                'balance_id'      => $balance1->getId(),
            ]);
        $this->fixtures->on('test')->create('banking_account_tpv',
            [
                'balance_id' => $balance1->getId(),
                'status'     => 'approved',
                'payer_ifsc' => 'HDFC0003981',
                'payer_account_number' => '45612345678900987'
            ]);

        $bankTransferService = new BankTransferService();
        $iv = random_string_special_chars(16);
        $encryptedData = $bankTransferService->encryptIdfcBankCallbackData($testData['request']['content'], $iv);

        $this->ba->directAuth();

        $request = [
            'url' => '/ecollect/notify/idfc/test',
            'method' => 'POST',
            'server' => $testData['request']['server'],
            'raw' => $encryptedData, // Raw data goes here
            'headers' => [
                'Content-Type' => 'text/plain'
            ],
        ];

        $encryptedFullResponse = $this->makeRequestAndGetRawContent($request);
        $response = $bankTransferService->decryptIdfcBankCallbackData((string)$encryptedFullResponse->getContent());

        $this->assertEquals($testData['response']['content']['statusDesc'],$response['statusDesc']);
        $this->assertEquals($testData['response']['content']['status'], $response['status']);
        $this->assertEquals($testData['response']['content']['errorDesc'], $response['errorDesc']);
        $this->assertEquals($testData['response']['content']['errorCode'], $response['errorCode']);
        $this->assertEquals($accountNumber, $response['corRefNo']);

        $bankTransferRequest = $this->getLastEntity('bank_transfer_request', true);

        $this->assertEquals(true, $bankTransferRequest['is_created']);
        $this->assertEquals('neft',  $bankTransferRequest['mode']);
        $this->assertEquals( $testData['request']['content']['ifscCode'] ,  $bankTransferRequest['payer_ifsc']);
        $this->assertEquals($accountNumber, $bankTransferRequest['payee_account']);

        $bankTransfer =  $this->getLastEntity('bank_transfer', true);

        $this->assertEquals($bankTransfer['utr'], $testData['request']['content']['utrNo']);
        $this->assertEquals($bankTransfer['narration'], $testData['request']['content']['utrNo']);
        $this->assertEquals(50000, $bankTransfer['amount']);
        $this->assertEquals('neft',  $bankTransferRequest['mode']);
        $this->assertEquals( $testData['request']['content']['ifscCode'] ,  $bankTransferRequest['payer_ifsc']);

        $payerBankAccount = $this->getEntityById('bank_account', $bankTransfer['payer_bank_account']['id'], true);
        $this->assertEquals($testData['request']['content']['remitterAccountNumber'], $payerBankAccount['account_number']);
        $this->assertEquals( $testData['request']['content']['ifscCode'] ,  $bankTransferRequest['payer_ifsc']);
    }

    public function testIdfcDuplicateForValidationApi()
    {
        $testData = $this->testData['testProcessBankTransferIdfc'];
        $accountNumber = $this->getIdfcVaBankAccount('3141');
        $testData['request']['content']['vaNumber'] = $accountNumber;

        $balance1 = $this->getDbEntity('balance',
            [
                'merchant_id' => '10000000000000',
            ], 'live');

        // This makes sure that the refunds for failed fund loadings on X happen via X
        (new Service)->setConfigKeys([ConfigKey::RX_FUND_LOADING_REFUNDS_VIA_X => true]);

        $this->fixtures->on('test')->edit('balance', $balance1->getId(), [
            'type'           => 'banking',
            'account_number' => $accountNumber,
        ]);

        $ba = $this->fixtures->on('test')->create('bank_account',
            [
                'merchant_id'    => '10000000000000',
                'entity_id'      => '100000000000va',
                'type'           => 'virtual_account',
                'ifsc_code'      => 'IDFB0020101',  //validate if its correct ifsc code
                'account_number' => $accountNumber,
            ]);

        $this->fixtures->on('test')->create('virtual_account',
            [
                'id'              => '100000000000va',
                'merchant_id'     => '10000000000000',
                'status'          => 'active',
                'bank_account_id' => $ba->getId(),
                'balance_id'      => $balance1->getId(),
            ]);

        $this->fixtures->on('test')->create('banking_account_tpv',
            [
                'balance_id' => $balance1->getId(),
                'status'     => 'approved',
                'payer_ifsc' => 'HDFC0003981',
                'payer_account_number' => '45612345678900987'
            ]);

        $current_time = new DateTime();
        $formatted_time = $current_time->format('d-M-y h.i.s.u A');
        $testData['request']['content']['creditGenerationTime'] = $formatted_time;

        $bankTransferService = new BankTransferService();
        $iv = random_string_special_chars(16);
        $encryptedData = $bankTransferService->encryptIdfcBankCallbackData($testData['request']['content'], $iv);

        $this->ba->directAuth();

        $request = [
            'url' => '/ecollect/notify/idfc/test',
            'method' => 'POST',
            'server' => $testData['request']['server'],
            'raw' => $encryptedData, // Raw data goes here
            'headers' => [
                'Content-Type' => 'text/plain'
            ],
        ];

        $encryptedFullResponse = $this->makeRequestAndGetRawContent($request);
        $response = $bankTransferService->decryptIdfcBankCallbackData((string)$encryptedFullResponse->getContent());

        $this->assertEquals($testData['response']['content']['statusDesc'],$response['statusDesc']);
        $this->assertEquals($testData['response']['content']['status'], $response['status']);
        $this->assertEquals($testData['response']['content']['errorDesc'], $response['errorDesc']);
        $this->assertEquals($testData['response']['content']['errorCode'], $response['errorCode']);
        $this->assertEquals($accountNumber, $response['corRefNo']);

        $bankTransferRequest = $this->getLastEntity('bank_transfer_request', true);

        $this->assertEquals(true, $bankTransferRequest['is_created']);
        $this->assertNotNull($bankTransferRequest['payee_account']);

        $bankTransfer =  $this->getLastEntity('bank_transfer', true);

        $this->assertEquals($bankTransfer['utr'], $testData['request']['content']['utrNo']);
        $this->assertEquals($bankTransfer['narration'], $testData['request']['content']['utrNo']);
        $this->assertEquals(50000, $bankTransfer['amount']);

        // no payment should be made : for that make account type as banking_account
        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals(null, $payment);

        $payerBankAccount = $this->getEntityById('bank_account', $bankTransfer['payer_bank_account']['id'], true);
        $this->assertEquals($testData['request']['content']['remitterAccountNumber'], $payerBankAccount['account_number']);

        $testData = $this->testData['testValidateBankTransferIdfc'];
        $testData['request']['content']['VANum'] = $accountNumber;

        $encryptedData = $bankTransferService->encryptIdfcBankCallbackData($testData['request']['content'], $iv);

        $this->ba->directAuth();
        $request = [
            'url' => '/ecollect/validate/idfc/test',
            'method' => 'POST',
            'server' => $testData['request']['server'],
            'raw' => $encryptedData, // Raw data goes here
            'headers' => [
                'Content-Type' => 'text/plain'
            ],
        ];

        $encryptedFullResponse = $this->makeRequestAndGetRawContent($request);
        $response = $bankTransferService->decryptIdfcBankCallbackData((string)$encryptedFullResponse->getContent());

        $this->assertEquals( "F" ,$response['status']);
        $this->assertEquals( "Failed",$response['statusDesc']);
        $this->assertEquals("02", $response['errorCode']);
        $this->assertEquals("INVALID_DATA", $response['errorDesc']);
    }

    public function testIdfcDuplicateForValidationApiForNonPayrollSeries()
    {
        $testData = $this->testData['testProcessBankTransferIdfc'];
        $accountNumber = $this->getIdfcVaBankAccount('5678');
        $testData['request']['content']['vaNumber'] = $accountNumber;

        $balance1 = $this->getDbEntity('balance',
            [
                'merchant_id' => '10000000000000',
            ], 'live');

        // This makes sure that the refunds for failed fund loadings on X happen via X
        (new Service)->setConfigKeys([ConfigKey::RX_FUND_LOADING_REFUNDS_VIA_X => true]);

        $this->fixtures->on('test')->edit('balance', $balance1->getId(), [
            'type'           => 'banking',
            'account_number' => $accountNumber,
        ]);

        $ba = $this->fixtures->on('test')->create('bank_account',
            [
                'merchant_id'    => '10000000000000',
                'entity_id'      => '100000000000va',
                'type'           => 'virtual_account',
                'ifsc_code'      => 'IDFB0020101',  //validate if its correct ifsc code
                'account_number' => $accountNumber,
            ]);

        $this->fixtures->on('test')->create('virtual_account',
            [
                'id'              => '100000000000va',
                'merchant_id'     => '10000000000000',
                'status'          => 'active',
                'bank_account_id' => $ba->getId(),
                'balance_id'      => $balance1->getId(),
            ]);

        $this->fixtures->on('test')->create('banking_account_tpv',
            [
                'balance_id' => $balance1->getId(),
                'status'     => 'approved',
                'payer_ifsc' => 'HDFC0003981',
                'payer_account_number' => '45612345678900987'
            ]);

        $current_time = new DateTime();
        $formatted_time = $current_time->format('d-M-y h.i.s.u A');
        $testData['request']['content']['creditGenerationTime'] = $formatted_time;

        $bankTransferService = new BankTransferService();
        $iv = random_string_special_chars(16);
        $encryptedData = $bankTransferService->encryptIdfcBankCallbackData($testData['request']['content'], $iv);

        $this->ba->directAuth();

        $request = [
            'url' => '/ecollect/notify/idfc/test',
            'method' => 'POST',
            'server' => $testData['request']['server'],
            'raw' => $encryptedData, // Raw data goes here
            'headers' => [
                'Content-Type' => 'text/plain'
            ],
        ];

        $encryptedFullResponse = $this->makeRequestAndGetRawContent($request);
        $response = $bankTransferService->decryptIdfcBankCallbackData((string)$encryptedFullResponse->getContent());

        $this->assertEquals($testData['response']['content']['statusDesc'],$response['statusDesc']);
        $this->assertEquals($testData['response']['content']['status'], $response['status']);
        $this->assertEquals($testData['response']['content']['errorDesc'], $response['errorDesc']);
        $this->assertEquals($testData['response']['content']['errorCode'], $response['errorCode']);
        $this->assertEquals($accountNumber, $response['corRefNo']);

        $bankTransferRequest = $this->getLastEntity('bank_transfer_request', true);

        $this->assertEquals(true, $bankTransferRequest['is_created']);
        $this->assertNotNull($bankTransferRequest['payee_account']);

        $bankTransfer =  $this->getLastEntity('bank_transfer', true);

        $this->assertEquals($bankTransfer['utr'], $testData['request']['content']['utrNo']);
        $this->assertEquals($bankTransfer['narration'], $testData['request']['content']['utrNo']);
        $this->assertEquals(50000, $bankTransfer['amount']);

        // no payment should be made : for that make account type as banking_account
        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals(null, $payment);

        $payerBankAccount = $this->getEntityById('bank_account', $bankTransfer['payer_bank_account']['id'], true);
        $this->assertEquals($testData['request']['content']['remitterAccountNumber'], $payerBankAccount['account_number']);

        $testData = $this->testData['testValidateBankTransferIdfc'];
        $testData['request']['content']['VANum'] = $accountNumber;

        $encryptedData = $bankTransferService->encryptIdfcBankCallbackData($testData['request']['content'], $iv);

        $this->ba->directAuth();
        $request = [
            'url' => '/ecollect/validate/idfc/test',
            'method' => 'POST',
            'server' => $testData['request']['server'],
            'raw' => $encryptedData, // Raw data goes here
            'headers' => [
                'Content-Type' => 'text/plain'
            ],
        ];

        $encryptedFullResponse = $this->makeRequestAndGetRawContent($request);
        $response = $bankTransferService->decryptIdfcBankCallbackData((string)$encryptedFullResponse->getContent());

        $this->assertEquals( "F" ,$response['status']);
        $this->assertEquals( "Failed",$response['statusDesc']);
        $this->assertEquals("02", $response['errorCode']);
        $this->assertEquals("INVALID_DATA", $response['errorDesc']);
    }

    public function testIdfcDuplicateForNotifyApi()
    {
        $testData = $this->testData['testProcessBankTransferIdfc'];
        $accountNumber = $this->getIdfcVaBankAccount('3141');
        $testData['request']['content']['vaNumber'] = $accountNumber;
        $current_time = new DateTime();
        $formatted_time = $current_time->format('d-M-y h.i.s.u A');
        $testData['request']['content']['creditGenerationTime'] = $formatted_time;

        $balance1 = $this->getDbEntity('balance',
            [
                'merchant_id' => '10000000000000',
            ], 'live');

        // This makes sure that the refunds for failed fund loadings on X happen via X
        (new Service)->setConfigKeys([ConfigKey::RX_FUND_LOADING_REFUNDS_VIA_X => true]);

        $this->fixtures->on('test')->edit('balance', $balance1->getId(), [
            'type'           => 'banking',
            'account_number' => $accountNumber,
        ]);

        $ba = $this->fixtures->on('test')->create('bank_account',
            [
                'merchant_id'    => '10000000000000',
                'entity_id'      => '100000000000va',
                'type'           => 'virtual_account',
                'ifsc_code'      => 'IDFB0020101',  //validate if its correct ifsc code
                'account_number' => $accountNumber,
            ]);

        $this->fixtures->on('test')->create('virtual_account',
            [
                'id'              => '100000000000va',
                'merchant_id'     => '10000000000000',
                'status'          => 'active',
                'bank_account_id' => $ba->getId(),
                'balance_id'      => $balance1->getId(),
            ]);

        $this->fixtures->on('test')->create('banking_account_tpv',
            [
                'balance_id' => $balance1->getId(),
                'status'     => 'approved',
                'payer_ifsc' => 'HDFC0003981',
                'payer_account_number' => '45612345678900987'
            ]);

        $bankTransferService = new BankTransferService();
        $iv = random_string_special_chars(16);
        $encryptedData = $bankTransferService->encryptIdfcBankCallbackData($testData['request']['content'], $iv);

        $this->ba->directAuth();

        $request = [
            'url' => '/ecollect/notify/idfc/test',
            'method' => 'POST',
            'server' => $testData['request']['server'],
            'raw' => $encryptedData, // Raw data goes here
            'headers' => [
                'Content-Type' => 'text/plain'
            ],
        ];

        $encryptedFullResponse = $this->makeRequestAndGetRawContent($request);
        $response = $bankTransferService->decryptIdfcBankCallbackData((string)$encryptedFullResponse->getContent());

        $this->assertEquals($testData['response']['content']['statusDesc'],$response['statusDesc']);
        $this->assertEquals($testData['response']['content']['status'], $response['status']);
        $this->assertEquals($testData['response']['content']['errorDesc'], $response['errorDesc']);
        $this->assertEquals($testData['response']['content']['errorCode'], $response['errorCode']);
        $this->assertEquals($accountNumber, $response['corRefNo']);

        $bankTransferRequest = $this->getLastEntity('bank_transfer_request', true);

        $this->assertEquals(true, $bankTransferRequest['is_created']);
        $this->assertNotNull($bankTransferRequest['payee_account']);

        $bankTransfer =  $this->getLastEntity('bank_transfer', true);

        $this->assertEquals($bankTransfer['utr'], $testData['request']['content']['utrNo']);
        $this->assertEquals($bankTransfer['narration'], $testData['request']['content']['utrNo']);
        $this->assertEquals(50000, $bankTransfer['amount']);


       // no payment should be made : for that make account type as banking_account
        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals(null, $payment);

        $payerBankAccount = $this->getEntityById('bank_account', $bankTransfer['payer_bank_account']['id'], true);
        $this->assertEquals($testData['request']['content']['remitterAccountNumber'], $payerBankAccount['account_number']);

        //making second call
        $encryptedFullResponse = $this->makeRequestAndGetRawContent($request);
        $response = $bankTransferService->decryptIdfcBankCallbackData((string)$encryptedFullResponse->getContent());

        $this->assertEquals( "F" ,$response['status']);
        $this->assertEquals( "Failure",$response['statusDesc']);
        $this->assertEquals("1001", $response['errorCode']);
        $this->assertEquals("INVALID_DATA", $response['errorDesc']);
    }

    public function testIdfcDuplicateForNotifyApiForNonPayrollSeries()
    {
        $testData = $this->testData['testProcessBankTransferIdfc'];
        $accountNumber = $this->getIdfcVaBankAccount('5678');
        $testData['request']['content']['vaNumber'] = $accountNumber;
        $current_time = new DateTime();
        $formatted_time = $current_time->format('d-M-y h.i.s.u A');
        $testData['request']['content']['creditGenerationTime'] = $formatted_time;

        $balance1 = $this->getDbEntity('balance',
            [
                'merchant_id' => '10000000000000',
            ], 'live');

        // This makes sure that the refunds for failed fund loadings on X happen via X
        (new Service)->setConfigKeys([ConfigKey::RX_FUND_LOADING_REFUNDS_VIA_X => true]);

        $this->fixtures->on('test')->edit('balance', $balance1->getId(), [
            'type'           => 'banking',
            'account_number' => $accountNumber,
        ]);

        $ba = $this->fixtures->on('test')->create('bank_account',
            [
                'merchant_id'    => '10000000000000',
                'entity_id'      => '100000000000va',
                'type'           => 'virtual_account',
                'ifsc_code'      => 'IDFB0020101',  //validate if its correct ifsc code
                'account_number' => $accountNumber,
            ]);

        $this->fixtures->on('test')->create('virtual_account',
            [
                'id'              => '100000000000va',
                'merchant_id'     => '10000000000000',
                'status'          => 'active',
                'bank_account_id' => $ba->getId(),
                'balance_id'      => $balance1->getId(),
            ]);

        $this->fixtures->on('test')->create('banking_account_tpv',
            [
                'balance_id' => $balance1->getId(),
                'status'     => 'approved',
                'payer_ifsc' => 'HDFC0003981',
                'payer_account_number' => '45612345678900987'
            ]);

        $bankTransferService = new BankTransferService();
        $iv = random_string_special_chars(16);
        $encryptedData = $bankTransferService->encryptIdfcBankCallbackData($testData['request']['content'], $iv);

        $this->ba->directAuth();

        $request = [
            'url' => '/ecollect/notify/idfc/test',
            'method' => 'POST',
            'server' => $testData['request']['server'],
            'raw' => $encryptedData, // Raw data goes here
            'headers' => [
                'Content-Type' => 'text/plain'
            ],
        ];

        $encryptedFullResponse = $this->makeRequestAndGetRawContent($request);
        $response = $bankTransferService->decryptIdfcBankCallbackData((string)$encryptedFullResponse->getContent());

        $this->assertEquals($testData['response']['content']['statusDesc'],$response['statusDesc']);
        $this->assertEquals($testData['response']['content']['status'], $response['status']);
        $this->assertEquals($testData['response']['content']['errorDesc'], $response['errorDesc']);
        $this->assertEquals($testData['response']['content']['errorCode'], $response['errorCode']);
        $this->assertEquals($accountNumber, $response['corRefNo']);

        $bankTransferRequest = $this->getLastEntity('bank_transfer_request', true);

        $this->assertEquals(true, $bankTransferRequest['is_created']);
        $this->assertNotNull($bankTransferRequest['payee_account']);

        $bankTransfer =  $this->getLastEntity('bank_transfer', true);

        $this->assertEquals($bankTransfer['utr'], $testData['request']['content']['utrNo']);
        $this->assertEquals($bankTransfer['narration'], $testData['request']['content']['utrNo']);
        $this->assertEquals(50000, $bankTransfer['amount']);


        // no payment should be made : for that make account type as banking_account
        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals(null, $payment);

        $payerBankAccount = $this->getEntityById('bank_account', $bankTransfer['payer_bank_account']['id'], true);
        $this->assertEquals($testData['request']['content']['remitterAccountNumber'], $payerBankAccount['account_number']);

        //making second call
        $encryptedFullResponse = $this->makeRequestAndGetRawContent($request);
        $response = $bankTransferService->decryptIdfcBankCallbackData((string)$encryptedFullResponse->getContent());

        $this->assertEquals( "F" ,$response['status']);
        $this->assertEquals( "Failure",$response['statusDesc']);
        $this->assertEquals("1001", $response['errorCode']);
        $this->assertEquals("INVALID_DATA", $response['errorDesc']);
    }

    public function testHandlePayrollTpvValidationFlowValidResponse()
    {
        $bankTransfer = $this->createMock(Entity::class);
        $merchantID = 'merchant123';
        $balanceID = 'balance123';
        $isValidationFlow = true;

        $this->xpayrollMock->expects($this->once())
            ->method('sendPayrollTpvRequestAndGetResponse')
            ->with($bankTransfer, $this->anything())
            ->willReturn(['is_valid' => true]);

        $this->traceMock->expects($this->once())
            ->method('info')
            ->with(TraceCode::TPV_ACCOUNT_FUND_LOADING_FOR_BANKING_ACCOUNT_TRIGGERED);

        $result = $this->processor->handlePayrollTpv($bankTransfer, $merchantID, $balanceID, $isValidationFlow);

        $this->assertTrue($result);
    }

    public function testHandlePayrollTpvValidationFlowInvalidResponse()
    {
        $bankTransfer = $this->createMock(Entity::class);
        $merchantID = 'merchant123';
        $balanceID = 'balance123';
        $isValidationFlow = true;

        $this->xpayrollMock->expects($this->once())
            ->method('sendPayrollTpvRequestAndGetResponse')
            ->with($bankTransfer, $this->anything())
            ->willReturn(['is_valid' => false]);

        $this->traceMock->expects($this->once())
            ->method('info')
            ->with(TraceCode::NON_TPV_ACCOUNT_FUND_LOADING_FOR_BANKING_ACCOUNT_TRIGGERED);

        $result = $this->processor->handlePayrollTpv($bankTransfer, $merchantID, $balanceID, $isValidationFlow);

        $this->assertFalse($result);
    }

    public function testHandlePayrollTpvNonValidationFlow()
    {
        $bankTransfer = $this->createMock(Entity::class);
        $merchantID = 'merchant123';
        $balanceID = 'balance123';
        $isValidationFlow = false;

        $this->traceMock->expects($this->once())
            ->method('info')
            ->with(TraceCode::TPV_SKIP_FOR_PAYROLL_IN_NOTIFICATION_API);

        $result = $this->processor->handlePayrollTpv($bankTransfer, $merchantID, $balanceID, $isValidationFlow);

        $this->assertTrue($result);
    }

    public function testIdfcValidationCallbackForFundLoading_WithVANumberValidationFailure()
    {
        $testData = $this->testData['testValidateBankTransferIdfc'];
        $accountNumber = $this->getIdfcVaBankAccount('2222'); //added random prefix for failure scenario
        $testData['request']['content']['VANum'] = $accountNumber;
        $testData['request']['content']['remitterBankifsc'] = "";
        $balance1 = $this->getDbEntity('balance',
            [
                'merchant_id' => '10000000000000',
            ], 'live');

        // This makes sure that the refunds for failed fund loadings on X happen via X
        (new Service)->setConfigKeys([ConfigKey::RX_FUND_LOADING_REFUNDS_VIA_X => true]);

        $this->fixtures->on('test')->edit('balance', $balance1->getId(), [
            'type'           => 'banking',
            'account_number' => $accountNumber,
        ]);

        $ba = $this->fixtures->on('test')->create('bank_account',
            [
                'merchant_id'    => '10000000000000',
                'entity_id'      => '100000000000va',
                'type'           => 'virtual_account',
                'ifsc_code'      => 'IDFB0020101',  //validate if its correct ifsc code
                'account_number' => $accountNumber,
            ]);

        $this->fixtures->on('test')->create('virtual_account',
            [
                'id'              => '100000000000va',
                'merchant_id'     => '10000000000000',
                'status'          => 'active',
                'bank_account_id' => $ba->getId(),
                'balance_id'      => $balance1->getId(),
            ]);

        $this->fixtures->on('test')->create('banking_account_tpv',
            [
                'balance_id' => $balance1->getId(),
                'status'     => 'approved',
                'payer_ifsc' => 'HDFC0003981',
                'payer_account_number' => '45612345678900987'
            ]);

        $bankTransferService = new BankTransferService();
        $iv = random_string_special_chars(16);
        $encryptedData = $bankTransferService->encryptIdfcBankCallbackData($testData['request']['content'], $iv);

        $this->ba->directAuth();

        $request = [
            'url' => '/ecollect/validate/idfc/test',
            'method' => 'POST',
            'server' => $testData['request']['server'],
            'raw' => $encryptedData, // Raw data goes here
            'headers' => [
                'Content-Type' => 'text/plain'
            ],
        ];

        $encryptedFullResponse = $this->makeRequestAndGetRawContent($request);
        $response = $bankTransferService->decryptIdfcBankCallbackData((string)$encryptedFullResponse->getContent());

        $this->assertEquals('F' ,$response['status']);
        $this->assertEquals('Failed' ,$response['statusDesc']);
        $this->assertEquals('02' , $response['errorCode']);
        $this->assertEquals('INVALID_DATA' , $response['errorDesc']);
        $this->assertEquals($testData['request']['content']['VANum'] ,$response['vaNum']);
        $this->assertEquals('HDFB201907090000000112' ,$response['bankRef']);
    }

    public function testIdfcNotificationCallbackForFundLoading_WithVANumberValidationFailure()
    {
        $testData = $this->testData['testProcessBankTransferIdfc'];

        $accountNumber = $this->getIdfcVaBankAccount('2222'); //added random prefix for failure scenario
        $testData['request']['content']['vaNumber'] = $accountNumber;
        $testData['request']['content']['productCode'] = "IIMPS";
        $current_time = new DateTime();
        $formatted_time = $current_time->format('d-M-y h.i.s.u A');
        $testData['request']['content']['creditGenerationTime'] = $formatted_time;
        $testData['request']['content']['ifscCode'] = "";

        $balance1 = $this->getDbEntity('balance',
            [
                'merchant_id' => '10000000000000',
            ], 'live');

        // This makes sure that the refunds for failed fund loadings on X happen via X
        (new Service)->setConfigKeys([ConfigKey::RX_FUND_LOADING_REFUNDS_VIA_X => true]);

        $this->fixtures->on('test')->edit('balance', $balance1->getId(), [
            'type'           => 'banking',
            'account_number' => $accountNumber,
        ]);

        $ba = $this->fixtures->on('test')->create('bank_account',
            [
                'merchant_id'    => '10000000000000',
                'entity_id'      => '100000000000va',
                'type'           => 'virtual_account',
                'ifsc_code'      => 'IDFB0020101',  //validate if its correct ifsc code
                'account_number' => $accountNumber,
            ]);

        $this->fixtures->on('test')->create('virtual_account',
            [
                'id'              => '100000000000va',
                'merchant_id'     => '10000000000000',
                'status'          => 'active',
                'bank_account_id' => $ba->getId(),
                'balance_id'      => $balance1->getId(),
            ]);
        $this->fixtures->on('test')->create('banking_account_tpv',
            [
                'balance_id' => $balance1->getId(),
                'status'     => 'approved',
                'payer_ifsc' => 'HDFC0003981',
                'payer_account_number' => '45612345678900987'
            ]);

        $bankTransferService = new BankTransferService();
        $iv = random_string_special_chars(16);
        $encryptedData = $bankTransferService->encryptIdfcBankCallbackData($testData['request']['content'], $iv);

        $this->ba->directAuth();

        $request = [
            'url' => '/ecollect/notify/idfc/test',
            'method' => 'POST',
            'server' => $testData['request']['server'],
            'raw' => $encryptedData, // Raw data goes here
            'headers' => [
                'Content-Type' => 'text/plain'
            ],
        ];

        $encryptedFullResponse = $this->makeRequestAndGetRawContent($request);
        $response = $bankTransferService->decryptIdfcBankCallbackData((string)$encryptedFullResponse->getContent());

        $this->assertEquals('F' ,$response['status']);
        $this->assertEquals('Failure' ,$response['statusDesc']);
        $this->assertEquals('1001' , $response['errorCode']);
        $this->assertEquals('INVALID_DATA' , $response['errorDesc']);
        $this->assertEquals($testData['request']['content']['vaNumber'] ,$response['corRefNo']);
        $this->assertEquals('HDFB201907090000000112' ,$response['ReqrefNo']);
    }

    public function createCollectXVirtualAccount(
        $mode = 'test',
        $merchantID = '10000000000000',
        $receivers = ['bank_account'],
        $gateway = 'idfc')
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

    public function testIdfcValidationCallbackForCollectx() {
        $testData = $this->testData['testValidateBankTransferIdfc'];

        $response = $this->createCollectXVirtualAccount(receivers: ['bank_account']);

        $beneAccountNo = $response['receivers'][0]['account_number'];

        $testData['request']['content']['VANum'] = $beneAccountNo;

        $this->testData[__FUNCTION__] = $testData;

        $merchantID = '10000000000000';

        $this->enableSplitzExperiment(
            experimentName: RazorxTreatment::COLLECTX_IDFC_PAYMENT_TRANSFER_RAMP_UP,
            id: $merchantID,
            requestData: ['id' => $merchantID]);

        $bankTransferService = new BankTransferService();
        $iv = random_string_special_chars(16);
        $encryptedData = $bankTransferService->encryptIdfcBankCallbackData($testData['request']['content'], $iv);

        $request = [
            'url' => '/ecollect/validate/idfc/test',
            'method' => 'post',
            'raw' => $encryptedData,
            'server' => [
                'HTTP_XorgToken' => 'RANDOM_IDFC_SECRET',
            ]
        ];

        $this->ba->idfcAuth();

        $encryptedFullResponse = $this->makeRequestAndGetRawContent($request);
        $response = $bankTransferService->decryptIdfcBankCallbackData((string)$encryptedFullResponse->getContent());

        $this->assertEquals('000', $response['status']);
        $this->assertEquals('Success', $response['statusDesc']);
        $this->assertEquals($testData['response']['content']['errorCode'], $response['errorCode']);
        $this->assertEquals($testData['response']['content']['errorDesc'], $response['errorDesc']);
        $this->assertEquals($testData['request']['content']['VANum'],$response['vANum']);
        $this->assertEquals($testData['request']['content']['bankRef'],$response['bankRef']);

        $bankTransferRequest = $this->getLastEntity('bank_transfer_request', true);

        $this->assertNotNull($bankTransferRequest);
        $this->assertFalse($bankTransferRequest['is_created']);
    }

    public function testIdfcNotificationCallbackForCollectxForIMPSMode() {
        $testData = $this->testData['testProcessBankTransferIdfc'];

        $response = $this->createCollectXVirtualAccount(receivers: ['bank_account']);

        $beneAccountNo = $response['receivers'][0]['account_number'];

        $testData['request']['content']['vaNumber'] = $beneAccountNo;

        $current_time = new DateTime();
        $formatted_time = $current_time->format('d-M-y h.i.s.u A');
        $testData['request']['content']['creditGenerationTime'] = $formatted_time;

        $testData['request']['content']['ifscCode'] = "";

        $this->testData[__FUNCTION__] = $testData;

        $merchantID = '10000000000000';

        $this->enableSplitzExperiment(
            experimentName: RazorxTreatment::COLLECTX_IDFC_PAYMENT_TRANSFER_RAMP_UP,
            id: $merchantID,
            requestData: ['id' => $merchantID]);

        $bankTransferService = new BankTransferService();
        $iv = random_string_special_chars(16);
        $encryptedData = $bankTransferService->encryptIdfcBankCallbackData($testData['request']['content'], $iv);

        $request = [
            'url' => '/ecollect/notify/idfc/test',
            'method' => 'post',
            'raw' => $encryptedData,
            'server' => [
                'HTTP_XorgToken' => 'RANDOM_IDFC_SECRET',
            ]
        ];

        $this->ba->idfcAuth();

        $encryptedFullResponse = $this->makeRequestAndGetRawContent($request);
        $response = $bankTransferService->decryptIdfcBankCallbackData((string)$encryptedFullResponse->getContent());

        $this->assertEquals('S', $response['status']);
        $this->assertEquals('Success', $response['statusDesc']);
        $this->assertEquals($testData['response']['content']['errorCode'], $response['errorCode']);
        $this->assertEquals($testData['response']['content']['errorDesc'], $response['errorDesc']);
        $this->assertEquals($testData['request']['content']['vaNumber'], $response['corRefNo']);
        $this->assertEquals('HDFB201907090000000112', $response['ReqrefNo']);

        $bankTransferRequest = $this->getLastEntity('bank_transfer_request', true);

        $this->assertNotNull($bankTransferRequest);
        $this->assertTrue($bankTransferRequest['is_created']);
        $this->assertEquals('IMPS',  $bankTransferRequest['mode']);
        $this->assertEquals('',  $bankTransferRequest['payer_ifsc']);
        $this->assertEquals($beneAccountNo, $bankTransferRequest['payee_account']);

        $bankTransfer =  $this->getLastEntity('bank_transfer', true);

        $this->assertEquals($bankTransfer['utr'], $testData['request']['content']['utrNo']);
        $this->assertEquals($bankTransfer['narration'], $testData['request']['content']['utrNo']);
        $this->assertEquals(50000, $bankTransfer['amount']);

        $payerBankAccount = $this->getEntityById('bank_account', $bankTransfer['payer_bank_account']['id'], true);
        $this->assertEquals($testData['request']['content']['remitterAccountNumber'], $payerBankAccount['account_number']);
        $this->assertEquals("", $payerBankAccount['ifsc']);

        $payment =  $this->getLastEntity('payment', true);

        $this->assertEquals(50000, $payment['amount']);
        $this->assertEquals('bt_idfc', $payment['gateway']);
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

    public function testIdfcNotificationCallbackForCollectxForNEFTMode() {
        $testData = $this->testData['testProcessBankTransferIdfc'];

        $response = $this->createCollectXVirtualAccount(receivers: ['bank_account']);

        $beneAccountNo = $response['receivers'][0]['account_number'];

        $testData['request']['content']['vaNumber'] = $beneAccountNo;
        $testData['request']['content']['productCode'] = "INEFT";

        $current_time = new DateTime();
        $formatted_time = $current_time->format('d-M-y h.i.s.u A');
        $testData['request']['content']['creditGenerationTime'] = $formatted_time;

        $this->testData[__FUNCTION__] = $testData;

        $merchantID = '10000000000000';

        $this->enableSplitzExperiment(
            experimentName: RazorxTreatment::COLLECTX_IDFC_PAYMENT_TRANSFER_RAMP_UP,
            id: $merchantID,
            requestData: ['id' => $merchantID]);

        $bankTransferService = new BankTransferService();
        $iv = random_string_special_chars(16);
        $encryptedData = $bankTransferService->encryptIdfcBankCallbackData($testData['request']['content'], $iv);

        $request = [
            'url' => '/ecollect/notify/idfc/test',
            'method' => 'post',
            'raw' => $encryptedData,
            'server' => [
                'HTTP_XorgToken' => 'RANDOM_IDFC_SECRET',
            ]
        ];

        $this->ba->idfcAuth();

        $encryptedFullResponse = $this->makeRequestAndGetRawContent($request);
        $response = $bankTransferService->decryptIdfcBankCallbackData((string)$encryptedFullResponse->getContent());

        $this->assertEquals('S', $response['status']);
        $this->assertEquals('Success', $response['statusDesc']);
        $this->assertEquals($testData['response']['content']['errorCode'], $response['errorCode']);
        $this->assertEquals($testData['response']['content']['errorDesc'], $response['errorDesc']);
        $this->assertEquals($testData['request']['content']['vaNumber'], $response['corRefNo']);
        $this->assertEquals('HDFB201907090000000112', $response['ReqrefNo']);

        $bankTransferRequest = $this->getLastEntity('bank_transfer_request', true);

        $this->assertNotNull($bankTransferRequest);
        $this->assertTrue($bankTransferRequest['is_created']);
        $this->assertEquals('NEFT',  $bankTransferRequest['mode']);
        $this->assertEquals('HDFC0003981',  $bankTransferRequest['payer_ifsc']);
        $this->assertEquals($beneAccountNo, $bankTransferRequest['payee_account']);

        $bankTransfer =  $this->getLastEntity('bank_transfer', true);

        $this->assertEquals($bankTransfer['utr'], $testData['request']['content']['utrNo']);
        $this->assertEquals($bankTransfer['narration'], $testData['request']['content']['utrNo']);
        $this->assertEquals(50000, $bankTransfer['amount']);

        $payerBankAccount = $this->getEntityById('bank_account', $bankTransfer['payer_bank_account']['id'], true);
        $this->assertEquals($testData['request']['content']['remitterAccountNumber'], $payerBankAccount['account_number']);
        $this->assertEquals('HDFC0003981' , $payerBankAccount['ifsc']);

        $payment =  $this->getLastEntity('payment', true);

        $this->assertEquals(50000, $payment['amount']);
        $this->assertEquals('bt_idfc', $payment['gateway']);
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

    public function testProcessBankTransferIdfcForImpsWithClosedVirtualAccount()
    {
        $testData = $this->testData['testProcessBankTransferIdfc'];

        $accountNumber = $this->getIdfcVaBankAccount('5678'); // Using non-payroll series
        $testData['request']['content']['vaNumber'] = $accountNumber;
        $testData['request']['content']['productCode'] = "IIMPS";
        $current_time = new DateTime();
        $formatted_time = $current_time->format('d-M-y h.i.s.u A');
        $testData['request']['content']['creditGenerationTime'] = $formatted_time;
        $testData['request']['content']['ifscCode'] = "";

        $balance1 = $this->getDbEntity('balance',
            [
                'merchant_id' => '10000000000000',
            ], 'live');

        // This makes sure that the refunds for failed fund loadings on X happen via X
        (new Service)->setConfigKeys([ConfigKey::RX_FUND_LOADING_REFUNDS_VIA_X => true]);

        $this->fixtures->on('test')->edit('balance', $balance1->getId(), [
            'type'           => 'banking',
            'account_number' => $accountNumber,
        ]);

        $ba = $this->fixtures->on('test')->create('bank_account',
            [
                'merchant_id'    => '10000000000000',
                'entity_id'      => 'HMwb1lgZD9N5Gm',
                'type'           => 'virtual_account',
                'ifsc_code'      => 'IDFB0020101',  //validate if its correct ifsc code
                'account_number' => $accountNumber,
            ]);

        // Create the virtual account but set it as closed
        $virtualAccount = $this->fixtures->on('test')->create('virtual_account',
            [
                'id'              => 'HMwb1lgZD9N5Gm',
                'merchant_id'     => '10000000000000',
                'status'          => 'closed', // Setting the status as closed
                'bank_account_id' => $ba->getId(),
                'balance_id'      => $balance1->getId(),
            ]);

        $this->fixtures->on('test')->create('banking_account_tpv',
            [
                'balance_id' => $balance1->getId(),
                'status'     => 'approved',
                'payer_ifsc' => 'HDFC0003981',
                'payer_account_number' => '45612345678900987'
            ]);

        // Mock the processor to ensure getTransferTypeBasedOnPayeeAccount returns true
        $processorMock = $this->getMockBuilder(Processor::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getTransferTypeBasedOnPayeeAccount'])
            ->getMockForAbstractClass();

        $processorMock->method('getTransferTypeBasedOnPayeeAccount')
            ->willReturn(true);

        $this->app->instance('processor', $processorMock);

        $bankTransferService = new BankTransferService();
        $iv = random_string_special_chars(16);
        $encryptedData = $bankTransferService->encryptIdfcBankCallbackData($testData['request']['content'], $iv);

        $this->ba->directAuth();

        $request = [
            'url' => '/ecollect/notify/idfc/test',
            'method' => 'POST',
            'server' => $testData['request']['server'],
            'raw' => $encryptedData, // Raw data goes here
            'headers' => [
                'Content-Type' => 'text/plain'
            ],
        ];

        $encryptedFullResponse = $this->makeRequestAndGetRawContent($request);
        $response = $bankTransferService->decryptIdfcBankCallbackData((string)$encryptedFullResponse->getContent());

        $this->assertEquals($testData['response']['content']['statusDesc'],$response['statusDesc']);
        $this->assertEquals($testData['response']['content']['status'], $response['status']);
        $this->assertEquals($testData['response']['content']['errorDesc'], $response['errorDesc']);
        $this->assertEquals($testData['response']['content']['errorCode'], $response['errorCode']);
        $this->assertEquals($accountNumber, $response['corRefNo']);

        $bankTransferRequest = $this->getLastEntity('bank_transfer_request', true);

        $this->assertEquals(true, $bankTransferRequest['is_created']);
        $this->assertEquals('imps',  $bankTransferRequest['mode']);
        $this->assertEquals('',  $bankTransferRequest['payer_ifsc']);
        $this->assertEquals($accountNumber, $bankTransferRequest['payee_account']);

        $bankTransfer = $this->getLastEntity('bank_transfer', true);

        $this->assertEquals($bankTransfer['utr'], $testData['request']['content']['utrNo']);
        $this->assertEquals($bankTransfer['narration'], $testData['request']['content']['utrNo']);
        $this->assertEquals(50000, $bankTransfer['amount']);

        $payerBankAccount = $this->getEntityById('bank_account', $bankTransfer['payer_bank_account']['id'], true);
        $this->assertEquals($testData['request']['content']['remitterAccountNumber'], $payerBankAccount['account_number']);
        $this->assertEquals("", $payerBankAccount['ifsc']);

        // Check if a payment was created for the refund
        $payment = $this->getLastEntity('payment', true);
        $this->assertNull($payment);

        // Check if a payout was created for the refund
        $payout = $this->getLastEntity('payout', true);
        $this->assertNull($payout);

        // Verify the reason for the refund
        $this->assertStringContainsString('VIRTUAL_ACCOUNT_CLOSED', $bankTransfer['unexpected_reason']);
    }
}


