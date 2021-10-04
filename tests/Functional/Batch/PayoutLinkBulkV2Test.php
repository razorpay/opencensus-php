<?php

namespace RZP\Tests\Functional\Batch;

use PhpOffice\PhpSpreadsheet\IOFactory;

use RZP\Models\Batch;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;

class PayoutLinkBulkV2Test extends TestCase
{
    use BatchTestTrait;
    use TestsBusinessBanking;

    protected $merchant;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/PayoutLinkBulkV2TestData.php';

        parent::setUp();

        $this->ba->privateAuth();

        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $this->ba->proxyAuth();

        $this->mockRazorxTreatment();

        $this->merchant = $this->getDbEntityById('merchant', '10000000000000');
    }

    public function testValidateBatchPayoutLinkBulkV2CSV()
    {
        $entries = [
            [
                Batch\Header::PAYOUT_LINK_BULK_CONTACT_NAME        => 'Amit',
                Batch\Header::PAYOUT_LINK_BULK_CONTACT_NUMBER      => '9876543210',
                Batch\Header::PAYOUT_LINK_BULK_CONTACT_EMAIL       => 'amit@razorpay.com',
                Batch\Header::PAYOUT_LINK_BULK_PAYOUT_DESC         => 'testing',
                Batch\Header::CONTACT_TYPE                         => 'employee',
                Batch\Header::PAYOUT_LINK_BULK_AMOUNT              => 100,
                Batch\Header::PAYOUT_LINK_BULK_SEND_SMS            => 'Yes',
                Batch\Header::PAYOUT_LINK_BULK_SEND_EMAIL          => 'Yes',
                Batch\Header::PAYOUT_PURPOSE                       => 'refund',
                Batch\Header::PAYOUT_LINK_BULK_REFERENCE_ID        => 'REFERENCE01',
                Batch\Header::PAYOUT_LINK_BULK_NOTES_TITLE         => 'test key',
                Batch\Header::PAYOUT_LINK_BULK_NOTES_DESC          => 'test value',
                Batch\Header::PAYOUT_LINK_BULK_EXPIRY_DATE         => '20/11/2020',
                Batch\Header::PAYOUT_LINK_BULK_EXPIRY_TIME         => '10:30',
            ],
        ];

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertEquals(10000, $response['total_payout_link_amount']);

        $fileContent = file($response['signed_url']);

        $expectedHeaderRow =  'Name of Contact,Contact Phone Number,Contact Email ID,Payout Description,Contact Type,' .
            'Payout Link Amount,Send Link to Phone Number,Send Link to Mail ID,Payout Purpose,Reference ID(optional),Internal notes(optional): Title,' .
            'Internal notes(optional): Description,Expiry Date(optional),Expiry Time(optional),Error Code,Error Description';

        $expectedDataRow = 'Amit,9876543210,amit@razorpay.com,testing,employee,100,Yes,Yes,refund,REFERENCE01,test key,test value,20/11/2020,10:30,,';

        $this->assertEquals($expectedHeaderRow, trim($fileContent[0]));

        $this->assertEquals($expectedDataRow, trim($fileContent[1]));
    }

    // Validation should go through even when some optional headers are missing
    public function testValidateBatchPayoutLinkBulkV2CSVOptionalHeaders()
    {
        $entries = [
            [
                Batch\Header::PAYOUT_LINK_BULK_CONTACT_NAME        => 'Amit',
                Batch\Header::PAYOUT_LINK_BULK_CONTACT_NUMBER      => '9876543210',
                Batch\Header::PAYOUT_LINK_BULK_CONTACT_EMAIL       => 'amit@razorpay.com',
                Batch\Header::PAYOUT_LINK_BULK_PAYOUT_DESC         => 'testing',
                Batch\Header::CONTACT_TYPE                         => 'employee',
                Batch\Header::PAYOUT_LINK_BULK_AMOUNT              => 100,
                Batch\Header::PAYOUT_LINK_BULK_SEND_SMS            => 'Yes',
                Batch\Header::PAYOUT_LINK_BULK_SEND_EMAIL          => 'Yes',
                Batch\Header::PAYOUT_PURPOSE                       => 'refund',
            ],
        ];

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $fileContent = file($response['signed_url']);

        $expectedHeaderRow =  'Name of Contact,Contact Phone Number,Contact Email ID,Payout Description,Contact Type,' .
            'Payout Link Amount,Send Link to Phone Number,Send Link to Mail ID,Payout Purpose,Reference ID(optional),Internal notes(optional): Title,' .
            'Internal notes(optional): Description,Expiry Date(optional),Expiry Time(optional),Error Code,Error Description';

        $expectedDataRow = 'Amit,9876543210,amit@razorpay.com,testing,employee,100,Yes,Yes,refund,,,,,,,';

        $this->assertEquals($expectedHeaderRow, trim($fileContent[0]));

        $this->assertEquals($expectedDataRow, trim($fileContent[1]));
    }

    // Removing header 'Payout Description' which is mandatory. This should return an error.
    public function testCreateBatchPayoutLinkBulkV2CSVMissingMandatoryHeader()
    {
        $entries = [
            [
                Batch\Header::PAYOUT_LINK_BULK_CONTACT_NAME        => 'Amit',
                Batch\Header::PAYOUT_LINK_BULK_CONTACT_NUMBER      => '9876543210',
                Batch\Header::PAYOUT_LINK_BULK_CONTACT_EMAIL       => 'amit@razorpay.com',
                Batch\Header::CONTACT_TYPE                         => 'employee',
                Batch\Header::PAYOUT_LINK_BULK_AMOUNT              => 100,
                Batch\Header::PAYOUT_LINK_BULK_SEND_SMS            => 'Yes',
                Batch\Header::PAYOUT_LINK_BULK_SEND_EMAIL          => 'Yes',
                Batch\Header::PAYOUT_PURPOSE                       => 'refund',
                Batch\Header::PAYOUT_LINK_BULK_REFERENCE_ID        => 'REFERENCE01',
                Batch\Header::PAYOUT_LINK_BULK_NOTES_TITLE         => 'test key',
                Batch\Header::PAYOUT_LINK_BULK_NOTES_DESC          => 'test value',
                Batch\Header::PAYOUT_LINK_BULK_EXPIRY_DATE         => '20/11/2020',
                Batch\Header::PAYOUT_LINK_BULK_EXPIRY_TIME         => '10:30',
            ],
        ];

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testValidateErrorFileForBatchPayoutLinkBulkV2CSVInvalidSendSMS()
    {
        $entries = [
            [
                Batch\Header::PAYOUT_LINK_BULK_CONTACT_NAME        => 'Amit',
                Batch\Header::PAYOUT_LINK_BULK_CONTACT_NUMBER      => '9876543210',
                Batch\Header::PAYOUT_LINK_BULK_CONTACT_EMAIL       => 'amit@razorpay.com',
                Batch\Header::PAYOUT_LINK_BULK_PAYOUT_DESC         => 'testing',
                Batch\Header::CONTACT_TYPE                         => 'employee',
                Batch\Header::PAYOUT_LINK_BULK_AMOUNT              => 100,
                Batch\Header::PAYOUT_LINK_BULK_SEND_SMS            => 'random',
                Batch\Header::PAYOUT_LINK_BULK_SEND_EMAIL          => 'Yes',
                Batch\Header::PAYOUT_PURPOSE                       => 'refund',
                Batch\Header::PAYOUT_LINK_BULK_REFERENCE_ID        => 'REFERENCE01',
                Batch\Header::PAYOUT_LINK_BULK_NOTES_TITLE         => 'test key',
                Batch\Header::PAYOUT_LINK_BULK_NOTES_DESC          => 'test value',
                Batch\Header::PAYOUT_LINK_BULK_EXPIRY_DATE         => '20/11/2020',
                Batch\Header::PAYOUT_LINK_BULK_EXPIRY_TIME         => '10:30',
            ],
        ];

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $fileContent = file($response['signed_url']);

        $expectedHeaderRow =  'Name of Contact,Contact Phone Number,Contact Email ID,Payout Description,Contact Type,' .
            'Payout Link Amount,Send Link to Phone Number,Send Link to Mail ID,Payout Purpose,Reference ID(optional),Internal notes(optional): Title,' .
            'Internal notes(optional): Description,Expiry Date(optional),Expiry Time(optional),Error Code,Error Description';

        $expectedDataRow = 'Amit,9876543210,amit@razorpay.com,testing,employee,100,random,Yes,refund,REFERENCE01,test key,test value,' .
            '20/11/2020,10:30,BAD_REQUEST_ERROR,The selected send link to phone number is invalid.';

        $this->assertEquals($expectedHeaderRow, trim($fileContent[0]));

        $this->assertEquals($expectedDataRow, trim($fileContent[1]));
    }

    public function testValidateErrorFileForBatchPayoutLinkBulkV2CSVInvalidAmount()
    {
        $entries = [
            [
                Batch\Header::PAYOUT_LINK_BULK_CONTACT_NAME        => 'Amit',
                Batch\Header::PAYOUT_LINK_BULK_CONTACT_NUMBER      => '9876543210',
                Batch\Header::PAYOUT_LINK_BULK_CONTACT_EMAIL       => 'amit@razorpay.com',
                Batch\Header::PAYOUT_LINK_BULK_PAYOUT_DESC         => 'testing',
                Batch\Header::CONTACT_TYPE                         => 'employee',
                Batch\Header::PAYOUT_LINK_BULK_AMOUNT              => 'ABCD',
                Batch\Header::PAYOUT_LINK_BULK_SEND_SMS            => 'Yes',
                Batch\Header::PAYOUT_LINK_BULK_SEND_EMAIL          => 'Yes',
                Batch\Header::PAYOUT_PURPOSE                       => 'refund',
                Batch\Header::PAYOUT_LINK_BULK_REFERENCE_ID        => 'REFERENCE01',
                Batch\Header::PAYOUT_LINK_BULK_NOTES_TITLE         => 'test key',
                Batch\Header::PAYOUT_LINK_BULK_NOTES_DESC          => 'test value',
                Batch\Header::PAYOUT_LINK_BULK_EXPIRY_DATE         => '20/11/2020',
                Batch\Header::PAYOUT_LINK_BULK_EXPIRY_TIME         => '10:30',
            ],
        ];

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $fileContent = file($response['signed_url']);

        $expectedHeaderRow =  'Name of Contact,Contact Phone Number,Contact Email ID,Payout Description,Contact Type,' .
            'Payout Link Amount,Send Link to Phone Number,Send Link to Mail ID,Payout Purpose,Reference ID(optional),Internal notes(optional): Title,' .
            'Internal notes(optional): Description,Expiry Date(optional),Expiry Time(optional),Error Code,Error Description';

        $expectedDataRow = 'Amit,9876543210,amit@razorpay.com,testing,employee,ABCD,Yes,Yes,refund,REFERENCE01,test key,test value,' .
            '20/11/2020,10:30,BAD_REQUEST_ERROR,The payout link amount format is invalid.';

        $this->assertEquals($expectedHeaderRow, trim($fileContent[0]));

        $this->assertEquals($expectedDataRow, trim($fileContent[1]));
    }

    public function testValidateBatchPayoutLinkBulkV2CSVRandomExtraHeader()
    {
        $entries = [
            [
                Batch\Header::PAYOUT_LINK_BULK_CONTACT_NAME        => 'Amit',
                Batch\Header::PAYOUT_LINK_BULK_CONTACT_NUMBER      => '9876543210',
                Batch\Header::PAYOUT_LINK_BULK_CONTACT_EMAIL       => 'amit@razorpay.com',
                Batch\Header::PAYOUT_LINK_BULK_PAYOUT_DESC         => 'testing',
                Batch\Header::CONTACT_TYPE                         => 'employee',
                Batch\Header::PAYOUT_LINK_BULK_AMOUNT              => 100,
                Batch\Header::PAYOUT_LINK_BULK_SEND_SMS            => 'random',
                Batch\Header::PAYOUT_LINK_BULK_SEND_EMAIL          => 'Yes',
                Batch\Header::PAYOUT_PURPOSE                       => 'refund',
                Batch\Header::PAYOUT_LINK_BULK_REFERENCE_ID        => 'REFERENCE01',
                Batch\Header::PAYOUT_LINK_BULK_NOTES_TITLE         => 'test key',
                Batch\Header::PAYOUT_LINK_BULK_NOTES_DESC          => 'test value',
                Batch\Header::PAYOUT_LINK_BULK_EXPIRY_DATE         => '20/11/2020',
                Batch\Header::PAYOUT_LINK_BULK_EXPIRY_TIME         => '10:30',
                // Below is a random Header picked up from Batch\Headers
                Batch\Header::PRICING_RULE_FEATURE      => 'abc'
            ],
        ];

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testValidateBatchPayoutLinkBulkV2CSVAmountRupeesFloatWithThreeDecimals()
    {
        $entries = [
            [
                Batch\Header::PAYOUT_LINK_BULK_CONTACT_NAME        => 'Amit',
                Batch\Header::PAYOUT_LINK_BULK_CONTACT_NUMBER      => '9876543210',
                Batch\Header::PAYOUT_LINK_BULK_CONTACT_EMAIL       => 'amit@razorpay.com',
                Batch\Header::PAYOUT_LINK_BULK_PAYOUT_DESC         => 'testing',
                Batch\Header::CONTACT_TYPE                         => 'employee',
                Batch\Header::PAYOUT_LINK_BULK_AMOUNT              => 100.333,
                Batch\Header::PAYOUT_LINK_BULK_SEND_SMS            => 'Yes',
                Batch\Header::PAYOUT_LINK_BULK_SEND_EMAIL          => 'Yes',
                Batch\Header::PAYOUT_PURPOSE                       => 'refund',
                Batch\Header::PAYOUT_LINK_BULK_REFERENCE_ID        => 'REFERENCE01',
                Batch\Header::PAYOUT_LINK_BULK_NOTES_TITLE         => 'test key',
                Batch\Header::PAYOUT_LINK_BULK_NOTES_DESC          => 'test value',
                Batch\Header::PAYOUT_LINK_BULK_EXPIRY_DATE         => '20/11/2020',
                Batch\Header::PAYOUT_LINK_BULK_EXPIRY_TIME         => '10:30',
            ],
        ];

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $fileContent = file($response['signed_url']);

        $expectedHeaderRow =  'Name of Contact,Contact Phone Number,Contact Email ID,Payout Description,Contact Type,' .
            'Payout Link Amount,Send Link to Phone Number,Send Link to Mail ID,Payout Purpose,Reference ID(optional),Internal notes(optional): Title,' .
            'Internal notes(optional): Description,Expiry Date(optional),Expiry Time(optional),Error Code,Error Description';

        $expectedDataRow = 'Amit,9876543210,amit@razorpay.com,testing,employee,100.333,Yes,Yes,refund,REFERENCE01,test key,test value,' .
            '20/11/2020,10:30,BAD_REQUEST_ERROR,The payout link amount format is invalid.';

        $this->assertEquals($expectedHeaderRow, trim($fileContent[0]));

        $this->assertEquals($expectedDataRow, trim($fileContent[1]));
    }

    public function testValidateBatchPayoutLinkBulkV2FailForNoNotesTitle()
    {
        $entries = [
            [
                Batch\Header::PAYOUT_LINK_BULK_CONTACT_NAME        => 'Amit',
                Batch\Header::PAYOUT_LINK_BULK_CONTACT_NUMBER      => '9876543210',
                Batch\Header::PAYOUT_LINK_BULK_CONTACT_EMAIL       => 'amit@razorpay.com',
                Batch\Header::PAYOUT_LINK_BULK_PAYOUT_DESC         => 'testing',
                Batch\Header::CONTACT_TYPE                         => 'employee',
                Batch\Header::PAYOUT_LINK_BULK_AMOUNT              => 100,
                Batch\Header::PAYOUT_LINK_BULK_SEND_SMS            => 'Yes',
                Batch\Header::PAYOUT_LINK_BULK_SEND_EMAIL          => 'Yes',
                Batch\Header::PAYOUT_PURPOSE                       => 'refund',
                Batch\Header::PAYOUT_LINK_BULK_REFERENCE_ID        => 'REFERENCE01',
                Batch\Header::PAYOUT_LINK_BULK_NOTES_TITLE         => '',
                Batch\Header::PAYOUT_LINK_BULK_NOTES_DESC          => 'test value',
                Batch\Header::PAYOUT_LINK_BULK_EXPIRY_DATE         => '20/11/2020',
                Batch\Header::PAYOUT_LINK_BULK_EXPIRY_TIME         => '10:30',
            ],
        ];

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $fileContent = file($response['signed_url']);

        $expectedHeaderRow =  'Name of Contact,Contact Phone Number,Contact Email ID,Payout Description,Contact Type,' .
            'Payout Link Amount,Send Link to Phone Number,Send Link to Mail ID,Payout Purpose,Reference ID(optional),Internal notes(optional): Title,' .
            'Internal notes(optional): Description,Expiry Date(optional),Expiry Time(optional),Error Code,Error Description';

        $expectedDataRow = 'Amit,9876543210,amit@razorpay.com,testing,employee,100,Yes,Yes,refund,REFERENCE01,,test value,20/11/2020,10:30,BAD_REQUEST_ERROR,Notes title missing';

        $this->assertEquals($expectedHeaderRow, trim($fileContent[0]));

        $this->assertEquals($expectedDataRow, trim($fileContent[1]));
    }

    public function testValidateBatchPayoutLinkBulkV2FailForNoNotesDesc()
    {
        $entries = [
            [
                Batch\Header::PAYOUT_LINK_BULK_CONTACT_NAME        => 'Amit',
                Batch\Header::PAYOUT_LINK_BULK_CONTACT_NUMBER      => '9876543210',
                Batch\Header::PAYOUT_LINK_BULK_CONTACT_EMAIL       => 'amit@razorpay.com',
                Batch\Header::PAYOUT_LINK_BULK_PAYOUT_DESC         => 'testing',
                Batch\Header::CONTACT_TYPE                         => 'employee',
                Batch\Header::PAYOUT_LINK_BULK_AMOUNT              => 100,
                Batch\Header::PAYOUT_LINK_BULK_SEND_SMS            => 'Yes',
                Batch\Header::PAYOUT_LINK_BULK_SEND_EMAIL          => 'Yes',
                Batch\Header::PAYOUT_PURPOSE                       => 'refund',
                Batch\Header::PAYOUT_LINK_BULK_REFERENCE_ID        => 'REFERENCE01',
                Batch\Header::PAYOUT_LINK_BULK_NOTES_TITLE         => 'test key',
                Batch\Header::PAYOUT_LINK_BULK_NOTES_DESC          => '',
                Batch\Header::PAYOUT_LINK_BULK_EXPIRY_DATE         => '20/11/2020',
                Batch\Header::PAYOUT_LINK_BULK_EXPIRY_TIME         => '10:30',
            ],
        ];

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $fileContent = file($response['signed_url']);

        $expectedHeaderRow =  'Name of Contact,Contact Phone Number,Contact Email ID,Payout Description,Contact Type,' .
            'Payout Link Amount,Send Link to Phone Number,Send Link to Mail ID,Payout Purpose,Reference ID(optional),Internal notes(optional): Title,' .
            'Internal notes(optional): Description,Expiry Date(optional),Expiry Time(optional),Error Code,Error Description';

        $expectedDataRow = 'Amit,9876543210,amit@razorpay.com,testing,employee,100,Yes,Yes,refund,REFERENCE01,test key,,20/11/2020,10:30,BAD_REQUEST_ERROR,Notes description missing';

        $this->assertEquals($expectedHeaderRow, trim($fileContent[0]));

        $this->assertEquals($expectedDataRow, trim($fileContent[1]));
    }

    public function testValidateBatchPayoutLinkBulkV2FailForMissingContactNumberForSendSMS()
    {
        $entries = [
            [
                Batch\Header::PAYOUT_LINK_BULK_CONTACT_NAME        => 'Amit',
                Batch\Header::PAYOUT_LINK_BULK_CONTACT_NUMBER      => '',
                Batch\Header::PAYOUT_LINK_BULK_CONTACT_EMAIL       => 'amit@razorpay.com',
                Batch\Header::PAYOUT_LINK_BULK_PAYOUT_DESC         => 'testing',
                Batch\Header::CONTACT_TYPE                         => 'employee',
                Batch\Header::PAYOUT_LINK_BULK_AMOUNT              => 100,
                Batch\Header::PAYOUT_LINK_BULK_SEND_SMS            => 'Yes',
                Batch\Header::PAYOUT_LINK_BULK_SEND_EMAIL          => 'Yes',
                Batch\Header::PAYOUT_PURPOSE                       => 'refund',
                Batch\Header::PAYOUT_LINK_BULK_REFERENCE_ID        => 'REFERENCE01',
                Batch\Header::PAYOUT_LINK_BULK_NOTES_TITLE         => 'test key',
                Batch\Header::PAYOUT_LINK_BULK_NOTES_DESC          => 'test value',
                Batch\Header::PAYOUT_LINK_BULK_EXPIRY_DATE         => '20/11/2020',
                Batch\Header::PAYOUT_LINK_BULK_EXPIRY_TIME         => '10:30',
            ],
        ];

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $fileContent = file($response['signed_url']);

        $expectedHeaderRow =  'Name of Contact,Contact Phone Number,Contact Email ID,Payout Description,Contact Type,' .
            'Payout Link Amount,Send Link to Phone Number,Send Link to Mail ID,Payout Purpose,Reference ID(optional),Internal notes(optional): Title,' .
            'Internal notes(optional): Description,Expiry Date(optional),Expiry Time(optional),Error Code,Error Description';

        $expectedDataRow = 'Amit,,amit@razorpay.com,testing,employee,100,Yes,Yes,refund,REFERENCE01,test key,test value,20/11/2020,10:30,BAD_REQUEST_ERROR,The contact phone number field is required when send link to phone number is Yes.';

        $this->assertEquals($expectedHeaderRow, trim($fileContent[0]));

        $this->assertEquals($expectedDataRow, trim($fileContent[1]));
    }

    public function testValidateBatchPayoutLinkBulkV2FailForMissingContactMailForSendEmail()
    {
        $entries = [
            [
                Batch\Header::PAYOUT_LINK_BULK_CONTACT_NAME        => 'Amit',
                Batch\Header::PAYOUT_LINK_BULK_CONTACT_NUMBER      => '9876543210',
                Batch\Header::PAYOUT_LINK_BULK_CONTACT_EMAIL       => '',
                Batch\Header::PAYOUT_LINK_BULK_PAYOUT_DESC         => 'testing',
                Batch\Header::CONTACT_TYPE                         => 'employee',
                Batch\Header::PAYOUT_LINK_BULK_AMOUNT              => 100,
                Batch\Header::PAYOUT_LINK_BULK_SEND_SMS            => 'Yes',
                Batch\Header::PAYOUT_LINK_BULK_SEND_EMAIL          => 'Yes',
                Batch\Header::PAYOUT_PURPOSE                       => 'refund',
                Batch\Header::PAYOUT_LINK_BULK_REFERENCE_ID        => 'REFERENCE01',
                Batch\Header::PAYOUT_LINK_BULK_NOTES_TITLE         => 'test key',
                Batch\Header::PAYOUT_LINK_BULK_NOTES_DESC          => 'test value',
                Batch\Header::PAYOUT_LINK_BULK_EXPIRY_DATE         => '20/11/2020',
                Batch\Header::PAYOUT_LINK_BULK_EXPIRY_TIME         => '10:30',
            ],
        ];

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $fileContent = file($response['signed_url']);

        $expectedHeaderRow =  'Name of Contact,Contact Phone Number,Contact Email ID,Payout Description,Contact Type,' .
            'Payout Link Amount,Send Link to Phone Number,Send Link to Mail ID,Payout Purpose,Reference ID(optional),Internal notes(optional): Title,' .
            'Internal notes(optional): Description,Expiry Date(optional),Expiry Time(optional),Error Code,Error Description';

        $expectedDataRow = 'Amit,9876543210,,testing,employee,100,Yes,Yes,refund,REFERENCE01,test key,test value,20/11/2020,10:30,BAD_REQUEST_ERROR,The contact email i d field is required when send link to mail i d is Yes.';

        $this->assertEquals($expectedHeaderRow, trim($fileContent[0]));

        $this->assertEquals($expectedDataRow, trim($fileContent[1]));
    }

    public function testValidateBatchPayoutLinkBulkV2FailForMissingContactNumberAndMail()
    {
        $entries = [
            [
                Batch\Header::PAYOUT_LINK_BULK_CONTACT_NAME        => 'Amit',
                Batch\Header::PAYOUT_LINK_BULK_CONTACT_NUMBER      => '',
                Batch\Header::PAYOUT_LINK_BULK_CONTACT_EMAIL       => '',
                Batch\Header::PAYOUT_LINK_BULK_PAYOUT_DESC         => 'testing',
                Batch\Header::CONTACT_TYPE                         => 'employee',
                Batch\Header::PAYOUT_LINK_BULK_AMOUNT              => 100,
                Batch\Header::PAYOUT_LINK_BULK_SEND_SMS            => 'No',
                Batch\Header::PAYOUT_LINK_BULK_SEND_EMAIL          => 'No',
                Batch\Header::PAYOUT_PURPOSE                       => 'refund',
                Batch\Header::PAYOUT_LINK_BULK_REFERENCE_ID        => 'REFERENCE01',
                Batch\Header::PAYOUT_LINK_BULK_NOTES_TITLE         => 'test key',
                Batch\Header::PAYOUT_LINK_BULK_NOTES_DESC          => 'test value',
                Batch\Header::PAYOUT_LINK_BULK_EXPIRY_DATE         => '20/11/2020',
                Batch\Header::PAYOUT_LINK_BULK_EXPIRY_TIME         => '10:30',
            ],
        ];

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $fileContent = file($response['signed_url']);

        $expectedHeaderRow =  'Name of Contact,Contact Phone Number,Contact Email ID,Payout Description,Contact Type,' .
            'Payout Link Amount,Send Link to Phone Number,Send Link to Mail ID,Payout Purpose,Reference ID(optional),Internal notes(optional): Title,' .
            'Internal notes(optional): Description,Expiry Date(optional),Expiry Time(optional),Error Code,Error Description';

        $expectedDataRow = 'Amit,,,testing,employee,100,No,No,refund,REFERENCE01,test key,test value,20/11/2020,10:30,BAD_REQUEST_ERROR,Both contact number and contact email cannot be empty';

        $this->assertEquals($expectedHeaderRow, trim($fileContent[0]));

        $this->assertEquals($expectedDataRow, trim($fileContent[1]));
    }

    public function testValidateBatchPayoutLinkBulkV2FailForMissingExpireDate()
    {
        $entries = [
            [
                Batch\Header::PAYOUT_LINK_BULK_CONTACT_NAME        => 'Amit',
                Batch\Header::PAYOUT_LINK_BULK_CONTACT_NUMBER      => '9876543210',
                Batch\Header::PAYOUT_LINK_BULK_CONTACT_EMAIL       => 'amit@razorpay.com',
                Batch\Header::PAYOUT_LINK_BULK_PAYOUT_DESC         => 'testing',
                Batch\Header::CONTACT_TYPE                         => 'employee',
                Batch\Header::PAYOUT_LINK_BULK_AMOUNT              => 100,
                Batch\Header::PAYOUT_LINK_BULK_SEND_SMS            => 'Yes',
                Batch\Header::PAYOUT_LINK_BULK_SEND_EMAIL          => 'Yes',
                Batch\Header::PAYOUT_PURPOSE                       => 'refund',
                Batch\Header::PAYOUT_LINK_BULK_REFERENCE_ID        => 'REFERENCE01',
                Batch\Header::PAYOUT_LINK_BULK_NOTES_TITLE         => 'test key',
                Batch\Header::PAYOUT_LINK_BULK_NOTES_DESC          => 'test value',
                Batch\Header::PAYOUT_LINK_BULK_EXPIRY_TIME         => '10:30',
            ],
        ];

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $fileContent = file($response['signed_url']);

        $expectedHeaderRow =  'Name of Contact,Contact Phone Number,Contact Email ID,Payout Description,Contact Type,' .
            'Payout Link Amount,Send Link to Phone Number,Send Link to Mail ID,Payout Purpose,Reference ID(optional),Internal notes(optional): Title,' .
            'Internal notes(optional): Description,Expiry Date(optional),Expiry Time(optional),Error Code,Error Description';

        $expectedDataRow = 'Amit,9876543210,amit@razorpay.com,testing,employee,100,Yes,Yes,refund,REFERENCE01,test key,test value,,10:30,BAD_REQUEST_ERROR,Expiry Date missing but Expiry Time present';

        $this->assertEquals($expectedHeaderRow, trim($fileContent[0]));

        $this->assertEquals($expectedDataRow, trim($fileContent[1]));
    }

    public function testValidateBatchPayoutLinkBulkV2FailForIncorrectExpireDateFormat1()
    {
        // input in format: m/d/y
        $entries = [
            [
                Batch\Header::PAYOUT_LINK_BULK_CONTACT_NAME        => 'Amit',
                Batch\Header::PAYOUT_LINK_BULK_CONTACT_NUMBER      => '9876543210',
                Batch\Header::PAYOUT_LINK_BULK_CONTACT_EMAIL       => 'amit@razorpay.com',
                Batch\Header::PAYOUT_LINK_BULK_PAYOUT_DESC         => 'testing',
                Batch\Header::CONTACT_TYPE                         => 'employee',
                Batch\Header::PAYOUT_LINK_BULK_AMOUNT              => 100,
                Batch\Header::PAYOUT_LINK_BULK_SEND_SMS            => 'Yes',
                Batch\Header::PAYOUT_LINK_BULK_SEND_EMAIL          => 'Yes',
                Batch\Header::PAYOUT_PURPOSE                       => 'refund',
                Batch\Header::PAYOUT_LINK_BULK_REFERENCE_ID        => 'REFERENCE01',
                Batch\Header::PAYOUT_LINK_BULK_NOTES_TITLE         => 'test key',
                Batch\Header::PAYOUT_LINK_BULK_NOTES_DESC          => 'test value',
                Batch\Header::PAYOUT_LINK_BULK_EXPIRY_DATE         => '11/20/2021',
                Batch\Header::PAYOUT_LINK_BULK_EXPIRY_TIME         => '10:30',
            ],
        ];

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $fileContent = file($response['signed_url']);

        $expectedHeaderRow =  'Name of Contact,Contact Phone Number,Contact Email ID,Payout Description,Contact Type,' .
            'Payout Link Amount,Send Link to Phone Number,Send Link to Mail ID,Payout Purpose,Reference ID(optional),Internal notes(optional): Title,' .
            'Internal notes(optional): Description,Expiry Date(optional),Expiry Time(optional),Error Code,Error Description';

        $expectedDataRow = 'Amit,9876543210,amit@razorpay.com,testing,employee,100,Yes,Yes,refund,REFERENCE01,test key,test value,11/20/2021,10:30,BAD_REQUEST_ERROR,Invalid Expiry Date format should be DD/MM/YYYY';

        $this->assertEquals($expectedHeaderRow, trim($fileContent[0]));

        $this->assertEquals($expectedDataRow, trim($fileContent[1]));
    }

    public function testValidateBatchPayoutLinkBulkV2FailForIncorrectExpireDateFormat2()
    {
        // input in format: d-m-y
        $entries = [
            [
                Batch\Header::PAYOUT_LINK_BULK_CONTACT_NAME        => 'Amit',
                Batch\Header::PAYOUT_LINK_BULK_CONTACT_NUMBER      => '9876543210',
                Batch\Header::PAYOUT_LINK_BULK_CONTACT_EMAIL       => 'amit@razorpay.com',
                Batch\Header::PAYOUT_LINK_BULK_PAYOUT_DESC         => 'testing',
                Batch\Header::CONTACT_TYPE                         => 'employee',
                Batch\Header::PAYOUT_LINK_BULK_AMOUNT              => 100,
                Batch\Header::PAYOUT_LINK_BULK_SEND_SMS            => 'Yes',
                Batch\Header::PAYOUT_LINK_BULK_SEND_EMAIL          => 'Yes',
                Batch\Header::PAYOUT_PURPOSE                       => 'refund',
                Batch\Header::PAYOUT_LINK_BULK_REFERENCE_ID        => 'REFERENCE01',
                Batch\Header::PAYOUT_LINK_BULK_NOTES_TITLE         => 'test key',
                Batch\Header::PAYOUT_LINK_BULK_NOTES_DESC          => 'test value',
                Batch\Header::PAYOUT_LINK_BULK_EXPIRY_DATE         => '20-11-2021',
                Batch\Header::PAYOUT_LINK_BULK_EXPIRY_TIME         => '10:30',
            ],
        ];

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $fileContent = file($response['signed_url']);

        $expectedHeaderRow =  'Name of Contact,Contact Phone Number,Contact Email ID,Payout Description,Contact Type,' .
            'Payout Link Amount,Send Link to Phone Number,Send Link to Mail ID,Payout Purpose,Reference ID(optional),Internal notes(optional): Title,' .
            'Internal notes(optional): Description,Expiry Date(optional),Expiry Time(optional),Error Code,Error Description';

        $expectedDataRow = 'Amit,9876543210,amit@razorpay.com,testing,employee,100,Yes,Yes,refund,REFERENCE01,test key,test value,20-11-2021,10:30,BAD_REQUEST_ERROR,Invalid Expiry Date format should be DD/MM/YYYY';

        $this->assertEquals($expectedHeaderRow, trim($fileContent[0]));

        $this->assertEquals($expectedDataRow, trim($fileContent[1]));
    }

    public function testValidateBatchPayoutLinkBulkV2FailForIncorrectExpireTimeFormat()
    {
        // format: h:m:s
        $entries = [
            [
                Batch\Header::PAYOUT_LINK_BULK_CONTACT_NAME        => 'Amit',
                Batch\Header::PAYOUT_LINK_BULK_CONTACT_NUMBER      => '9876543210',
                Batch\Header::PAYOUT_LINK_BULK_CONTACT_EMAIL       => 'amit@razorpay.com',
                Batch\Header::PAYOUT_LINK_BULK_PAYOUT_DESC         => 'testing',
                Batch\Header::CONTACT_TYPE                         => 'employee',
                Batch\Header::PAYOUT_LINK_BULK_AMOUNT              => 100,
                Batch\Header::PAYOUT_LINK_BULK_SEND_SMS            => 'Yes',
                Batch\Header::PAYOUT_LINK_BULK_SEND_EMAIL          => 'Yes',
                Batch\Header::PAYOUT_PURPOSE                       => 'refund',
                Batch\Header::PAYOUT_LINK_BULK_REFERENCE_ID        => 'REFERENCE01',
                Batch\Header::PAYOUT_LINK_BULK_NOTES_TITLE         => 'test key',
                Batch\Header::PAYOUT_LINK_BULK_NOTES_DESC          => 'test value',
                Batch\Header::PAYOUT_LINK_BULK_EXPIRY_DATE         => '20/11/2020',
                Batch\Header::PAYOUT_LINK_BULK_EXPIRY_TIME         => '10:30:15',
            ],
        ];

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $fileContent = file($response['signed_url']);

        $expectedHeaderRow =  'Name of Contact,Contact Phone Number,Contact Email ID,Payout Description,Contact Type,' .
            'Payout Link Amount,Send Link to Phone Number,Send Link to Mail ID,Payout Purpose,Reference ID(optional),Internal notes(optional): Title,' .
            'Internal notes(optional): Description,Expiry Date(optional),Expiry Time(optional),Error Code,Error Description';

        $expectedDataRow = 'Amit,9876543210,amit@razorpay.com,testing,employee,100,Yes,Yes,refund,REFERENCE01,test key,test value,20/11/2020,10:30:15,BAD_REQUEST_ERROR,Invalid Expiry Time format should be HH:MM';

        $this->assertEquals($expectedHeaderRow, trim($fileContent[0]));

        $this->assertEquals($expectedDataRow, trim($fileContent[1]));
    }

    public function testValidateBatchPayoutLinkBulkV2CSVSuccess()
    {
        $entries = [
            [
                Batch\Header::PAYOUT_LINK_BULK_CONTACT_NAME        => 'Amit',
                Batch\Header::PAYOUT_LINK_BULK_CONTACT_NUMBER      => '9876543210',
                Batch\Header::PAYOUT_LINK_BULK_CONTACT_EMAIL       => 'amit@razorpay.com',
                Batch\Header::PAYOUT_LINK_BULK_PAYOUT_DESC         => 'testing',
                Batch\Header::CONTACT_TYPE                         => 'employee',
                Batch\Header::PAYOUT_LINK_BULK_AMOUNT              => 100,
                Batch\Header::PAYOUT_LINK_BULK_SEND_SMS            => 'Yes',
                Batch\Header::PAYOUT_LINK_BULK_SEND_EMAIL          => 'Yes',
                Batch\Header::PAYOUT_PURPOSE                       => 'refund',
                Batch\Header::PAYOUT_LINK_BULK_REFERENCE_ID        => 'REFERENCE01',
                Batch\Header::PAYOUT_LINK_BULK_NOTES_TITLE         => 'test key',
                Batch\Header::PAYOUT_LINK_BULK_NOTES_DESC          => 'test value',
                Batch\Header::PAYOUT_LINK_BULK_EXPIRY_DATE         => '20/11/2020',
                Batch\Header::PAYOUT_LINK_BULK_EXPIRY_TIME         => '10:30',
            ],
        ];

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertEquals(10000, $response['total_payout_link_amount']);

        $fileContent = file($response['signed_url']);

        $expectedHeaderRow =  'Name of Contact,Contact Phone Number,Contact Email ID,Payout Description,Contact Type,' .
            'Payout Link Amount,Send Link to Phone Number,Send Link to Mail ID,Payout Purpose,Reference ID(optional),Internal notes(optional): Title,' .
            'Internal notes(optional): Description,Expiry Date(optional),Expiry Time(optional),Error Code,Error Description';

        $expectedDataRow = 'Amit,9876543210,amit@razorpay.com,testing,employee,100,Yes,Yes,refund,REFERENCE01,test key,test value,20/11/2020,10:30,,';

        $this->assertEquals($expectedHeaderRow, trim($fileContent[0]));

        $this->assertEquals($expectedDataRow, trim($fileContent[1]));
    }

    public function testValidateBatchPayoutLinkBulkV2CSVSuccessPost12Hr()
    {
        $entries = [
            [
                Batch\Header::PAYOUT_LINK_BULK_CONTACT_NAME        => 'Amit',
                Batch\Header::PAYOUT_LINK_BULK_CONTACT_NUMBER      => '9876543210',
                Batch\Header::PAYOUT_LINK_BULK_CONTACT_EMAIL       => 'amit@razorpay.com',
                Batch\Header::PAYOUT_LINK_BULK_PAYOUT_DESC         => 'testing',
                Batch\Header::CONTACT_TYPE                         => 'employee',
                Batch\Header::PAYOUT_LINK_BULK_AMOUNT              => 100,
                Batch\Header::PAYOUT_LINK_BULK_SEND_SMS            => 'Yes',
                Batch\Header::PAYOUT_LINK_BULK_SEND_EMAIL          => 'Yes',
                Batch\Header::PAYOUT_PURPOSE                       => 'refund',
                Batch\Header::PAYOUT_LINK_BULK_REFERENCE_ID        => 'REFERENCE01',
                Batch\Header::PAYOUT_LINK_BULK_NOTES_TITLE         => 'test key',
                Batch\Header::PAYOUT_LINK_BULK_NOTES_DESC          => 'test value',
                Batch\Header::PAYOUT_LINK_BULK_EXPIRY_DATE         => '20/11/2020',
                Batch\Header::PAYOUT_LINK_BULK_EXPIRY_TIME         => '15:30',
            ],
        ];

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertEquals(10000, $response['total_payout_link_amount']);

        $fileContent = file($response['signed_url']);

        $expectedHeaderRow =  'Name of Contact,Contact Phone Number,Contact Email ID,Payout Description,Contact Type,' .
            'Payout Link Amount,Send Link to Phone Number,Send Link to Mail ID,Payout Purpose,Reference ID(optional),Internal notes(optional): Title,' .
            'Internal notes(optional): Description,Expiry Date(optional),Expiry Time(optional),Error Code,Error Description';

        $expectedDataRow = 'Amit,9876543210,amit@razorpay.com,testing,employee,100,Yes,Yes,refund,REFERENCE01,test key,test value,20/11/2020,15:30,,';

        $this->assertEquals($expectedHeaderRow, trim($fileContent[0]));

        $this->assertEquals($expectedDataRow, trim($fileContent[1]));
    }

}
