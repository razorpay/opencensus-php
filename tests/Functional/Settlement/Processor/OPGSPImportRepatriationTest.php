<?php

namespace RZP\Tests\Functional\Settlement\Processor;

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use RZP\Models\Settlement\Channel;
use RZP\Tests\Functional\FundTransfer\AttemptTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\OAuth\OAuthTestCase;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Tests\Functional\Helpers\Workflow\WorkflowTrait;

class OPGSPImportRepatriationTest extends OAuthTestCase
{
    use FileHandlerTrait;
    use AttemptTrait;
    use DbEntityFetchTrait;
    use PartnerTrait;
    use WorkflowTrait;

    const DEFAULT_SUBMERCHANT_ID    = '10000000000009';

    const EXCHANGE_RATE = 80;

    const Date = "Date";
    const OPGSPTranRefNo = "OPGSPTranRefNo";
    const INRAmount = "INRAmount";
    const CURRENCY = "CURRENCY";
    const IBANNumber = "IBANNumber";
    const CNAPSCode = "CNAPSCode";
    const POPSCode = "POPSCode";
    const BeneficiaryAccountNumber = "BeneficiaryAccountNumber";
    const BeneficiaryName = "BeneficiaryName";
    const BeneficiaryAddress1 = "BeneficiaryAddress1";
    const BeneficiaryAddress2 = "BeneficiaryAddress2";
    const BeneficiaryCountry = "BeneficiaryCountry";
    const BeneficiaryBankBICCode = "BeneficiaryBankBICCode";
    const BeneficiaryBankName = "BeneficiaryBankName";
    const BeneficiaryBankAdd = "BeneficiaryBankAdd";
    const BeneficiaryBankCountry = "BeneficiaryBankCountry";
    const IntermediaryBankBICCode = "IntermediaryBankBICCode";
    const IntermediaryBankName = "IntermediaryBankName";
    const IntermediaryBankAddress = "IntermediaryBankAddress";
    const IntermediaryBankCountry = "IntermediaryBankCountry";
    const RemittanceInfo = "RemittanceInfo";
    const CommodityCode = "CommodityCode";
    const CommodityDescription = "CommodityDescription";
    const HSCode = "HSCode";
    const HSCodeDescription = "HSCodeDescription";
    const PurposeOfRemittance = "PurposeOfRemittance";
    const PaymentTerms = "PaymentTerms";
    const IECode = "IECode";
    const TIDMin = "TIDMin";
    const TIDMax = "TIDMax";


    protected function setUp(): void
    {
        parent::setUp();
        $connector = $this->mockSqlConnectorWithReplicaLag(0);
        $this->app->instance('db.connector.mysql', $connector);
        $this->authServiceMock = $this->createAuthServiceMock(['sendRequest']);
        $this->app['config']->set('applications.ufh.mock', true);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Carbon::setTestNow();
    }

    public function testOpgspRepatriation()
    {
        list($partner, $app) = $this->createPartnerAndApplication([
            'partner_type' => 'reseller'
        ]);
        $this->createConfigForPartnerApp($app->getId());
        [$subMerchant, $accessMap] = $this->createSubMerchant($partner, $app,
            ['id'=>self::DEFAULT_SUBMERCHANT_ID]);
        $this->fixtures->edit('merchant', $subMerchant->getId(), [
            'channel' => Channel::ICICI,
            'activated' => true ,
            'suspended_at' => null
        ]);

        $this->fixtures->user->createUserForMerchant($partner->getId());

        $payments = $this->createPaymentEntities(2, $subMerchant->getId());

        $this->initiateSettlements(Channel::ICICI);
        $settlement = $this->getLastEntity('settlement', true);

        $paymentTransaction1 = $this->getEntities('transaction', ['entity_id' => $payments[0]['id']], true);
        $paymentTransaction2 = $this->getEntities('transaction', ['entity_id' => $payments[1]['id']], true);

        $this->fixtures->stripSign($settlement['id']);

        $request = [
            'url' => '/settlements/status/update',
            'method' => 'POST',
            'content' => [
                'id'                            => $settlement['id'],
                'utr'                           => '12312312311',
                'status'                        => 'processed',
                'redacted_ba'                   => 'sample',
                'remarks'                       => 'xyz',
                'failure_reason'                => 'na',
                'trigger_failed_notification'   => false
            ],
        ];

        $this->ba->settlementsAuth();
        $this->makeRequestAndGetContent($request);

        $entries = [
            [
                'Consolidated' => $this->getConsolidatedDataSheet($settlement),
                'Transactional' => $this->getTransactionalDataSheet($settlement, [$paymentTransaction1['items'][0], $paymentTransaction2['items'][0]]),
            ],
        ];

        $url = $this->writeToExcelFile($entries[0], 'file', 'files/filestore', ['Consolidated', 'Transactional']);
        $uploadedFile = $this->createUploadedFile($url);

        $input = [
            'manual'           => true,
            'partner'          => 'icici',
            'attachment-count' => 1,
        ];

        $lambdaRequest = [
            'url'     => '/settlements/opgsp/repat',
            'content' => $input,
            'method'  => 'POST',
            'files' => [
                'file' => $uploadedFile,
            ],
        ];

        $this->ba->h2hAuth();
        $content = $this->makeRequestAndGetContent($lambdaRequest);
        $this->assertTrue($content['success']);

        $repatriationEntity = $this->getLastEntity('settlement_international_repatriation', true);
        $this->assertEquals($settlement['amount'], $repatriationEntity['amount']);
        $this->assertEquals('INR', $repatriationEntity['currency']);
        $this->assertEquals($settlement['id'], $repatriationEntity['settlement_ids'][0]);
        $this->assertEquals($settlement['amount']/self::EXCHANGE_RATE, $repatriationEntity['credit_amount']);
        $this->assertEquals('USD', $repatriationEntity['credit_currency']);
    }

    protected function getConsolidatedDataSheet($settlement)
    {
        return [
            [
                self::Date => Carbon::createFromTimestamp($settlement['updated_at'])->isoFormat('DD-MM-YYYY'),
                self::OPGSPTranRefNo => $settlement['id'],
                self::INRAmount => number_format(($settlement['amount']/100),2),
                self::CURRENCY => 'USD',
                self::IBANNumber => '',
                self::CNAPSCode => '',
                self::POPSCode => '',
                self::BeneficiaryAccountNumber => '1234567890',
                self::BeneficiaryName => 'test name',
                self::BeneficiaryAddress1 => 'test address',
                self::BeneficiaryAddress2 => '',
                self::BeneficiaryCountry => 'United States',
                self::BeneficiaryBankBICCode => 'TEST12345',
                self::BeneficiaryBankName => 'test bank name',
                self::BeneficiaryBankAdd => 'test bank address',
                self::BeneficiaryBankCountry => 'United States',
                self::IntermediaryBankBICCode => '',
                self::IntermediaryBankName => '',
                self::IntermediaryBankAddress => '',
                self::IntermediaryBankCountry => '',
                self::RemittanceInfo => '',
                self::CommodityCode => 'Digital',
                self::CommodityDescription => '',
                self::HSCode => '',
                self::HSCodeDescription => '',
                self::PurposeOfRemittance => 'Digital',
                self::PaymentTerms => '',
                self::IECode => '',
                self::TIDMin => '',
                self::TIDMax => '',
            ],
        ];
    }

    protected function getTransactionalDataSheet($settlement, $transactions)
    {
        $header = $this->getTransactionHeader();
        $transactionalData = [];
        foreach ($transactions as $transaction)
        {
            $transactionalData[] = [
                $header['Date']   => Carbon::createFromTimestamp($transaction['created_at'])->isoFormat('DD-MM-YYYY'),
                $header['OPGSPTransactionRefNo']   => $transaction['entity_id'],
                $header['INRAmount']   => ((float)$transaction['credit']) / 100,
                $header['CURRENCY ']   => 'USD',
                $header['IBANNumber']   => '',
                $header['CNAPSCode']   => '',
                $header['POPSCode']   => '',
                $header['BeneficiaryBankCountry']   => 'United State',
                $header['BeneficiaryBankAdd']   => 'Test bank address',
                $header['BeneficiaryAccountNumber']   => '1234567890',
                $header['BeneficiaryName']   => 'Test Name',
                $header['BeneficiaryAddress1']   => 'Test Address1',
                $header['BeneficiaryAddress2']   => '',
                $header['BeneficiaryCountry']   => 'United State',
                $header['BeneficiaryBankBICCode']   => 'TEST000123',
                $header['BeneficiaryBankName']   => 'Test Bank Name',
                $header['IntermediaryBankBICCode']   => '',
                $header['IntermediaryBankName']   => '',
                $header['IntermediaryBankAddress']   => '',
                $header['IntermediaryBankCountry']   => '',
                $header['RemittanceInfo']   => '',
                $header['InvoiceNumber']   => 'doc_123456',
                $header['InvoiceDate']   => Carbon::createFromTimestamp($transaction['created_at'])->isoFormat('DD-MM-YYYY'),
                $header['CommodityCode']   => 'Digital',
                $header['CommodityDescription']   => '',
                $header['Quantity']   => '',
                $header['Rate']   => '',
                $header['HSCode']   => 'TEST0012',
                $header['HSCodeDescription']   => 'Test HS Code Description',
                $header['BuyerName']   => 'Test Name',
                $header['BuyerAddress']   => 'Test Address',
                $header['PurposeOfRemittance']   => 'Digital',
                $header['PaymentTerms']   => '',
                $header['IECode']   => '',
                $header['AirwayBill']   => '',
                $header['TransactionAmount']   => ((float)$transaction['credit']) / 100,
                $header['RequestedAction']   => 'capture',
                $header['RequestID']   => $transaction['id'],
                $header['ProductInfo']   => '',
                $header['MID']   => 'MID',
                $header['ProcessingFee']   => 'Processing Fee',
                $header['GST']   => ((float)$transaction['tax'] ?? 0) / 100,
                $header['MerchantTransactionId']   => '',
                $header['PAN']   => '',
                $header['DOB']   => '',
                $header['Mode']   => 'card',
                $header['PgLabel']   => '',
                $header['CardType']   => '',
                $header['IssuingBank']   => '',
                $header['BankRefNumber']   => '',
                $header['ExchangeRate']   => self::EXCHANGE_RATE,
                $header['SettlementAmount']   => ($settlement['amount'] / (count($transactions) * self::EXCHANGE_RATE * 100)),
                $header['TrackNumber']   => 'TEST123456',
            ];
        }
        return $transactionalData;
    }

    protected function getTransactionHeader()
    {
        return [
            'Date'  => 'Date',
            'OPGSPTransactionRefNo'  => 'OPGSP Transaction Ref No',
            'INRAmount'  => 'INR amount',
            'CURRENCY '  => 'Currency in which remittance to be made',
            'IBANNumber'  => 'IBAN Number',
            'CNAPSCode'  => 'CNAPS Code',
            'POPSCode'  => 'POPS Code',
            'BeneficiaryBankCountry'  => 'Beneficiary Bank Country',
            'BeneficiaryBankAdd'  => 'Beneficiary Bank Address',
            'BeneficiaryAccountNumber'  => 'Beneficiary Account Number',
            'BeneficiaryName'  => 'Beneficiary Name',
            'BeneficiaryAddress1'  => 'Beneficiary Address1',
            'BeneficiaryAddress2'  => 'Beneficiary Address2',
            'BeneficiaryCountry'  => 'Beneficiary Country',
            'BeneficiaryBankBICCode'  => 'Beneficiary Bank BIC Code',
            'BeneficiaryBankName'  => 'Beneficiary Bank Name',
            'IntermediaryBankBICCode'  => 'Intermediary Bank BIC Code',
            'IntermediaryBankName'  => 'Intermediary Bank Name',
            'IntermediaryBankAddress'  => 'Intermediary Bank Address',
            'IntermediaryBankCountry'  => 'Intermediary Bank Country',
            'RemittanceInfo'  => 'Remittance Info',
            'InvoiceNumber'  => 'Invoice Number',
            'InvoiceDate'  => 'Invoice Date',
            'CommodityCode'  => 'Commodity Code',
            'CommodityDescription'  => 'Commodity Description',
            'Quantity'  => 'Quantity',
            'Rate'  => 'Rate',
            'HSCode'  => 'HS Code',
            'HSCodeDescription'  => 'HS Code Description',
            'BuyerName'  => 'Buyer Name',
            'BuyerAddress'  => 'Buyer Address',
            'PurposeOfRemittance'  => 'Purpose Of Remittance',
            'PaymentTerms'  => 'Payment Terms',
            'IECode'  => 'IE Code',
            'AirwayBill'  => 'Airway Bill',
            'TransactionAmount'  => 'Transaction Amount',
            'RequestedAction'  => 'Requested Action',
            'RequestID'  => 'Request ID',
            'ProductInfo'  => 'Product Info',
            'MID'  => 'MID',
            'ProcessingFee'  => 'Processing Fee',
            'GST'  => 'GST',
            'MerchantTransactionId'  => 'Merchant Transaction Id',
            'PAN'  => 'PAN',
            'DOB'  => 'DOB',
            'Mode'  => 'Mode',
            'PgLabel'  => 'Pg Label',
            'CardType'  => 'Card Type',
            'IssuingBank'  => 'Issuing Bank',
            'BankRefNumber'  => 'Bank Ref Number',
            // added by bank
            'ExchangeRate' => 'Exchange Rate',
            'SettlementAmount' => 'Settlement Amount',
            'TrackNumber' => 'Track Number',
        ];
    }

    protected function createUploadedFile(string $url, $fileName = 'file_acct_0123.xlsx'): UploadedFile
    {
        $mime = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        return new UploadedFile(
            $url,
            $fileName,
            $mime,
            null,
            true);
    }

    protected function performAdminActionOnDispute(array $input, $disputeId)
    {
        $this->addPermissionToBaAdmin('edit_dispute');
        $admin = $this->ba->getAdmin();
        $this->fixtures->admin->edit($admin["id"], ['allow_all_merchants' => true]);
        $this->ba->adminProxyAuth(self::DEFAULT_SUBMERCHANT_ID, 'rzp_test_' . self::DEFAULT_SUBMERCHANT_ID);

        return $this->makeRequestAndGetContent([
            'url'     => '/disputes/disp_' . $disputeId,
            'method'  => 'POST',
            'content' => $input,
        ]);
    }
}
