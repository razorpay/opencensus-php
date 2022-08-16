<?php

namespace RZP\Tests\Functional\Batch;

use RZP\Tests\Functional\Batch\BatchTestTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Tests\Functional\Helpers\VirtualAccount\VirtualAccountTrait;

class EzetapSettlementBatchTest extends TestCase
{
    use TestsWebhookEvents;
    use VirtualAccountTrait;
    use BatchTestTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/EzetapSettlementBatchTestData.php';

        parent::setUp();

        $this->fixtures->on('test')->create('terminal:shared_bank_account_terminal');

        $this->fixtures->create('terminal:vpa_shared_terminal_icici');
    }

    public function testValidateEzetapSettlement()
    {
        $this->ba->proxyAuth();

        $entries = $this->getDefaultFileEntries();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->startTest();
    }

    public function testValidateEzetapSettlementMissingData()
    {
        $this->ba->proxyAuth();

        $entries = $this->getFileEntriesWithMissingData();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->startTest();
    }

    protected function getDefaultFileEntries()
    {
        return [
            [
                'Txn.Source' => 'Cards',
                'MERCHANT CODE / External MID' => '12345789abcqwe',
                'TERMINAL NUMBER / External TID' => '1234567890abc',
                'CARD NUMBER  / Payer VPA / Customer Name' => '123456789012334',
                'MERCHANT_TRACKID' => 'abcd',
                'TRANS DATE' => '11-DEC-21',
                'SETTLE DATE' => '12-DEC-21',
                'DOMESTIC AMT / Transaction Amount' => '2000.00',
                'Net Amount' => '1958.58',
                'UDF1' => '1',
                'UDF2' => '2',
                'UDF3' => '3',
                'UDF4' => '4',
                'UDF5' => '5',
                'TRAN_ID / UPI Trxn ID/ Bank Reference No' => '202171079149009',
                'SEQUENCE NUMBER / Txn ref no. (RRN)' => '134579072343',
                'DEBITCREDIT_TYPE / Trans Type' => 'debit',
                'REC FMT / Transaction Type' => 'BAT',
                'CGST AMT' => '0.00',
                'SGST AMT' => '0.00',
                'IGST AMT' => '0.00',
                'UTGST AMT' => '0.00',
                'MSF' => '401.20',
                'GSTN_No' => '123456789',
                'Merchant Name' => 'Paridhi',
                'BAT NBR' => '15',
                'UPVALUE' => '11',
                'CARD TYPE' => 'DEBIT',
                'INTNL AMT' => '0.00',
                'APPROV CODE' => '153709',
                'ARN NO' => '74332741346134595638548',
                'SERV TAX' => '0.00',
                'SB Cess' => '0.00',
                'KK Cess' => '0.00',
                'INVOICE_NUMBER' => '123',
                'UPI Merchant ID' => '123',
                'Merchant VPA' => 'abc',
                'Customer Ref No. (RRN)' => 'dfer',
                'Currency' => 'INR',
                'Pay Type' => 'P2A',
            ],
        ];
    }

    protected function getFileEntriesWithMissingData()
    {
        return [
            [
                'Txn.Source' => 'Cards',
                'MERCHANT CODE / External MID' => '12345789abcqwe',
                'TERMINAL NUMBER / External TID' => '1234567890abc',
                'CARD NUMBER  / Payer VPA / Customer Name' => '123456789012334',
                'MERCHANT_TRACKID' => 'abcd',
            ],
        ];
    }
}
