<?php

namespace RZP\Tests\Functional\Batch;

use PhpOffice\PhpSpreadsheet\IOFactory;

use RZP\Models\Batch;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;

class PayoutLinkBulkTest extends TestCase
{
    use BatchTestTrait;
    use TestsBusinessBanking;

    protected $merchant;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/PayoutLinkBulkTestData.php';

        parent::setUp();

        $this->ba->privateAuth();

        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $this->ba->proxyAuth();

        $this->mockRazorxTreatment();

        $this->merchant = $this->getDbEntityById('merchant', '10000000000000');
    }

    public function testValidateBatchPayoutLinkBulkCSV()
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
            ],
        ];

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertEquals(10000, $response['total_payout_link_amount']);

        $fileContent = file($response['signed_url']);

        $expectedHeaderRow =  'Name of Contact,Contact Phone Number,Contact Email ID,Payout Description,Contact Type,' .
            'Payout Link Amount,Send Link to Phone Number,Send Link to Mail ID,Payout Purpose,Reference ID(optional),Internal notes(optional): Title,' .
            'Internal notes(optional): Description,Error Code,Error Description';

        $expectedDataRow = 'Amit,9876543210,amit@razorpay.com,testing,employee,100,Yes,Yes,refund,REFERENCE01,test key,test value,,';

        $this->assertEquals($expectedHeaderRow, trim($fileContent[0]));

        $this->assertEquals($expectedDataRow, trim($fileContent[1]));
    }

    // Validation should go through even when some optional headers are missing
    public function testValidateBatchPayoutLinkBulkCSVOptionalHeaders()
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
            'Internal notes(optional): Description,Error Code,Error Description';

        $expectedDataRow = 'Amit,9876543210,amit@razorpay.com,testing,employee,100,Yes,Yes,refund,,,,,';

        $this->assertEquals($expectedHeaderRow, trim($fileContent[0]));

        $this->assertEquals($expectedDataRow, trim($fileContent[1]));
    }

    // Removing header 'Payout Description' which is mandatory. This should return an error.
    public function testCreateBatchPayoutLinkBulkCSVMissingMandatoryHeader()
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
            ],
        ];

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testValidateErrorFileForBatchPayoutLinkBulkCSVInvalidSendSMS()
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
            ],
        ];

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $fileContent = file($response['signed_url']);

        $expectedHeaderRow =  'Name of Contact,Contact Phone Number,Contact Email ID,Payout Description,Contact Type,' .
            'Payout Link Amount,Send Link to Phone Number,Send Link to Mail ID,Payout Purpose,Reference ID(optional),Internal notes(optional): Title,' .
            'Internal notes(optional): Description,Error Code,Error Description';

        $expectedDataRow = 'Amit,9876543210,amit@razorpay.com,testing,employee,100,random,Yes,refund,REFERENCE01,test key,test value,' .
            'BAD_REQUEST_ERROR,The selected send link to phone number is invalid.';

        $this->assertEquals($expectedHeaderRow, trim($fileContent[0]));

        $this->assertEquals($expectedDataRow, trim($fileContent[1]));
    }

    public function testValidateErrorFileForBatchPayoutLinkBulkCSVInvalidAmount()
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
            ],
        ];

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $fileContent = file($response['signed_url']);

        $expectedHeaderRow =  'Name of Contact,Contact Phone Number,Contact Email ID,Payout Description,Contact Type,' .
            'Payout Link Amount,Send Link to Phone Number,Send Link to Mail ID,Payout Purpose,Reference ID(optional),Internal notes(optional): Title,' .
            'Internal notes(optional): Description,Error Code,Error Description';

        $expectedDataRow = 'Amit,9876543210,amit@razorpay.com,testing,employee,ABCD,Yes,Yes,refund,REFERENCE01,test key,test value,' .
            'BAD_REQUEST_ERROR,The payout link amount format is invalid.';

        $this->assertEquals($expectedHeaderRow, trim($fileContent[0]));

        $this->assertEquals($expectedDataRow, trim($fileContent[1]));
    }

    public function testValidateBatchPayoutLinkBulkCSVRandomExtraHeader()
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
                // Below is a random Header picked up from Batch\Headers
                Batch\Header::PRICING_RULE_FEATURE      => 'abc'
            ],
        ];

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testValidateBatchPayoutLinkBulkCSVAmountRupeesFloatWithThreeDecimals()
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
            ],
        ];

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $fileContent = file($response['signed_url']);

        $expectedHeaderRow =  'Name of Contact,Contact Phone Number,Contact Email ID,Payout Description,Contact Type,' .
            'Payout Link Amount,Send Link to Phone Number,Send Link to Mail ID,Payout Purpose,Reference ID(optional),Internal notes(optional): Title,' .
            'Internal notes(optional): Description,Error Code,Error Description';

        $expectedDataRow = 'Amit,9876543210,amit@razorpay.com,testing,employee,100.333,Yes,Yes,refund,REFERENCE01,test key,test value,' .
            'BAD_REQUEST_ERROR,The payout link amount format is invalid.';

        $this->assertEquals($expectedHeaderRow, trim($fileContent[0]));

        $this->assertEquals($expectedDataRow, trim($fileContent[1]));
    }

    public function testValidateBatchPayoutLinkBulkFailForNoNotesTitle()
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
            ],
        ];

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $fileContent = file($response['signed_url']);

        $expectedHeaderRow =  'Name of Contact,Contact Phone Number,Contact Email ID,Payout Description,Contact Type,' .
            'Payout Link Amount,Send Link to Phone Number,Send Link to Mail ID,Payout Purpose,Reference ID(optional),Internal notes(optional): Title,' .
            'Internal notes(optional): Description,Error Code,Error Description';

        $expectedDataRow = 'Amit,9876543210,amit@razorpay.com,testing,employee,100,Yes,Yes,refund,REFERENCE01,,test value,BAD_REQUEST_ERROR,Notes title missing';

        $this->assertEquals($expectedHeaderRow, trim($fileContent[0]));

        $this->assertEquals($expectedDataRow, trim($fileContent[1]));
    }

    public function testValidateBatchPayoutLinkBulkFailForNoNotesDesc()
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
            ],
        ];

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $fileContent = file($response['signed_url']);

        $expectedHeaderRow =  'Name of Contact,Contact Phone Number,Contact Email ID,Payout Description,Contact Type,' .
            'Payout Link Amount,Send Link to Phone Number,Send Link to Mail ID,Payout Purpose,Reference ID(optional),Internal notes(optional): Title,' .
            'Internal notes(optional): Description,Error Code,Error Description';

        $expectedDataRow = 'Amit,9876543210,amit@razorpay.com,testing,employee,100,Yes,Yes,refund,REFERENCE01,test key,,BAD_REQUEST_ERROR,Notes description missing';

        $this->assertEquals($expectedHeaderRow, trim($fileContent[0]));

        $this->assertEquals($expectedDataRow, trim($fileContent[1]));
    }

    public function testValidateBatchPayoutLinkBulkFailForMissingContactNumberForSendSMS()
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
            ],
        ];

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $fileContent = file($response['signed_url']);

        $expectedHeaderRow =  'Name of Contact,Contact Phone Number,Contact Email ID,Payout Description,Contact Type,' .
            'Payout Link Amount,Send Link to Phone Number,Send Link to Mail ID,Payout Purpose,Reference ID(optional),Internal notes(optional): Title,' .
            'Internal notes(optional): Description,Error Code,Error Description';

        $expectedDataRow = 'Amit,,amit@razorpay.com,testing,employee,100,Yes,Yes,refund,REFERENCE01,test key,test value,BAD_REQUEST_ERROR,The contact phone number field is required when send link to phone number is Yes.';

        $this->assertEquals($expectedHeaderRow, trim($fileContent[0]));

        $this->assertEquals($expectedDataRow, trim($fileContent[1]));
    }

    public function testValidateBatchPayoutLinkBulkFailForMissingContactMailForSendEmail()
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
            ],
        ];

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $fileContent = file($response['signed_url']);

        $expectedHeaderRow =  'Name of Contact,Contact Phone Number,Contact Email ID,Payout Description,Contact Type,' .
            'Payout Link Amount,Send Link to Phone Number,Send Link to Mail ID,Payout Purpose,Reference ID(optional),Internal notes(optional): Title,' .
            'Internal notes(optional): Description,Error Code,Error Description';

        $expectedDataRow = 'Amit,9876543210,,testing,employee,100,Yes,Yes,refund,REFERENCE01,test key,test value,BAD_REQUEST_ERROR,The contact email i d field is required when send link to mail i d is Yes.';

        $this->assertEquals($expectedHeaderRow, trim($fileContent[0]));

        $this->assertEquals($expectedDataRow, trim($fileContent[1]));
    }

    public function testValidateBatchPayoutLinkBulkFailForMissingContactNumberAndMail()
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
            ],
        ];

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $fileContent = file($response['signed_url']);

        $expectedHeaderRow =  'Name of Contact,Contact Phone Number,Contact Email ID,Payout Description,Contact Type,' .
            'Payout Link Amount,Send Link to Phone Number,Send Link to Mail ID,Payout Purpose,Reference ID(optional),Internal notes(optional): Title,' .
            'Internal notes(optional): Description,Error Code,Error Description';

        $expectedDataRow = 'Amit,,,testing,employee,100,No,No,refund,REFERENCE01,test key,test value,BAD_REQUEST_ERROR,Both contact number and contact email cannot be empty';

        $this->assertEquals($expectedHeaderRow, trim($fileContent[0]));

        $this->assertEquals($expectedDataRow, trim($fileContent[1]));
    }

}
