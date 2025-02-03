<?php

namespace Functional\BankTransfer;

use DateTime;
use DB;
use Mail;
use Cache;
use RZP\Http\Controllers\BankTransferController;
use RZP\Models\Admin\Service;
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

}


