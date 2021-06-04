<?php

namespace RZP\Tests\Functional\Batch;

use PhpOffice\PhpSpreadsheet\IOFactory;

use RZP\Models\Admin;
use RZP\Models\Batch;
use RZP\Models\Payout;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;

class PayoutTest extends TestCase
{
    use BatchTestTrait;
    use TestsBusinessBanking;

    protected $merchant;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/PayoutTestData.php';

        parent::setUp();

        $this->ba->privateAuth();

        $this->fixtures->create('contact', ['id' => '1000001contact', 'active' => 1]);

        $this->fixtures->create(
            'fund_account',
            [
                'id'           => '100000000000fa',
                'source_id'    => '1000001contact',
                'source_type'  => 'contact',
                'account_type' => 'bank_account',
                'account_id'   => '1000000lcustba'
            ]);

        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $this->ba->proxyAuth();

        // Below timestamp is of Jan 1, 2010. This shall ensure that all merchants act as new merchants.
        // The above timestamp is what decides if a merchant is supposed to be considered as a new merchant
        // who got onboarded after Bulk Improvements project or an existing bulk merchant.
        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::BULK_PAYOUTS_NEW_MERCHANT_CUTOFF_TIMESTAMP => 1262304000]);

        $this->mockRazorxTreatment();

        $this->merchant = $this->getDbEntityById('merchant', '10000000000000');
    }

    // Upload a CSV file and it gets successfully validated.
    // We shall also add assertions to the `batch/validated` file since that is the file that gets sent to Batch Service
    public function testValidateBatchPayoutsCSV()
    {
        $entries = [
            [
                Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                Batch\Header::PAYOUT_AMOUNT_RUPEES      => 10,
                Batch\Header::PAYOUT_CURRENCY           => 'INR',
                Batch\Header::PAYOUT_MODE               => 'NEFT',
                Batch\Header::PAYOUT_PURPOSE            => 'refund',
                Batch\Header::FUND_ACCOUNT_ID           => '',
                Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account',
                Batch\Header::FUND_ACCOUNT_NAME         => 'Mehul Kaushik',
                Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0010720',
                Batch\Header::FUND_ACCOUNT_NUMBER       => '100200300400',
                Batch\Header::FUND_ACCOUNT_VPA          => '',
                Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '',
                Batch\Header::CONTACT_NAME_2            => 'Mehul Kaushik',
                Batch\Header::PAYOUT_NARRATION          => 'test123',
                Batch\Header::PAYOUT_REFERENCE_ID       => '',
                Batch\Header::FUND_ACCOUNT_EMAIL        => '',
                Batch\Header::CONTACT_TYPE              => 'employee',
                Batch\Header::CONTACT_EMAIL_2           => 'mehul.kaushik@razorpay.com',
                Batch\Header::CONTACT_MOBILE_2          => '',
                Batch\Header::CONTACT_REFERENCE_ID      => '',
                Batch\Header::NOTES_CODE                => 'test',
                Batch\Header::NOTES_PLACE               => 'Bangalore'
            ],
        ];

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertEquals(1000, $response['total_payout_amount']);

        $fileContent = file($response['signed_url']);

        $expectedHeaderRow =  'Error Description,RazorpayX Account Number,Payout Amount (in Rupees),Payout Currency,Payout Mode,'.
            'Payout Purpose,Fund Account Id,Fund Account Type,Fund Account Name,Fund Account Ifsc,Fund Account Number,'.
            'Fund Account Vpa,Fund Account Phone Number,Contact Name,Payout Narration,Payout Reference Id,Fund Account Email,Contact Type,' .
            'Contact Email,Contact Mobile,Contact Reference Id,notes[code],notes[place]';

        $expectedDataRow = ',2323230041626905,10,INR,NEFT,refund,,bank_account,Mehul Kaushik,SBIN0010720,100200300400,,' .
            ',Mehul Kaushik,test123,,,employee,mehul.kaushik@razorpay.com,,,test,Bangalore';

        $this->assertEquals($expectedHeaderRow, trim($fileContent[0]));
        $this->assertEquals($expectedDataRow, trim($fileContent[1]));
    }

    public function testValidateBatchPayoutsCSVForAmazonPayPayout()
    {
        $entries = [
            [
                Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                Batch\Header::PAYOUT_AMOUNT_RUPEES      => 10,
                Batch\Header::PAYOUT_CURRENCY           => 'INR',
                Batch\Header::PAYOUT_MODE               => 'amazonpay',
                Batch\Header::PAYOUT_PURPOSE            => 'refund',
                Batch\Header::FUND_ACCOUNT_ID           => '',
                Batch\Header::FUND_ACCOUNT_TYPE         => 'wallet',
                Batch\Header::FUND_ACCOUNT_NAME         => 'Mehul Kaushik',
                Batch\Header::FUND_ACCOUNT_IFSC         => '',
                Batch\Header::FUND_ACCOUNT_NUMBER       => '',
                Batch\Header::FUND_ACCOUNT_VPA          => '',
                Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '+918124632237',
                Batch\Header::CONTACT_NAME_2            => 'Mehul Kaushik',
                Batch\Header::PAYOUT_NARRATION          => 'test123',
                Batch\Header::PAYOUT_REFERENCE_ID       => '',
                Batch\Header::FUND_ACCOUNT_EMAIL        => 'sample@example.com',
                Batch\Header::CONTACT_TYPE              => 'employee',
                Batch\Header::CONTACT_EMAIL_2           => 'mehul.kaushik@razorpay.com',
                Batch\Header::CONTACT_MOBILE_2          => '',
                Batch\Header::CONTACT_REFERENCE_ID      => '',
                Batch\Header::NOTES_CODE                => 'test',
                Batch\Header::NOTES_PLACE               => 'Bangalore'
            ],
        ];

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertEquals(1000, $response['total_payout_amount']);

        $fileContent = file($response['signed_url']);

        $expectedHeaderRow =  'Error Description,RazorpayX Account Number,Payout Amount (in Rupees),Payout Currency,Payout Mode,'.
            'Payout Purpose,Fund Account Id,Fund Account Type,Fund Account Name,Fund Account Ifsc,Fund Account Number,'.
            'Fund Account Vpa,Fund Account Phone Number,Contact Name,Payout Narration,Payout Reference Id,Fund Account Email,Contact Type,' .
            'Contact Email,Contact Mobile,Contact Reference Id,notes[code],notes[place]';

        $expectedDataRow = ',2323230041626905,10,INR,amazonpay,refund,,wallet,Mehul Kaushik,,,,+918124632237,' .
            'Mehul Kaushik,test123,,sample@example.com,employee,mehul.kaushik@razorpay.com,,,test,Bangalore';

        $this->assertEquals($expectedHeaderRow, trim($fileContent[0]));
        $this->assertEquals($expectedDataRow, trim($fileContent[1]));
    }

    // Upload a XLSX file and it gets successfully validated.
    // We shall also add assertions to the `batch/validated` file since that is the file that gets sent to Batch Service
    public function testValidateBatchPayoutsXLSX()
    {
        $entries = [
            //
            // Have to keep the keys with Placeholders because in actual file,
            // the cells are merged but this is how the file is read by the FileUploaderTrait.
            //
            [
                'Mandatory Fields'             => Batch\Header::RAZORPAYX_ACCOUNT_NUMBER,
                'Placeholder 1'                => Batch\Header::PAYOUT_AMOUNT_RUPEES,
                'Placeholder 2'                => Batch\Header::PAYOUT_CURRENCY,
                'Placeholder 3'                => Batch\Header::PAYOUT_MODE,
                'Placeholder 4'                => Batch\Header::PAYOUT_PURPOSE,
                'Partially optional fields'    => Batch\Header::FUND_ACCOUNT_ID,
                'Placeholder 5'                => Batch\Header::FUND_ACCOUNT_TYPE,
                'Placeholder 6'                => Batch\Header::FUND_ACCOUNT_NAME,
                'Placeholder 7'                => Batch\Header::FUND_ACCOUNT_IFSC,
                'Placeholder 8'                => Batch\Header::FUND_ACCOUNT_NUMBER,
                'Placeholder 9'                => Batch\Header::FUND_ACCOUNT_VPA,
                'Placeholder 10'               => Batch\Header::FUND_ACCOUNT_PHONE_NUMBER,
                'Optional'                     => Batch\Header::CONTACT_NAME_2,
                'Placeholder 11'               => Batch\Header::PAYOUT_NARRATION,
                'Placeholder 12'               => Batch\Header::PAYOUT_REFERENCE_ID,
                'Placeholder 13'               => Batch\Header::FUND_ACCOUNT_EMAIL,
                'Placeholder 14'               => Batch\Header::CONTACT_TYPE,
                'Placeholder 15'               => Batch\Header::CONTACT_EMAIL_2,
                'Placeholder 16'               => Batch\Header::CONTACT_MOBILE_2,
                'Placeholder 17'               => Batch\Header::CONTACT_REFERENCE_ID,
                'Placeholder 18'               => Batch\Header::NOTES_CODE,
                'Placeholder 19'               => Batch\Header::NOTES_PLACE,
            ],
            [
                Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                Batch\Header::PAYOUT_AMOUNT_RUPEES      => 10.23,
                Batch\Header::PAYOUT_CURRENCY           => 'INR',
                Batch\Header::PAYOUT_MODE               => 'NEFT',
                Batch\Header::PAYOUT_PURPOSE            => 'refund',
                Batch\Header::FUND_ACCOUNT_ID           => '',
                Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account',
                Batch\Header::FUND_ACCOUNT_NAME         => 'Mehul Kaushik',
                Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0010720',
                Batch\Header::FUND_ACCOUNT_NUMBER       => '100200300400',
                Batch\Header::FUND_ACCOUNT_VPA          => '',
                Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '',
                Batch\Header::CONTACT_NAME_2            => 'Mehul Kaushik',
                Batch\Header::PAYOUT_NARRATION          => 'test123',
                Batch\Header::PAYOUT_REFERENCE_ID       => '',
                Batch\Header::FUND_ACCOUNT_EMAIL        => '',
                Batch\Header::CONTACT_TYPE              => 'employee',
                Batch\Header::CONTACT_EMAIL_2           => 'mehul.kaushik@razorpay.com',
                Batch\Header::CONTACT_MOBILE_2          => '',
                Batch\Header::CONTACT_REFERENCE_ID      => '',
                Batch\Header::NOTES_CODE                => 'test',
                Batch\Header::NOTES_PLACE               => 'test',
            ],
        ];

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertEquals(1023, $response['total_payout_amount']);

        $spreadsheet = IOFactory::load($response['signed_url']);

        $activeSheet = $spreadsheet->getActiveSheet();

        $this->assertEquals(2, $activeSheet->getHighestRow());

        $expectedData = [
            [
                'Error Description',
                'RazorpayX Account Number',
                'Payout Amount (in Rupees)',
                'Payout Currency',
                'Payout Mode',
                'Payout Purpose',
                'Fund Account Id',
                'Fund Account Type',
                'Fund Account Name',
                'Fund Account Ifsc',
                'Fund Account Number',
                'Fund Account Vpa',
                'Fund Account Phone Number',
                'Contact Name',
                'Payout Narration',
                'Payout Reference Id',
                'Fund Account Email',
                'Contact Type',
                'Contact Email',
                'Contact Mobile',
                'Contact Reference Id',
                'notes[code]',
                'notes[place]',
            ],
            [
                null,
                // NOTE : Account number is set as a merchant's banking balance's account number : 2224440041626905
                2323230041626905,
                10.23,
                'INR',
                'NEFT',
                'refund',
                null,
                'bank_account',
                'Mehul Kaushik',
                'SBIN0010720',
                100200300400,
                null,
                null,
                'Mehul Kaushik',
                'test123',
                null,
                null,
                'employee',
                'mehul.kaushik@razorpay.com',
                null,
                null,
                'test',
                'test',
            ],
        ];

        for ($row = 1; $row <= 2; $row++)
        {
            for ($col = 1; $col <= 23; $col++)
            {
                try
                {
                    $cellValue = $activeSheet->getCellByColumnAndRow($col, $row, false)->getValue();
                }
                catch (\Throwable $ex)
                {
                    $cellValue = null;
                }

                $this->assertEquals($expectedData[$row-1][$col-1], $cellValue);
            }
        }
    }

    // Upload a XLSX file and it gets successfully validated.
    // We shall also add assertions to the `batch/validated` file since that is the file that gets sent to Batch Service
    public function testValidateBatchPayoutsXLSXForAmazonPayPayout()
    {
        $entries = [
            //
            // Have to keep the keys with Placeholders because in actual file,
            // the cells are merged but this is how the file is read by the FileUploaderTrait.
            //
            [
                'Mandatory Fields'             => Batch\Header::RAZORPAYX_ACCOUNT_NUMBER,
                'Placeholder 1'                => Batch\Header::PAYOUT_AMOUNT_RUPEES,
                'Placeholder 2'                => Batch\Header::PAYOUT_CURRENCY,
                'Placeholder 3'                => Batch\Header::PAYOUT_MODE,
                'Placeholder 4'                => Batch\Header::PAYOUT_PURPOSE,
                'Partially optional fields'    => Batch\Header::FUND_ACCOUNT_ID,
                'Placeholder 5'                => Batch\Header::FUND_ACCOUNT_TYPE,
                'Placeholder 6'                => Batch\Header::FUND_ACCOUNT_NAME,
                'Placeholder 7'                => Batch\Header::FUND_ACCOUNT_IFSC,
                'Placeholder 8'                => Batch\Header::FUND_ACCOUNT_NUMBER,
                'Placeholder 9'                => Batch\Header::FUND_ACCOUNT_VPA,
                'Placeholder 10'               => Batch\Header::FUND_ACCOUNT_PHONE_NUMBER,
                'Optional'                     => Batch\Header::CONTACT_NAME_2,
                'Placeholder 11'               => Batch\Header::PAYOUT_NARRATION,
                'Placeholder 12'               => Batch\Header::PAYOUT_REFERENCE_ID,
                'Placeholder 13'               => Batch\Header::FUND_ACCOUNT_EMAIL,
                'Placeholder 14'               => Batch\Header::CONTACT_TYPE,
                'Placeholder 15'               => Batch\Header::CONTACT_EMAIL_2,
                'Placeholder 16'               => Batch\Header::CONTACT_MOBILE_2,
                'Placeholder 17'               => Batch\Header::CONTACT_REFERENCE_ID,
                'Placeholder 18'               => Batch\Header::NOTES_CODE,
                'Placeholder 19'               => Batch\Header::NOTES_PLACE,
            ],
            [
                Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                Batch\Header::PAYOUT_AMOUNT_RUPEES      => 10.23,
                Batch\Header::PAYOUT_CURRENCY           => 'INR',
                Batch\Header::PAYOUT_MODE               => 'amazonpay',
                Batch\Header::PAYOUT_PURPOSE            => 'refund',
                Batch\Header::FUND_ACCOUNT_ID           => '',
                Batch\Header::FUND_ACCOUNT_TYPE         => 'wallet',
                Batch\Header::FUND_ACCOUNT_NAME         => 'Mehul Kaushik',
                Batch\Header::FUND_ACCOUNT_IFSC         => '',
                Batch\Header::FUND_ACCOUNT_NUMBER       => '',
                Batch\Header::FUND_ACCOUNT_VPA          => '',
                Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '+918124632237',
                Batch\Header::CONTACT_NAME_2            => 'Mehul Kaushik',
                Batch\Header::PAYOUT_NARRATION          => 'test123',
                Batch\Header::PAYOUT_REFERENCE_ID       => '',
                Batch\Header::FUND_ACCOUNT_EMAIL        => '',
                Batch\Header::CONTACT_TYPE              => 'employee',
                Batch\Header::CONTACT_EMAIL_2           => 'mehul.kaushik@razorpay.com',
                Batch\Header::CONTACT_MOBILE_2          => '',
                Batch\Header::CONTACT_REFERENCE_ID      => '',
                Batch\Header::NOTES_CODE                => 'test',
                Batch\Header::NOTES_PLACE               => 'test',
            ],
        ];

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertEquals(1023, $response['total_payout_amount']);

        $spreadsheet = IOFactory::load($response['signed_url']);

        $activeSheet = $spreadsheet->getActiveSheet();

        $this->assertEquals(2, $activeSheet->getHighestRow());

        $expectedData = [
            [
                'Error Description',
                'RazorpayX Account Number',
                'Payout Amount (in Rupees)',
                'Payout Currency',
                'Payout Mode',
                'Payout Purpose',
                'Fund Account Id',
                'Fund Account Type',
                'Fund Account Name',
                'Fund Account Ifsc',
                'Fund Account Number',
                'Fund Account Vpa',
                'Fund Account Phone Number',
                'Contact Name',
                'Payout Narration',
                'Payout Reference Id',
                'Fund Account Email',
                'Contact Type',
                'Contact Email',
                'Contact Mobile',
                'Contact Reference Id',
                'notes[code]',
                'notes[place]',
            ],
            [
                null,
                // NOTE : Account number is set as a merchant's banking balance's account number : 2224440041626905
                2323230041626905,
                10.23,
                'INR',
                'amazonpay',
                'refund',
                null,
                'wallet',
                'Mehul Kaushik',
                null,
                null,
                null,
                '+918124632237',
                'Mehul Kaushik',
                'test123',
                null,
                null,
                'employee',
                'mehul.kaushik@razorpay.com',
                null,
                null,
                'test',
                'test',
            ]
        ];

        for ($row = 1; $row <= 2; $row++)
        {
            for ($col = 1; $col <= 23; $col++)
            {
                try
                {
                    $cellValue = $activeSheet->getCellByColumnAndRow($col, $row, false)->getValue();
                }
                catch (\Throwable $ex)
                {
                    $cellValue = null;
                }

                $this->assertEquals($expectedData[$row-1][$col-1], $cellValue);
            }
        }
    }

    // Validation should go through even when some optional headers are missing
    public function testValidateBatchPayoutsXLSXOptionalHeaders()
    {
        $entries = [
            //
            // Have to keep the keys with Placeholders because in actual file,
            // the cells are merged but this is how the file is read by the FileUploaderTrait.
            //
            [
                'Mandatory Fields'             => Batch\Header::RAZORPAYX_ACCOUNT_NUMBER,
                'Placeholder 1'                => Batch\Header::PAYOUT_AMOUNT_RUPEES,
                'Placeholder 2'                => Batch\Header::PAYOUT_CURRENCY,
                'Placeholder 3'                => Batch\Header::PAYOUT_MODE,
                'Placeholder 4'                => Batch\Header::PAYOUT_PURPOSE,
                'Partially optional fields'    => Batch\Header::FUND_ACCOUNT_ID,
                'Placeholder 5'                => Batch\Header::FUND_ACCOUNT_TYPE,
                'Placeholder 6'                => Batch\Header::FUND_ACCOUNT_NAME,
                'Placeholder 7'                => Batch\Header::FUND_ACCOUNT_IFSC,
                'Placeholder 8'                => Batch\Header::FUND_ACCOUNT_NUMBER,
                'Placeholder 9'                => Batch\Header::FUND_ACCOUNT_VPA,
                'Placeholder 10'               => Batch\Header::FUND_ACCOUNT_PHONE_NUMBER,
                'Placeholder 11'               => Batch\Header::CONTACT_NAME_2,
            ],
            [
                Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                Batch\Header::PAYOUT_AMOUNT_RUPEES      => 10,
                Batch\Header::PAYOUT_CURRENCY           => 'INR',
                Batch\Header::PAYOUT_MODE               => 'NEFT',
                Batch\Header::PAYOUT_PURPOSE            => 'refund',
                Batch\Header::FUND_ACCOUNT_ID           => '',
                Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account',
                Batch\Header::FUND_ACCOUNT_NAME         => 'Mehul Kaushik',
                Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0010720',
                Batch\Header::FUND_ACCOUNT_NUMBER       => '100200300400',
                Batch\Header::FUND_ACCOUNT_VPA          => '',
                Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '',
                Batch\Header::CONTACT_NAME_2            => 'Mehul Kaushik',
            ],
        ];

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $spreadsheet = IOFactory::load($response['signed_url']);

        $activeSheet = $spreadsheet->getActiveSheet();

        $this->assertEquals(2, $activeSheet->getHighestRow());

        $expectedData = [
            [
                'Error Description',
                'RazorpayX Account Number',
                'Payout Amount (in Rupees)',
                'Payout Currency',
                'Payout Mode',
                'Payout Purpose',
                'Fund Account Id',
                'Fund Account Type',
                'Fund Account Name',
                'Fund Account Ifsc',
                'Fund Account Number',
                'Fund Account Vpa',
                'Fund Account Phone Number',
                'Contact Name',
            ],
            [
                null,
                // NOTE : Account number is set as a merchant's banking balance's account number : 2224440041626905
                2323230041626905,
                10,
                'INR',
                'NEFT',
                'refund',
                null,
                'bank_account',
                'Mehul Kaushik',
                'SBIN0010720',
                100200300400,
                null,
                null,
                'Mehul Kaushik',
            ],
        ];

        for ($row = 1; $row <= 2; $row++)
        {
            for ($col = 1; $col <= 14; $col++)
            {
                try
                {
                    $cellValue = $activeSheet->getCellByColumnAndRow($col, $row, false)->getValue();
                }
                catch (\Throwable $ex)
                {
                    $cellValue = null;
                }

                $this->assertEquals($expectedData[$row-1][$col-1], $cellValue);
            }
        }
    }

    // Validation should pass even if amazon pay headers are not passed
    public function testValidateBatchPayoutsXLSXWithoutAmazonPayHeaders()
    {
        $entries = [
            //
            // Have to keep the keys with Placeholders because in actual file,
            // the cells are merged but this is how the file is read by the FileUploaderTrait.
            //
            [
                'Mandatory Fields'             => Batch\Header::RAZORPAYX_ACCOUNT_NUMBER,
                'Placeholder 1'                => Batch\Header::PAYOUT_AMOUNT_RUPEES,
                'Placeholder 2'                => Batch\Header::PAYOUT_CURRENCY,
                'Placeholder 3'                => Batch\Header::PAYOUT_MODE,
                'Placeholder 4'                => Batch\Header::PAYOUT_PURPOSE,
                'Partially optional fields'    => Batch\Header::FUND_ACCOUNT_ID,
                'Placeholder 5'                => Batch\Header::FUND_ACCOUNT_TYPE,
                'Placeholder 6'                => Batch\Header::FUND_ACCOUNT_NAME,
                'Placeholder 7'                => Batch\Header::FUND_ACCOUNT_IFSC,
                'Placeholder 8'                => Batch\Header::FUND_ACCOUNT_NUMBER,
                'Placeholder 9'                => Batch\Header::FUND_ACCOUNT_VPA,
                'Optional'                     => Batch\Header::CONTACT_NAME_2,
                'Placeholder 10'               => Batch\Header::PAYOUT_NARRATION,
                'Placeholder 11'               => Batch\Header::PAYOUT_REFERENCE_ID,
                'Placeholder 12'               => Batch\Header::CONTACT_TYPE,
                'Placeholder 13'               => Batch\Header::CONTACT_EMAIL_2,
                'Placeholder 14'               => Batch\Header::CONTACT_MOBILE_2,
                'Placeholder 15'               => Batch\Header::CONTACT_REFERENCE_ID,
                'Placeholder 16'               => Batch\Header::NOTES_CODE,
                'Placeholder 17'               => Batch\Header::NOTES_PLACE,
            ],
            [
                Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                Batch\Header::PAYOUT_AMOUNT_RUPEES      => 10.23,
                Batch\Header::PAYOUT_CURRENCY           => 'INR',
                Batch\Header::PAYOUT_MODE               => 'NEFT',
                Batch\Header::PAYOUT_PURPOSE            => 'refund',
                Batch\Header::FUND_ACCOUNT_ID           => '',
                Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account',
                Batch\Header::FUND_ACCOUNT_NAME         => 'Mehul Kaushik',
                Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0010720',
                Batch\Header::FUND_ACCOUNT_NUMBER       => '100200300400',
                Batch\Header::FUND_ACCOUNT_VPA          => '',
                Batch\Header::CONTACT_NAME_2            => 'Mehul Kaushik',
                Batch\Header::PAYOUT_NARRATION          => 'test123',
                Batch\Header::PAYOUT_REFERENCE_ID       => '',
                Batch\Header::CONTACT_TYPE              => 'employee',
                Batch\Header::CONTACT_EMAIL_2           => 'mehul.kaushik@razorpay.com',
                Batch\Header::CONTACT_MOBILE_2          => '',
                Batch\Header::CONTACT_REFERENCE_ID      => '',
                Batch\Header::NOTES_CODE                => 'test',
                Batch\Header::NOTES_PLACE               => 'test',
            ],
        ];

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertEquals(1023, $response['total_payout_amount']);

        $spreadsheet = IOFactory::load($response['signed_url']);

        $activeSheet = $spreadsheet->getActiveSheet();

        $this->assertEquals(2, $activeSheet->getHighestRow());

        $expectedData = [
            [
                'Error Description',
                'RazorpayX Account Number',
                'Payout Amount (in Rupees)',
                'Payout Currency',
                'Payout Mode',
                'Payout Purpose',
                'Fund Account Id',
                'Fund Account Type',
                'Fund Account Name',
                'Fund Account Ifsc',
                'Fund Account Number',
                'Fund Account Vpa',
                'Contact Name',
                'Payout Narration',
                'Payout Reference Id',
                'Contact Type',
                'Contact Email',
                'Contact Mobile',
                'Contact Reference Id',
                'notes[code]',
                'notes[place]',
            ],
            [
                null,
                // NOTE : Account number is set as a merchant's banking balance's account number : 2224440041626905
                2323230041626905,
                10.23,
                'INR',
                'NEFT',
                'refund',
                null,
                'bank_account',
                'Mehul Kaushik',
                'SBIN0010720',
                100200300400,
                null,
                'Mehul Kaushik',
                'test123',
                null,
                'employee',
                'mehul.kaushik@razorpay.com',
                null,
                null,
                'test',
                'test',
            ],
        ];

        for ($row = 1; $row <= 2; $row++)
        {
            for ($col = 1; $col <= 21; $col++)
            {
                try
                {
                    $cellValue = $activeSheet->getCellByColumnAndRow($col, $row, false)->getValue();
                }
                catch (\Throwable $ex)
                {
                    $cellValue = null;
                }

                $this->assertEquals($expectedData[$row-1][$col-1], $cellValue);
            }
        }
    }

    // Validation should go through even when some optional headers are missing
    public function testValidateBatchPayoutsCSVOptionalHeaders()
    {
        $entries = [
            [
                Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                Batch\Header::PAYOUT_AMOUNT_RUPEES      => 10,
                Batch\Header::PAYOUT_CURRENCY           => 'INR',
                Batch\Header::PAYOUT_MODE               => 'NEFT',
                Batch\Header::PAYOUT_PURPOSE            => 'refund',
                Batch\Header::FUND_ACCOUNT_ID           => '',
                Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account',
                Batch\Header::FUND_ACCOUNT_NAME         => 'Mehul Kaushik',
                Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0010720',
                Batch\Header::FUND_ACCOUNT_NUMBER       => '100200300400',
                Batch\Header::FUND_ACCOUNT_VPA          => '',
                Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '',
                Batch\Header::CONTACT_NAME_2            => 'Mehul Kaushik',
            ],
        ];

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $fileContent = file($response['signed_url']);

        $expectedHeaderRow =  'Error Description,RazorpayX Account Number,Payout Amount (in Rupees),Payout Currency,Payout Mode,'.
            'Payout Purpose,Fund Account Id,Fund Account Type,Fund Account Name,Fund Account Ifsc,Fund Account Number,'.
            'Fund Account Vpa,Fund Account Phone Number,Contact Name';

        $expectedDataRow = ',2323230041626905,10,INR,NEFT,refund,,bank_account,Mehul Kaushik,SBIN0010720,100200300400,,' .
                            ',Mehul Kaushik';

        $this->assertEquals($expectedHeaderRow, trim($fileContent[0]));
        $this->assertEquals($expectedDataRow, trim($fileContent[1]));
    }

    // Validation should go through even without amazonpay headers
    public function testValidateBatchPayoutsCSVWithoutAmazonPayHeaders()
    {
        $entries = [
            [
                Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                Batch\Header::PAYOUT_AMOUNT_RUPEES      => 10,
                Batch\Header::PAYOUT_CURRENCY           => 'INR',
                Batch\Header::PAYOUT_MODE               => 'NEFT',
                Batch\Header::PAYOUT_PURPOSE            => 'refund',
                Batch\Header::FUND_ACCOUNT_ID           => '',
                Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account',
                Batch\Header::FUND_ACCOUNT_NAME         => 'Mehul Kaushik',
                Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0010720',
                Batch\Header::FUND_ACCOUNT_NUMBER       => '100200300400',
                Batch\Header::FUND_ACCOUNT_VPA          => '',
                Batch\Header::CONTACT_NAME_2            => 'Mehul Kaushik',
                Batch\Header::PAYOUT_NARRATION          => 'test123',
                Batch\Header::PAYOUT_REFERENCE_ID       => '',
                Batch\Header::CONTACT_TYPE              => 'employee',
                Batch\Header::CONTACT_EMAIL_2           => 'mehul.kaushik@razorpay.com',
                Batch\Header::CONTACT_MOBILE_2          => '',
                Batch\Header::CONTACT_REFERENCE_ID      => '',
                Batch\Header::NOTES_CODE                => 'test',
                Batch\Header::NOTES_PLACE               => 'Bangalore'
            ],
        ];

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertEquals(1000, $response['total_payout_amount']);

        $fileContent = file($response['signed_url']);

        $expectedHeaderRow =  'Error Description,RazorpayX Account Number,Payout Amount (in Rupees),Payout Currency,Payout Mode,'.
            'Payout Purpose,Fund Account Id,Fund Account Type,Fund Account Name,Fund Account Ifsc,Fund Account Number,'.
            'Fund Account Vpa,Contact Name,Payout Narration,Payout Reference Id,Contact Type,' .
            'Contact Email,Contact Mobile,Contact Reference Id,notes[code],notes[place]';

        $expectedDataRow = ',2323230041626905,10,INR,NEFT,refund,,bank_account,Mehul Kaushik,SBIN0010720,100200300400,' .
            ',Mehul Kaushik,test123,,employee,mehul.kaushik@razorpay.com,,,test,Bangalore';

        $this->assertEquals($expectedHeaderRow, trim($fileContent[0]));
        $this->assertEquals($expectedDataRow, trim($fileContent[1]));
    }

    // Removing header 'Payout Mode' which is mandatory. This should return an error.
    public function testValidateBatchPayoutsXLSXMissingMandatoryHeader()
    {
        $entries = [
            //
            // Have to keep the keys with Placeholders because in actual file,
            // the cells are merged but this is how the file is read by the FileUploaderTrait.
            //
            [
                'Mandatory Fields'             => Batch\Header::RAZORPAYX_ACCOUNT_NUMBER,
                'Placeholder 1'                => Batch\Header::PAYOUT_AMOUNT_RUPEES,
                'Placeholder 2'                => Batch\Header::PAYOUT_CURRENCY,
                'Partially optional fields'    => Batch\Header::PAYOUT_PURPOSE,
                'Placeholder 4'                => Batch\Header::FUND_ACCOUNT_ID,
                'Placeholder 5'                => Batch\Header::FUND_ACCOUNT_TYPE,
                'Placeholder 6'                => Batch\Header::FUND_ACCOUNT_NAME,
                'Placeholder 7'                => Batch\Header::FUND_ACCOUNT_IFSC,
                'Placeholder 8'                => Batch\Header::FUND_ACCOUNT_PHONE_NUMBER,
                'Optional'                     => Batch\Header::FUND_ACCOUNT_NUMBER,
                'Placeholder 9'                => Batch\Header::FUND_ACCOUNT_VPA,
                'Placeholder 10'               => Batch\Header::CONTACT_TYPE,
                'Placeholder 11'               => Batch\Header::CONTACT_NAME_2,
            ],
            [
                Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                Batch\Header::PAYOUT_AMOUNT_RUPEES      => 10,
                Batch\Header::PAYOUT_CURRENCY           => 'INR',
                Batch\Header::PAYOUT_PURPOSE            => 'refund',
                Batch\Header::FUND_ACCOUNT_ID           => '',
                Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account',
                Batch\Header::FUND_ACCOUNT_NAME         => 'Mehul Kaushik',
                Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0010720',
                Batch\Header::FUND_ACCOUNT_NUMBER       => '100200300400',
                Batch\Header::FUND_ACCOUNT_VPA          => '',
                Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '',
                Batch\Header::CONTACT_TYPE              => 'employee',
                Batch\Header::CONTACT_NAME_2            => 'Mehul Kaushik',
            ],
        ];

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    // Removing header 'Payout Mode' which is mandatory. This should return an error.
    public function testCreateBatchPayoutsCSVMissingMandatoryHeader()
    {
        $entries = [
            [
                Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                Batch\Header::PAYOUT_AMOUNT_RUPEES      => 10,
                Batch\Header::PAYOUT_CURRENCY           => 'INR',
                Batch\Header::PAYOUT_PURPOSE            => 'refund',
                Batch\Header::FUND_ACCOUNT_ID           => '',
                Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account',
                Batch\Header::FUND_ACCOUNT_NAME         => 'Mehul Kaushik',
                Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0010720',
                Batch\Header::FUND_ACCOUNT_NUMBER       => '100200300400',
                Batch\Header::FUND_ACCOUNT_VPA          => '',
                Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '',
                Batch\Header::CONTACT_TYPE              => 'employee',
                Batch\Header::CONTACT_NAME_2            => 'Mehul Kaushik',
            ],
        ];

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    // We don't need to make any change to consider this user as a new user since we have set this in redis
    public function testValidateBatchPayoutsCSVAmountInPaiseForNewUser()
    {
        $entries = [
            [
                Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                Batch\Header::PAYOUT_AMOUNT             => 1000,
                Batch\Header::PAYOUT_CURRENCY           => 'INR',
                Batch\Header::PAYOUT_MODE               => 'NEFT',
                Batch\Header::PAYOUT_PURPOSE            => 'refund',
                Batch\Header::PAYOUT_NARRATION          => 'test123',
                Batch\Header::PAYOUT_REFERENCE_ID       => '',
                Batch\Header::FUND_ACCOUNT_ID           => '',
                Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account',
                Batch\Header::FUND_ACCOUNT_NAME         => 'Mehul Kaushik',
                Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0010720',
                Batch\Header::FUND_ACCOUNT_NUMBER       => '100200300400',
                Batch\Header::FUND_ACCOUNT_VPA          => '',
                Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '',
                Batch\Header::FUND_ACCOUNT_EMAIL        => '',
                Batch\Header::CONTACT_TYPE              => 'employee',
                Batch\Header::CONTACT_NAME_2            => 'Mehul Kaushik',
                Batch\Header::CONTACT_EMAIL_2           => 'mehul.kaushik@razorpay.com',
                Batch\Header::CONTACT_MOBILE_2          => '',
                Batch\Header::CONTACT_REFERENCE_ID      => '',
            ],
        ];

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    // We don't need to make any change to consider this user as a new user since we have set this in redis
    public function testValidateBatchPayoutsXLSXAmountInPaiseForNewUser()
    {
        $entries = [
            //
            // Have to keep the keys with Placeholders because in actual file,
            // the cells are merged but this is how the file is read by the FileUploaderTrait.
            //
            [
                'Mandatory Fields'             => Batch\Header::RAZORPAYX_ACCOUNT_NUMBER,
                'Placeholder 1'                => Batch\Header::PAYOUT_AMOUNT,
                'Placeholder 2'                => Batch\Header::PAYOUT_CURRENCY,
                'Placeholder 3'                => Batch\Header::PAYOUT_MODE,
                'Placeholder 4'                => Batch\Header::PAYOUT_PURPOSE,
                'Partially optional fields'    => Batch\Header::FUND_ACCOUNT_ID,
                'Placeholder 5'                => Batch\Header::FUND_ACCOUNT_TYPE,
                'Placeholder 6'                => Batch\Header::FUND_ACCOUNT_NAME,
                'Placeholder 7'                => Batch\Header::FUND_ACCOUNT_IFSC,
                'Placeholder 8'                => Batch\Header::FUND_ACCOUNT_NUMBER,
                'Placeholder 9'                => Batch\Header::FUND_ACCOUNT_VPA,
                'Placeholder 10'               => Batch\Header::FUND_ACCOUNT_PHONE_NUMBER,
                'Optional'                     => Batch\Header::CONTACT_NAME_2,
                'Placeholder 11'               => Batch\Header::PAYOUT_NARRATION,
                'Placeholder 12'               => Batch\Header::PAYOUT_REFERENCE_ID,
                'Placeholder 13'               => Batch\Header::FUND_ACCOUNT_EMAIL,
                'Placeholder 14'               => Batch\Header::CONTACT_TYPE,
                'Placeholder 15'               => Batch\Header::CONTACT_EMAIL_2,
                'Placeholder 16'               => Batch\Header::CONTACT_MOBILE_2,
                'Placeholder 17'               => Batch\Header::CONTACT_REFERENCE_ID,
                'Placeholder 18'               => Batch\Header::NOTES_CODE,
                'Placeholder 19'               => Batch\Header::NOTES_PLACE,
            ],
            [
                Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                Batch\Header::PAYOUT_AMOUNT             => 1000,
                Batch\Header::PAYOUT_CURRENCY           => 'INR',
                Batch\Header::PAYOUT_MODE               => 'NEFT',
                Batch\Header::PAYOUT_PURPOSE            => 'refund',
                Batch\Header::FUND_ACCOUNT_ID           => '',
                Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account',
                Batch\Header::FUND_ACCOUNT_NAME         => 'Mehul Kaushik',
                Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0010720',
                Batch\Header::FUND_ACCOUNT_NUMBER       => '100200300400',
                Batch\Header::FUND_ACCOUNT_VPA          => '',
                Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '',
                Batch\Header::CONTACT_NAME_2            => 'Mehul Kaushik',
                Batch\Header::PAYOUT_NARRATION          => 'test123',
                Batch\Header::PAYOUT_REFERENCE_ID       => '',
                Batch\Header::FUND_ACCOUNT_EMAIL        => '',
                Batch\Header::CONTACT_TYPE              => 'employee',
                Batch\Header::CONTACT_EMAIL_2           => 'mehul.kaushik@razorpay.com',
                Batch\Header::CONTACT_MOBILE_2          => '',
                Batch\Header::CONTACT_REFERENCE_ID      => '',
                Batch\Header::NOTES_CODE                => 'test',
                Batch\Header::NOTES_PLACE               => 'test',
            ],
        ];

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testValidateBatchPayoutsXLSXAmountInRupeesForExistingBulkPaiseUser()
    {
        $this->changeMerchantToExistingBulkPaiseType();

        $entries = [
            //
            // Have to keep the keys with Placeholders because in actual file,
            // the cells are merged but this is how the file is read by the FileUploaderTrait.
            //
            [
                'Mandatory Fields'             => Batch\Header::RAZORPAYX_ACCOUNT_NUMBER,
                'Placeholder 1'                => Batch\Header::PAYOUT_AMOUNT_RUPEES,
                'Placeholder 2'                => Batch\Header::PAYOUT_CURRENCY,
                'Placeholder 3'                => Batch\Header::PAYOUT_MODE,
                'Placeholder 4'                => Batch\Header::PAYOUT_PURPOSE,
                'Partially optional fields'    => Batch\Header::FUND_ACCOUNT_ID,
                'Placeholder 5'                => Batch\Header::FUND_ACCOUNT_TYPE,
                'Placeholder 6'                => Batch\Header::FUND_ACCOUNT_NAME,
                'Placeholder 7'                => Batch\Header::FUND_ACCOUNT_IFSC,
                'Placeholder 8'                => Batch\Header::FUND_ACCOUNT_NUMBER,
                'Placeholder 9'                => Batch\Header::FUND_ACCOUNT_VPA,
                'Placeholder 10'               => Batch\Header::FUND_ACCOUNT_PHONE_NUMBER,
                'Optional'                     => Batch\Header::CONTACT_NAME_2,
                'Placeholder 11'               => Batch\Header::PAYOUT_NARRATION,
                'Placeholder 12'               => Batch\Header::PAYOUT_REFERENCE_ID,
                'Placeholder 13'               => Batch\Header::FUND_ACCOUNT_EMAIL,
                'Placeholder 14'               => Batch\Header::CONTACT_TYPE,
                'Placeholder 15'               => Batch\Header::CONTACT_EMAIL_2,
                'Placeholder 16'               => Batch\Header::CONTACT_MOBILE_2,
                'Placeholder 17'               => Batch\Header::CONTACT_REFERENCE_ID,
                'Placeholder 18'               => Batch\Header::NOTES_CODE,
                'Placeholder 19'               => Batch\Header::NOTES_PLACE,
            ],
            [
                Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                Batch\Header::PAYOUT_AMOUNT_RUPEES      => 10,
                Batch\Header::PAYOUT_CURRENCY           => 'INR',
                Batch\Header::PAYOUT_MODE               => 'NEFT',
                Batch\Header::PAYOUT_PURPOSE            => 'refund',
                Batch\Header::FUND_ACCOUNT_ID           => '',
                Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account',
                Batch\Header::FUND_ACCOUNT_NAME         => 'Mehul Kaushik',
                Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0010720',
                Batch\Header::FUND_ACCOUNT_NUMBER       => '100200300400',
                Batch\Header::FUND_ACCOUNT_VPA          => '',
                Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '',
                Batch\Header::CONTACT_NAME_2            => 'Mehul Kaushik',
                Batch\Header::PAYOUT_NARRATION          => 'test123',
                Batch\Header::PAYOUT_REFERENCE_ID       => '',
                Batch\Header::FUND_ACCOUNT_EMAIL        => '',
                Batch\Header::CONTACT_TYPE              => 'employee',
                Batch\Header::CONTACT_EMAIL_2           => 'mehul.kaushik@razorpay.com',
                Batch\Header::CONTACT_MOBILE_2          => '',
                Batch\Header::CONTACT_REFERENCE_ID      => '',
                Batch\Header::NOTES_CODE                => 'test',
                Batch\Header::NOTES_PLACE               => 'test',
            ],
        ];

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testValidateBatchPayoutsCSVAmountInPaiseForExistingBulkPaiseUser()
    {
        $this->changeMerchantToExistingBulkPaiseType();

        $entries = [
            [
                Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                Batch\Header::PAYOUT_AMOUNT             => 1000,
                Batch\Header::PAYOUT_CURRENCY           => 'INR',
                Batch\Header::PAYOUT_MODE               => 'NEFT',
                Batch\Header::PAYOUT_PURPOSE            => 'refund',
                Batch\Header::PAYOUT_NARRATION          => 'test123',
                Batch\Header::PAYOUT_REFERENCE_ID       => '',
                Batch\Header::FUND_ACCOUNT_ID           => '',
                Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account',
                Batch\Header::FUND_ACCOUNT_NAME         => 'Mehul Kaushik',
                Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0010720',
                Batch\Header::FUND_ACCOUNT_NUMBER       => '100200300400',
                Batch\Header::FUND_ACCOUNT_VPA          => '',
                Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '',
                Batch\Header::FUND_ACCOUNT_EMAIL        => '',
                Batch\Header::CONTACT_TYPE              => 'employee',
                Batch\Header::CONTACT_NAME_2            => 'Mehul Kaushik',
                Batch\Header::CONTACT_EMAIL_2           => 'mehul.kaushik@razorpay.com',
                Batch\Header::CONTACT_MOBILE_2          => '',
                Batch\Header::CONTACT_REFERENCE_ID      => '',
            ],
        ];

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertEquals(1000, $response['total_payout_amount']);
    }

    public function testValidateBatchPayoutsCSVAmountInPaiseForExistingBulkRupeesUser()
    {
        $this->changeMerchantToExistingBulkRupeesType();

        $entries = [
            [
                Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                Batch\Header::PAYOUT_AMOUNT             => 1000,
                Batch\Header::PAYOUT_CURRENCY           => 'INR',
                Batch\Header::PAYOUT_MODE               => 'NEFT',
                Batch\Header::PAYOUT_PURPOSE            => 'refund',
                Batch\Header::PAYOUT_NARRATION          => 'test123',
                Batch\Header::PAYOUT_REFERENCE_ID       => '',
                Batch\Header::FUND_ACCOUNT_ID           => '',
                Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account',
                Batch\Header::FUND_ACCOUNT_NAME         => 'Mehul Kaushik',
                Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0010720',
                Batch\Header::FUND_ACCOUNT_NUMBER       => '100200300400',
                Batch\Header::FUND_ACCOUNT_VPA          => '',
                Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '',
                Batch\Header::FUND_ACCOUNT_EMAIL        => '',
                Batch\Header::CONTACT_TYPE              => 'employee',
                Batch\Header::CONTACT_NAME_2            => 'Mehul Kaushik',
                Batch\Header::CONTACT_EMAIL_2           => 'mehul.kaushik@razorpay.com',
                Batch\Header::CONTACT_MOBILE_2          => '',
                Batch\Header::CONTACT_REFERENCE_ID      => '',
            ],
        ];

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testValidateBatchPayoutsXLSXAmountInRupeesForExistingBulkRupeesUser()
    {
        $this->changeMerchantToExistingBulkRupeesType();

        $entries = [
            //
            // Have to keep the keys with Placeholders because in actual file,
            // the cells are merged but this is how the file is read by the FileUploaderTrait.
            //
            [
                'Mandatory Fields'             => Batch\Header::RAZORPAYX_ACCOUNT_NUMBER,
                'Placeholder 1'                => Batch\Header::PAYOUT_AMOUNT_RUPEES,
                'Placeholder 2'                => Batch\Header::PAYOUT_CURRENCY,
                'Placeholder 3'                => Batch\Header::PAYOUT_MODE,
                'Placeholder 4'                => Batch\Header::PAYOUT_PURPOSE,
                'Partially optional fields'    => Batch\Header::FUND_ACCOUNT_ID,
                'Placeholder 5'                => Batch\Header::FUND_ACCOUNT_TYPE,
                'Placeholder 6'                => Batch\Header::FUND_ACCOUNT_NAME,
                'Placeholder 7'                => Batch\Header::FUND_ACCOUNT_IFSC,
                'Placeholder 8'                => Batch\Header::FUND_ACCOUNT_NUMBER,
                'Placeholder 9'                => Batch\Header::FUND_ACCOUNT_VPA,
                'Placeholder 10'               => Batch\Header::FUND_ACCOUNT_PHONE_NUMBER,
                'Optional'                     => Batch\Header::CONTACT_NAME_2,
                'Placeholder 11'               => Batch\Header::PAYOUT_NARRATION,
                'Placeholder 12'               => Batch\Header::PAYOUT_REFERENCE_ID,
                'Placeholder 13'               => Batch\Header::FUND_ACCOUNT_EMAIL,
                'Placeholder 14'               => Batch\Header::CONTACT_TYPE,
                'Placeholder 15'               => Batch\Header::CONTACT_EMAIL_2,
                'Placeholder 16'               => Batch\Header::CONTACT_MOBILE_2,
                'Placeholder 17'               => Batch\Header::CONTACT_REFERENCE_ID,
                'Placeholder 18'               => Batch\Header::NOTES_CODE,
                'Placeholder 19'               => Batch\Header::NOTES_PLACE,
            ],
            [
                Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                Batch\Header::PAYOUT_AMOUNT_RUPEES      => 10,
                Batch\Header::PAYOUT_CURRENCY           => 'INR',
                Batch\Header::PAYOUT_MODE               => 'NEFT',
                Batch\Header::PAYOUT_PURPOSE            => 'refund',
                Batch\Header::FUND_ACCOUNT_ID           => '',
                Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account',
                Batch\Header::FUND_ACCOUNT_NAME         => 'Mehul Kaushik',
                Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0010720',
                Batch\Header::FUND_ACCOUNT_NUMBER       => '100200300400',
                Batch\Header::FUND_ACCOUNT_VPA          => '',
                Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '',
                Batch\Header::CONTACT_NAME_2            => 'Mehul Kaushik',
                Batch\Header::PAYOUT_NARRATION          => 'test123',
                Batch\Header::PAYOUT_REFERENCE_ID       => '',
                Batch\Header::FUND_ACCOUNT_EMAIL        => '',
                Batch\Header::CONTACT_TYPE              => 'employee',
                Batch\Header::CONTACT_EMAIL_2           => 'mehul.kaushik@razorpay.com',
                Batch\Header::CONTACT_MOBILE_2          => '',
                Batch\Header::CONTACT_REFERENCE_ID      => '',
                Batch\Header::NOTES_CODE                => 'test',
                Batch\Header::NOTES_PLACE               => 'test',
            ],
        ];

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testValidateBatchPayoutsCSVAmountInRupeesForExistingBulkRupeesUser()
    {
        $this->changeMerchantToExistingBulkRupeesType();

        $entries = [
            [
                Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                Batch\Header::PAYOUT_AMOUNT_RUPEES      => 10,
                Batch\Header::PAYOUT_CURRENCY           => 'INR',
                Batch\Header::PAYOUT_MODE               => 'NEFT',
                Batch\Header::PAYOUT_PURPOSE            => 'refund',
                Batch\Header::PAYOUT_NARRATION          => 'test123',
                Batch\Header::PAYOUT_REFERENCE_ID       => '',
                Batch\Header::FUND_ACCOUNT_ID           => '',
                Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account',
                Batch\Header::FUND_ACCOUNT_NAME         => 'Mehul Kaushik',
                Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0010720',
                Batch\Header::FUND_ACCOUNT_NUMBER       => '100200300400',
                Batch\Header::FUND_ACCOUNT_VPA          => '',
                Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '',
                Batch\Header::FUND_ACCOUNT_EMAIL        => '',
                Batch\Header::CONTACT_TYPE              => 'employee',
                Batch\Header::CONTACT_NAME_2            => 'Mehul Kaushik',
                Batch\Header::CONTACT_EMAIL_2           => 'mehul.kaushik@razorpay.com',
                Batch\Header::CONTACT_MOBILE_2          => '',
                Batch\Header::CONTACT_REFERENCE_ID      => '',
            ],
        ];

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testValidateBatchPayoutsXLSXBothTypeOfAmountHeaders()
    {
        $entries = [
            //
            // Have to keep the keys with Placeholders because in actual file,
            // the cells are merged but this is how the file is read by the FileUploaderTrait.
            //
            [
                'Mandatory Fields'             => Batch\Header::RAZORPAYX_ACCOUNT_NUMBER,
                'Placeholder 1'                => Batch\Header::PAYOUT_AMOUNT_RUPEES,
                'Placeholder 20'               => Batch\Header::PAYOUT_AMOUNT,
                'Placeholder 2'                => Batch\Header::PAYOUT_CURRENCY,
                'Placeholder 3'                => Batch\Header::PAYOUT_MODE,
                'Placeholder 4'                => Batch\Header::PAYOUT_PURPOSE,
                'Partially optional fields'    => Batch\Header::FUND_ACCOUNT_ID,
                'Placeholder 5'                => Batch\Header::FUND_ACCOUNT_TYPE,
                'Placeholder 6'                => Batch\Header::FUND_ACCOUNT_NAME,
                'Placeholder 7'                => Batch\Header::FUND_ACCOUNT_IFSC,
                'Placeholder 8'                => Batch\Header::FUND_ACCOUNT_NUMBER,
                'Placeholder 9'                => Batch\Header::FUND_ACCOUNT_VPA,
                'Placeholder 10'               => Batch\Header::FUND_ACCOUNT_PHONE_NUMBER,
                'Optional'                     => Batch\Header::CONTACT_NAME_2,
                'Placeholder 11'               => Batch\Header::PAYOUT_NARRATION,
                'Placeholder 12'               => Batch\Header::PAYOUT_REFERENCE_ID,
                'Placeholder 13'               => Batch\Header::FUND_ACCOUNT_EMAIL,
                'Placeholder 14'               => Batch\Header::CONTACT_TYPE,
                'Placeholder 15'               => Batch\Header::CONTACT_EMAIL_2,
                'Placeholder 16'               => Batch\Header::CONTACT_MOBILE_2,
                'Placeholder 17'               => Batch\Header::CONTACT_REFERENCE_ID,
                'Placeholder 18'               => Batch\Header::NOTES_CODE,
                'Placeholder 19'               => Batch\Header::NOTES_PLACE,
            ],
            [
                Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                Batch\Header::PAYOUT_AMOUNT_RUPEES      => 10,
                Batch\Header::PAYOUT_AMOUNT             => 1000,
                Batch\Header::PAYOUT_CURRENCY           => 'INR',
                Batch\Header::PAYOUT_MODE               => 'NEFT',
                Batch\Header::PAYOUT_PURPOSE            => 'refund',
                Batch\Header::FUND_ACCOUNT_ID           => '',
                Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account',
                Batch\Header::FUND_ACCOUNT_NAME         => 'Mehul Kaushik',
                Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0010720',
                Batch\Header::FUND_ACCOUNT_NUMBER       => '100200300400',
                Batch\Header::FUND_ACCOUNT_VPA          => '',
                Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '',
                Batch\Header::CONTACT_NAME_2            => 'Mehul Kaushik',
                Batch\Header::PAYOUT_NARRATION          => 'test123',
                Batch\Header::PAYOUT_REFERENCE_ID       => '',
                Batch\Header::FUND_ACCOUNT_EMAIL        => '',
                Batch\Header::CONTACT_TYPE              => 'employee',
                Batch\Header::CONTACT_EMAIL_2           => 'mehul.kaushik@razorpay.com',
                Batch\Header::CONTACT_MOBILE_2          => '',
                Batch\Header::CONTACT_REFERENCE_ID      => '',
                Batch\Header::NOTES_CODE                => 'test',
                Batch\Header::NOTES_PLACE               => 'test',
            ],
        ];

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testValidateErrorFileForBatchPayoutsCSVNewUser()
    {
        $entries = [
            [
                Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                Batch\Header::PAYOUT_AMOUNT_RUPEES      => 10,
                Batch\Header::PAYOUT_CURRENCY           => 'INR',
                // Changed mode to an incorrect value
                Batch\Header::PAYOUT_MODE               => 'ABCD',
                Batch\Header::PAYOUT_PURPOSE            => 'refund',
                Batch\Header::FUND_ACCOUNT_ID           => '',
                Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account',
                Batch\Header::FUND_ACCOUNT_NAME         => 'Mehul Kaushik',
                Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0010720',
                Batch\Header::FUND_ACCOUNT_NUMBER       => '100200300400',
                Batch\Header::FUND_ACCOUNT_VPA          => '',
                Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '',
                Batch\Header::CONTACT_NAME_2            => 'Mehul Kaushik',
                Batch\Header::PAYOUT_NARRATION          => 'test123',
                Batch\Header::PAYOUT_REFERENCE_ID       => '',
                Batch\Header::FUND_ACCOUNT_EMAIL        => '',
                Batch\Header::CONTACT_TYPE              => 'employee',
                Batch\Header::CONTACT_EMAIL_2           => 'mehul.kaushik@razorpay.com',
                Batch\Header::CONTACT_MOBILE_2          => '',
                Batch\Header::CONTACT_REFERENCE_ID      => '',
            ],
        ];

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $fileContent = file($response['signed_url']);

        $expectedHeaderRow =  'Error Description,RazorpayX Account Number,Payout Amount (in Rupees),Payout Currency,Payout Mode,'.
            'Payout Purpose,Fund Account Id,Fund Account Type,Fund Account Name,Fund Account Ifsc,Fund Account Number,'.
            'Fund Account Vpa,Fund Account Phone Number,Contact Name,Payout Narration,Payout Reference Id,Fund Account Email,' .
            'Contact Type,Contact Email,Contact Mobile,Contact Reference Id';

        $expectedDataRow = 'Payout mode is invalid,2323230041626905,10,INR,ABCD,refund,,bank_account,Mehul Kaushik,SBIN0010720,100200300400,,' .
            ',Mehul Kaushik,test123,,,employee,mehul.kaushik@razorpay.com,,';

        $this->assertEquals($expectedHeaderRow, trim($fileContent[0]));
        $this->assertEquals($expectedDataRow, trim($fileContent[1]));
    }

    public function testValidateErrorFileForBatchPayoutsCSVExistingBulkPaiseUser()
    {
        $this->changeMerchantToExistingBulkPaiseType();

        $entries = [
            [
                Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                Batch\Header::PAYOUT_AMOUNT             => 1000,
                Batch\Header::PAYOUT_CURRENCY           => 'INR',
                // Changed mode to an incorrect value
                Batch\Header::PAYOUT_MODE               => 'ABCD',
                Batch\Header::PAYOUT_PURPOSE            => 'refund',
                Batch\Header::FUND_ACCOUNT_ID           => '',
                Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account',
                Batch\Header::FUND_ACCOUNT_NAME         => 'Mehul Kaushik',
                Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0010720',
                Batch\Header::FUND_ACCOUNT_NUMBER       => '100200300400',
                Batch\Header::FUND_ACCOUNT_VPA          => '',
                Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '',
                Batch\Header::CONTACT_NAME_2            => 'Mehul Kaushik',
                Batch\Header::PAYOUT_NARRATION          => 'test123',
                Batch\Header::PAYOUT_REFERENCE_ID       => '',
                Batch\Header::FUND_ACCOUNT_EMAIL        => '',
                Batch\Header::CONTACT_TYPE              => 'employee',
                Batch\Header::CONTACT_EMAIL_2           => 'mehul.kaushik@razorpay.com',
                Batch\Header::CONTACT_MOBILE_2          => '',
                Batch\Header::CONTACT_REFERENCE_ID      => '',
            ],
        ];

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $fileContent = file($response['signed_url']);

        $expectedHeaderRow =  'Error Description,RazorpayX Account Number,Payout Amount,Payout Currency,Payout Mode,'.
            'Payout Purpose,Fund Account Id,Fund Account Type,Fund Account Name,Fund Account Ifsc,Fund Account Number,'.
            'Fund Account Vpa,Fund Account Phone Number,Contact Name,Payout Narration,Payout Reference Id,Fund Account Email,' .
            'Contact Type,Contact Email,Contact Mobile,Contact Reference Id';

        $expectedDataRow = 'Payout mode is invalid,2323230041626905,1000,INR,ABCD,refund,,bank_account,Mehul Kaushik,SBIN0010720,100200300400,,' .
            ',Mehul Kaushik,test123,,,employee,mehul.kaushik@razorpay.com,,';

        $this->assertEquals($expectedHeaderRow, trim($fileContent[0]));
        $this->assertEquals($expectedDataRow, trim($fileContent[1]));
    }

    public function testValidateErrorFileForBatchPayoutsCSVExistingBulkRupeesUser()
    {
        $this->changeMerchantToExistingBulkRupeesType();

        $entries = [
            [
                Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                Batch\Header::PAYOUT_AMOUNT_RUPEES      => 10.23,
                Batch\Header::PAYOUT_CURRENCY           => 'INR',
                // Changed mode to an incorrect value
                Batch\Header::PAYOUT_MODE               => 'ABCD',
                Batch\Header::PAYOUT_PURPOSE            => 'refund',
                Batch\Header::FUND_ACCOUNT_ID           => '',
                Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account',
                Batch\Header::FUND_ACCOUNT_NAME         => 'Mehul Kaushik',
                Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0010720',
                Batch\Header::FUND_ACCOUNT_NUMBER       => '100200300400',
                Batch\Header::FUND_ACCOUNT_VPA          => '',
                Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '',
                Batch\Header::CONTACT_NAME_2            => 'Mehul Kaushik',
                Batch\Header::PAYOUT_NARRATION          => 'test123',
                Batch\Header::PAYOUT_REFERENCE_ID       => '',
                Batch\Header::FUND_ACCOUNT_EMAIL        => '',
                Batch\Header::CONTACT_TYPE              => 'employee',
                Batch\Header::CONTACT_EMAIL_2           => 'mehul.kaushik@razorpay.com',
                Batch\Header::CONTACT_MOBILE_2          => '',
                Batch\Header::CONTACT_REFERENCE_ID      => '',
            ],
        ];

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertEquals(1023, $response['total_payout_amount']);

        $fileContent = file($response['signed_url']);

        $expectedHeaderRow =  'Error Description,RazorpayX Account Number,Payout Amount (in Rupees),Payout Currency,Payout Mode,'.
            'Payout Purpose,Fund Account Id,Fund Account Type,Fund Account Name,Fund Account Ifsc,Fund Account Number,'.
            'Fund Account Vpa,Fund Account Phone Number,Contact Name,Payout Narration,Payout Reference Id,Fund Account Email,' .
            'Contact Type,Contact Email,Contact Mobile,Contact Reference Id';

        $expectedDataRow = 'Payout mode is invalid,2323230041626905,10.23,INR,ABCD,refund,,bank_account,Mehul Kaushik,SBIN0010720,100200300400,,' .
            ',Mehul Kaushik,test123,,,employee,mehul.kaushik@razorpay.com,,';

        $this->assertEquals($expectedHeaderRow, trim($fileContent[0]));
        $this->assertEquals($expectedDataRow, trim($fileContent[1]));
    }

    public function testValidateErrorFileForBatchPayoutsXLSXNewUser()
    {
        $entries = [
            //
            // Have to keep the keys with Placeholders because in actual file,
            // the cells are merged but this is how the file is read by the FileUploaderTrait.
            //
            [
                'Mandatory Fields'             => Batch\Header::RAZORPAYX_ACCOUNT_NUMBER,
                'Placeholder 1'                => Batch\Header::PAYOUT_AMOUNT_RUPEES,
                'Placeholder 2'                => Batch\Header::PAYOUT_CURRENCY,
                'Placeholder 3'                => Batch\Header::PAYOUT_MODE,
                'Placeholder 4'                => Batch\Header::PAYOUT_PURPOSE,
                'Partially optional fields'    => Batch\Header::FUND_ACCOUNT_ID,
                'Placeholder 5'                => Batch\Header::FUND_ACCOUNT_TYPE,
                'Placeholder 6'                => Batch\Header::FUND_ACCOUNT_NAME,
                'Placeholder 7'                => Batch\Header::FUND_ACCOUNT_IFSC,
                'Placeholder 8'                => Batch\Header::FUND_ACCOUNT_NUMBER,
                'Placeholder 9'                => Batch\Header::FUND_ACCOUNT_VPA,
                'Placeholder 10'               => Batch\Header::FUND_ACCOUNT_PHONE_NUMBER,
                'Optional'                     => Batch\Header::CONTACT_NAME_2,
                'Placeholder 11'               => Batch\Header::PAYOUT_NARRATION,
                'Placeholder 12'               => Batch\Header::PAYOUT_REFERENCE_ID,
                'Placeholder 13'               => Batch\Header::FUND_ACCOUNT_EMAIL,
                'Placeholder 14'               => Batch\Header::CONTACT_TYPE,
                'Placeholder 15'               => Batch\Header::CONTACT_EMAIL_2,
                'Placeholder 16'               => Batch\Header::CONTACT_MOBILE_2,
                'Placeholder 17'               => Batch\Header::CONTACT_REFERENCE_ID,
                'Placeholder 18'               => Batch\Header::NOTES_CODE,
                'Placeholder 19'               => Batch\Header::NOTES_PLACE,
            ],
            [
                Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                Batch\Header::PAYOUT_AMOUNT_RUPEES      => 10,
                Batch\Header::PAYOUT_CURRENCY           => 'INR',
                Batch\Header::PAYOUT_MODE               => 'ABCD',
                Batch\Header::PAYOUT_PURPOSE            => 'refund',
                Batch\Header::FUND_ACCOUNT_ID           => '',
                Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account',
                Batch\Header::FUND_ACCOUNT_NAME         => 'Mehul Kaushik',
                Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0010720',
                Batch\Header::FUND_ACCOUNT_NUMBER       => '100200300400',
                Batch\Header::FUND_ACCOUNT_VPA          => '',
                Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '',
                Batch\Header::CONTACT_NAME_2            => 'Mehul Kaushik',
                Batch\Header::PAYOUT_NARRATION          => 'test123',
                Batch\Header::PAYOUT_REFERENCE_ID       => '',
                Batch\Header::FUND_ACCOUNT_EMAIL        => '',
                Batch\Header::CONTACT_TYPE              => 'employee',
                Batch\Header::CONTACT_EMAIL_2           => 'mehul.kaushik@razorpay.com',
                Batch\Header::CONTACT_MOBILE_2          => '',
                Batch\Header::CONTACT_REFERENCE_ID      => '',
                Batch\Header::NOTES_CODE                => 'test',
                Batch\Header::NOTES_PLACE               => 'test',
            ],
        ];

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertEquals(1000, $response['total_payout_amount']);

        $spreadsheet = IOFactory::load($response['signed_url']);

        $activeSheet = $spreadsheet->getActiveSheet();

        $this->assertEquals(3, $activeSheet->getHighestRow());

        $expectedData = [
            [
                null,
                'Mandatory Fields',
                null,
                null,
                null,
                null,
                '(Conditionally Mandatory) If you want to make a payout to an existing fund account you can just add their Fund Account Id.',
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                'Optional Fields',
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                null,
            ],
            [
                'Error Description',
                'RazorpayX Account Number',
                'Payout Amount (in Rupees)',
                'Payout Currency',
                'Payout Mode',
                'Payout Purpose',
                'Fund Account Id',
                'Fund Account Type',
                'Fund Account Name',
                'Fund Account Ifsc',
                'Fund Account Number',
                'Fund Account Vpa',
                'Fund Account Phone Number',
                'Contact Name',
                'Payout Narration',
                'Payout Reference Id',
                'Fund Account Email',
                'Contact Type',
                'Contact Email',
                'Contact Mobile',
                'Contact Reference Id',
                'notes[code]',
                'notes[place]',
            ],
            [
                'Payout mode is invalid',
                // NOTE : Account number is set as a merchant's banking balance's account number : 2224440041626905
                // The trailing whitespace makes sure that the account number is displayed correctly
                '2323230041626905 ',
                10,
                'INR',
                'ABCD',
                'refund',
                null,
                'bank_account',
                'Mehul Kaushik',
                'SBIN0010720',
                '100200300400 ',
                null,
                null,
                'Mehul Kaushik',
                'test123',
                null,
                null,
                'employee',
                'mehul.kaushik@razorpay.com',
                ' ',
                null,
                'test',
                'test',
            ]
        ];

        for ($row = 1; $row <= 3; $row++)
        {
            for ($col = 1; $col <= 23; $col++)
            {
                try
                {
                    $cellValue = $activeSheet->getCellByColumnAndRow($col, $row, false)->getValue();
                }
                catch (\Throwable $ex)
                {
                    $cellValue = null;
                }

                $this->assertEquals($expectedData[$row-1][$col-1], $cellValue);
            }
        }
    }

    public function testValidateErrorFileForBatchPayoutsXLSXExistingBulkRupeesUser()
    {
        $this->changeMerchantToExistingBulkRupeesType();

        $entries = [
            //
            // Have to keep the keys with Placeholders because in actual file,
            // the cells are merged but this is how the file is read by the FileUploaderTrait.
            //
            [
                'Mandatory Fields'             => Batch\Header::RAZORPAYX_ACCOUNT_NUMBER,
                'Placeholder 1'                => Batch\Header::PAYOUT_AMOUNT_RUPEES,
                'Placeholder 2'                => Batch\Header::PAYOUT_CURRENCY,
                'Placeholder 3'                => Batch\Header::PAYOUT_MODE,
                'Placeholder 4'                => Batch\Header::PAYOUT_PURPOSE,
                'Partially optional fields'    => Batch\Header::FUND_ACCOUNT_ID,
                'Placeholder 5'                => Batch\Header::FUND_ACCOUNT_TYPE,
                'Placeholder 6'                => Batch\Header::FUND_ACCOUNT_NAME,
                'Placeholder 7'                => Batch\Header::FUND_ACCOUNT_IFSC,
                'Placeholder 8'                => Batch\Header::FUND_ACCOUNT_NUMBER,
                'Placeholder 9'                => Batch\Header::FUND_ACCOUNT_VPA,
                'Placeholder 10'               => Batch\Header::FUND_ACCOUNT_PHONE_NUMBER,
                'Optional'                     => Batch\Header::CONTACT_NAME_2,
                'Placeholder 11'               => Batch\Header::PAYOUT_NARRATION,
                'Placeholder 12'               => Batch\Header::PAYOUT_REFERENCE_ID,
                'Placeholder 13'               => Batch\Header::FUND_ACCOUNT_EMAIL,
                'Placeholder 14'               => Batch\Header::CONTACT_TYPE,
                'Placeholder 15'               => Batch\Header::CONTACT_EMAIL_2,
                'Placeholder 16'               => Batch\Header::CONTACT_MOBILE_2,
                'Placeholder 17'               => Batch\Header::CONTACT_REFERENCE_ID,
                'Placeholder 18'               => Batch\Header::NOTES_CODE,
                'Placeholder 19'               => Batch\Header::NOTES_PLACE,
            ],
            [
                Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                Batch\Header::PAYOUT_AMOUNT_RUPEES      => 10,
                Batch\Header::PAYOUT_CURRENCY           => 'INR',
                Batch\Header::PAYOUT_MODE               => 'ABCD',
                Batch\Header::PAYOUT_PURPOSE            => 'refund',
                Batch\Header::FUND_ACCOUNT_ID           => '',
                Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account',
                Batch\Header::FUND_ACCOUNT_NAME         => 'Mehul Kaushik',
                Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0010720',
                Batch\Header::FUND_ACCOUNT_NUMBER       => '100200300400',
                Batch\Header::FUND_ACCOUNT_VPA          => '',
                Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '',
                Batch\Header::CONTACT_NAME_2            => 'Mehul Kaushik',
                Batch\Header::PAYOUT_NARRATION          => 'test123',
                Batch\Header::PAYOUT_REFERENCE_ID       => '',
                Batch\Header::FUND_ACCOUNT_EMAIL        => '',
                Batch\Header::CONTACT_TYPE              => 'employee',
                Batch\Header::CONTACT_EMAIL_2           => 'mehul.kaushik@razorpay.com',
                Batch\Header::CONTACT_MOBILE_2          => '',
                Batch\Header::CONTACT_REFERENCE_ID      => '',
                Batch\Header::NOTES_CODE                => 'test',
                Batch\Header::NOTES_PLACE               => 'test',
            ],
        ];

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertEquals(1000, $response['total_payout_amount']);

        $spreadsheet = IOFactory::load($response['signed_url']);

        $activeSheet = $spreadsheet->getActiveSheet();

        $this->assertEquals(3, $activeSheet->getHighestRow());

        $expectedData = [
            [
                null,
                'Mandatory Fields',
                null,
                null,
                null,
                null,
                '(Conditionally Mandatory) If you want to make a payout to an existing fund account you can just add their Fund Account Id.',
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                'Optional Fields',
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                null,
            ],
            [
                'Error Description',
                'RazorpayX Account Number',
                'Payout Amount (in Rupees)',
                'Payout Currency',
                'Payout Mode',
                'Payout Purpose',
                'Fund Account Id',
                'Fund Account Type',
                'Fund Account Name',
                'Fund Account Ifsc',
                'Fund Account Number',
                'Fund Account Vpa',
                'Fund Account Phone Number',
                'Contact Name',
                'Payout Narration',
                'Payout Reference Id',
                'Fund Account Email',
                'Contact Type',
                'Contact Email',
                'Contact Mobile',
                'Contact Reference Id',
                'notes[code]',
                'notes[place]',
            ],
            [
                'Payout mode is invalid',
                // NOTE : Account number is set as a merchant's banking balance's account number : 2224440041626905
                // The trailing whitespace makes sure that the account number is displayed correctly
                '2323230041626905 ',
                10,
                'INR',
                'ABCD',
                'refund',
                null,
                'bank_account',
                'Mehul Kaushik',
                'SBIN0010720',
                '100200300400 ',
                null,
                null,
                'Mehul Kaushik',
                'test123',
                null,
                null,
                'employee',
                'mehul.kaushik@razorpay.com',
                ' ',
                null,
                'test',
                'test',
            ]
        ];

        for ($row = 1; $row <= 3; $row++)
        {
            for ($col = 1; $col <= 23; $col++)
            {
                try
                {
                    $cellValue = $activeSheet->getCellByColumnAndRow($col, $row, false)->getValue();
                }
                catch (\Throwable $ex)
                {
                    $cellValue = null;
                }

                $this->assertEquals($expectedData[$row-1][$col-1], $cellValue);
            }
        }
    }

    public function testValidateBatchPayoutsCSVRandomExtraHeader()
    {
        $entries = [
            [
                Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                Batch\Header::PAYOUT_AMOUNT             => 1000,
                Batch\Header::PAYOUT_CURRENCY           => 'INR',
                Batch\Header::PAYOUT_MODE               => 'NEFT',
                Batch\Header::PAYOUT_PURPOSE            => 'refund',
                Batch\Header::PAYOUT_NARRATION          => 'test123',
                Batch\Header::PAYOUT_REFERENCE_ID       => '',
                Batch\Header::FUND_ACCOUNT_ID           => '',
                Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account',
                Batch\Header::FUND_ACCOUNT_NAME         => 'Mehul Kaushik',
                Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0010720',
                Batch\Header::FUND_ACCOUNT_NUMBER       => '100200300400',
                Batch\Header::FUND_ACCOUNT_VPA          => '',
                Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '',
                Batch\Header::FUND_ACCOUNT_EMAIL        => '',
                Batch\Header::CONTACT_TYPE              => 'employee',
                Batch\Header::CONTACT_NAME_2            => 'Mehul Kaushik',
                Batch\Header::CONTACT_EMAIL_2           => 'mehul.kaushik@razorpay.com',
                Batch\Header::CONTACT_MOBILE_2          => '',
                Batch\Header::CONTACT_REFERENCE_ID      => '',
                // Below is a random Header picked up from Batch\Headers
                Batch\Header::PRICING_RULE_FEATURE      => 'abc'
            ],
        ];

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testValidateBatchPayoutsCSVPayoutAmountRupeesFloatWithThreeDecimals()
    {
        $entries = [
            [
                Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                Batch\Header::PAYOUT_AMOUNT_RUPEES      => 10.333,
                Batch\Header::PAYOUT_CURRENCY           => 'INR',
                Batch\Header::PAYOUT_MODE               => 'NEFT',
                Batch\Header::PAYOUT_PURPOSE            => 'refund',
                Batch\Header::FUND_ACCOUNT_ID           => '',
                Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account',
                Batch\Header::FUND_ACCOUNT_NAME         => 'Mehul Kaushik',
                Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0010720',
                Batch\Header::FUND_ACCOUNT_NUMBER       => '100200300400',
                Batch\Header::FUND_ACCOUNT_VPA          => '',
                Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '',
                Batch\Header::CONTACT_NAME_2            => 'Mehul Kaushik',
                Batch\Header::PAYOUT_NARRATION          => 'test123',
                Batch\Header::PAYOUT_REFERENCE_ID       => '',
                Batch\Header::FUND_ACCOUNT_EMAIL        => '',
                Batch\Header::CONTACT_TYPE              => 'employee',
                Batch\Header::CONTACT_EMAIL_2           => 'mehul.kaushik@razorpay.com',
                Batch\Header::CONTACT_MOBILE_2          => '',
                Batch\Header::CONTACT_REFERENCE_ID      => '',
            ],
        ];

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $fileContent = file($response['signed_url']);

        $expectedHeaderRow =  'Error Description,RazorpayX Account Number,Payout Amount (in Rupees),Payout Currency,Payout Mode,'.
            'Payout Purpose,Fund Account Id,Fund Account Type,Fund Account Name,Fund Account Ifsc,Fund Account Number,'.
            'Fund Account Vpa,Fund Account Phone Number,Contact Name,Payout Narration,Payout Reference Id,Fund Account Email,' .
            'Contact Type,Contact Email,Contact Mobile,Contact Reference Id';

        $expectedDataRow1 = 'The payout amount(in rupees) format is invalid.,2323230041626905,10.333,INR,NEFT,refund,,bank_account,Mehul Kaushik,SBIN0010720,100200300400,,' .
            ',Mehul Kaushik,test123,,,employee,mehul.kaushik@razorpay.com,,';

        $this->assertEquals($expectedHeaderRow, trim($fileContent[0]));
        $this->assertEquals($expectedDataRow1, trim($fileContent[1]));
    }

    public function testValidateBatchPayoutsCSVAmountInRupeesNewUserExperimentOff()
    {
        $this->turnBulkRolloutExperimentOff();

        $entries = [
            [
                Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                Batch\Header::PAYOUT_AMOUNT_RUPEES      => 10,
                Batch\Header::PAYOUT_CURRENCY           => 'INR',
                Batch\Header::PAYOUT_MODE               => 'NEFT',
                Batch\Header::PAYOUT_PURPOSE            => 'refund',
                Batch\Header::PAYOUT_NARRATION          => 'test123',
                Batch\Header::PAYOUT_REFERENCE_ID       => '',
                Batch\Header::FUND_ACCOUNT_ID           => '',
                Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account',
                Batch\Header::FUND_ACCOUNT_NAME         => 'Mehul Kaushik',
                Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0010720',
                Batch\Header::FUND_ACCOUNT_NUMBER       => '100200300400',
                Batch\Header::FUND_ACCOUNT_VPA          => '',
                Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '',
                Batch\Header::FUND_ACCOUNT_EMAIL        => '',
                Batch\Header::CONTACT_TYPE              => 'employee',
                Batch\Header::CONTACT_NAME_2            => 'Mehul Kaushik',
                Batch\Header::CONTACT_EMAIL_2           => 'mehul.kaushik@razorpay.com',
                Batch\Header::CONTACT_MOBILE_2          => '',
                Batch\Header::CONTACT_REFERENCE_ID      => '',
            ],
        ];

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testValidateBatchPayoutsCSVAmountInRupeesExistingBulkRupeesUserExperimentOff()
    {
        $this->turnBulkRolloutExperimentOff();

        $this->changeMerchantToExistingBulkRupeesType();

        $entries = [
            [
                Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                Batch\Header::PAYOUT_AMOUNT_RUPEES      => 10,
                Batch\Header::PAYOUT_CURRENCY           => 'INR',
                Batch\Header::PAYOUT_MODE               => 'NEFT',
                Batch\Header::PAYOUT_PURPOSE            => 'refund',
                Batch\Header::PAYOUT_NARRATION          => 'test123',
                Batch\Header::PAYOUT_REFERENCE_ID       => '',
                Batch\Header::FUND_ACCOUNT_ID           => '',
                Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account',
                Batch\Header::FUND_ACCOUNT_NAME         => 'Mehul Kaushik',
                Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0010720',
                Batch\Header::FUND_ACCOUNT_NUMBER       => '100200300400',
                Batch\Header::FUND_ACCOUNT_VPA          => '',
                Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '',
                Batch\Header::FUND_ACCOUNT_EMAIL        => '',
                Batch\Header::CONTACT_TYPE              => 'employee',
                Batch\Header::CONTACT_NAME_2            => 'Mehul Kaushik',
                Batch\Header::CONTACT_EMAIL_2           => 'mehul.kaushik@razorpay.com',
                Batch\Header::CONTACT_MOBILE_2          => '',
                Batch\Header::CONTACT_REFERENCE_ID      => '',
            ],
        ];

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $this->startTest();
    }


    public function testValidateBatchPayoutsCSVAmountInPaiseNewUserExperimentOff()
    {
        $this->turnBulkRolloutExperimentOff();

        $entries = [
            [
                Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                Batch\Header::PAYOUT_AMOUNT             => 1000,
                Batch\Header::PAYOUT_CURRENCY           => 'INR',
                Batch\Header::PAYOUT_MODE               => 'NEFT',
                Batch\Header::PAYOUT_PURPOSE            => 'refund',
                Batch\Header::PAYOUT_NARRATION          => 'test123',
                Batch\Header::PAYOUT_REFERENCE_ID       => '',
                Batch\Header::FUND_ACCOUNT_ID           => '',
                Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account',
                Batch\Header::FUND_ACCOUNT_NAME         => 'Mehul Kaushik',
                Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0010720',
                Batch\Header::FUND_ACCOUNT_NUMBER       => '100200300400',
                Batch\Header::FUND_ACCOUNT_VPA          => '',
                Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '',
                Batch\Header::FUND_ACCOUNT_EMAIL        => '',
                Batch\Header::CONTACT_TYPE              => 'employee',
                Batch\Header::CONTACT_NAME_2            => 'Mehul Kaushik',
                Batch\Header::CONTACT_EMAIL_2           => 'mehul.kaushik@razorpay.com',
                Batch\Header::CONTACT_MOBILE_2          => '',
                Batch\Header::CONTACT_REFERENCE_ID      => '',
            ],
        ];

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testValidateBatchPayoutsCSVAmountInPaiseExistingBulkRupeesUserExperimentOff()
    {
        $this->turnBulkRolloutExperimentOff();

        $this->changeMerchantToExistingBulkRupeesType();

        $entries = [
            [
                Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                Batch\Header::PAYOUT_AMOUNT             => 1000,
                Batch\Header::PAYOUT_CURRENCY           => 'INR',
                Batch\Header::PAYOUT_MODE               => 'NEFT',
                Batch\Header::PAYOUT_PURPOSE            => 'refund',
                Batch\Header::PAYOUT_NARRATION          => 'test123',
                Batch\Header::PAYOUT_REFERENCE_ID       => '',
                Batch\Header::FUND_ACCOUNT_ID           => '',
                Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account',
                Batch\Header::FUND_ACCOUNT_NAME         => 'Mehul Kaushik',
                Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0010720',
                Batch\Header::FUND_ACCOUNT_NUMBER       => '100200300400',
                Batch\Header::FUND_ACCOUNT_VPA          => '',
                Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '',
                Batch\Header::FUND_ACCOUNT_EMAIL        => '',
                Batch\Header::CONTACT_TYPE              => 'employee',
                Batch\Header::CONTACT_NAME_2            => 'Mehul Kaushik',
                Batch\Header::CONTACT_EMAIL_2           => 'mehul.kaushik@razorpay.com',
                Batch\Header::CONTACT_MOBILE_2          => '',
                Batch\Header::CONTACT_REFERENCE_ID      => '',
            ],
        ];

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testValidateErrorFileForBatchPayoutsCSVNewUserExperimentOff()
    {
        $this->turnBulkRolloutExperimentOff();

        $entries = [
            [
                Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                Batch\Header::PAYOUT_AMOUNT             => 1000,
                Batch\Header::PAYOUT_CURRENCY           => 'INR',
                // Changed mode to an incorrect value
                Batch\Header::PAYOUT_MODE               => 'ABCD',
                Batch\Header::PAYOUT_PURPOSE            => 'refund',
                Batch\Header::FUND_ACCOUNT_ID           => '',
                Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account',
                Batch\Header::FUND_ACCOUNT_NAME         => 'Mehul Kaushik',
                Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0010720',
                Batch\Header::FUND_ACCOUNT_NUMBER       => '100200300400',
                Batch\Header::FUND_ACCOUNT_VPA          => '',
                Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '',
                Batch\Header::CONTACT_NAME_2            => 'Mehul Kaushik',
                Batch\Header::PAYOUT_NARRATION          => 'test123',
                Batch\Header::PAYOUT_REFERENCE_ID       => '',
                Batch\Header::FUND_ACCOUNT_EMAIL        => '',
                Batch\Header::CONTACT_TYPE              => 'employee',
                Batch\Header::CONTACT_EMAIL_2           => 'mehul.kaushik@razorpay.com',
                Batch\Header::CONTACT_MOBILE_2          => '',
                Batch\Header::CONTACT_REFERENCE_ID      => '',
            ],
        ];

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $fileContent = file($response['signed_url']);

        $expectedHeaderRow =  'Error Description,RazorpayX Account Number,Payout Amount,Payout Currency,Payout Mode,'.
            'Payout Purpose,Fund Account Id,Fund Account Type,Fund Account Name,Fund Account Ifsc,Fund Account Number,'.
            'Fund Account Vpa,Fund Account Phone Number,Contact Name,Payout Narration,Payout Reference Id,Fund Account Email,' .
            'Contact Type,Contact Email,Contact Mobile,Contact Reference Id';

        $expectedDataRow = 'Payout mode is invalid,2323230041626905,1000,INR,ABCD,refund,,bank_account,Mehul Kaushik,SBIN0010720,100200300400,,' .
            ',Mehul Kaushik,test123,,,employee,mehul.kaushik@razorpay.com,,';

        $this->assertEquals($expectedHeaderRow, trim($fileContent[0]));
        $this->assertEquals($expectedDataRow, trim($fileContent[1]));
    }

    public function testValidateErrorFileForBatchPayoutsCSVExistingBulkPaiseUserExperimentOff()
    {
        $this->turnBulkRolloutExperimentOff();

        $this->changeMerchantToExistingBulkPaiseType();

        $entries = [
            [
                Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                Batch\Header::PAYOUT_AMOUNT             => 1000,
                Batch\Header::PAYOUT_CURRENCY           => 'INR',
                // Changed mode to an incorrect value
                Batch\Header::PAYOUT_MODE               => 'ABCD',
                Batch\Header::PAYOUT_PURPOSE            => 'refund',
                Batch\Header::FUND_ACCOUNT_ID           => '',
                Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account',
                Batch\Header::FUND_ACCOUNT_NAME         => 'Mehul Kaushik',
                Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0010720',
                Batch\Header::FUND_ACCOUNT_NUMBER       => '100200300400',
                Batch\Header::FUND_ACCOUNT_VPA          => '',
                Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '',
                Batch\Header::CONTACT_NAME_2            => 'Mehul Kaushik',
                Batch\Header::PAYOUT_NARRATION          => 'test123',
                Batch\Header::PAYOUT_REFERENCE_ID       => '',
                Batch\Header::FUND_ACCOUNT_EMAIL        => '',
                Batch\Header::CONTACT_TYPE              => 'employee',
                Batch\Header::CONTACT_EMAIL_2           => 'mehul.kaushik@razorpay.com',
                Batch\Header::CONTACT_MOBILE_2          => '',
                Batch\Header::CONTACT_REFERENCE_ID      => '',
            ],
        ];

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $fileContent = file($response['signed_url']);

        $expectedHeaderRow = 'Error Description,RazorpayX Account Number,Payout Amount,Payout Currency,Payout Mode,'.
            'Payout Purpose,Fund Account Id,Fund Account Type,Fund Account Name,Fund Account Ifsc,Fund Account Number,'.
            'Fund Account Vpa,Fund Account Phone Number,Contact Name,Payout Narration,Payout Reference Id,Fund Account Email,' .
            'Contact Type,Contact Email,Contact Mobile,Contact Reference Id';

        $expectedDataRow = 'Payout mode is invalid,2323230041626905,1000,INR,ABCD,refund,,bank_account,Mehul Kaushik,SBIN0010720,100200300400,,' .
            ',Mehul Kaushik,test123,,,employee,mehul.kaushik@razorpay.com,,';

        $this->assertEquals($expectedHeaderRow, trim($fileContent[0]));
        $this->assertEquals($expectedDataRow, trim($fileContent[1]));
    }

    public function testValidateErrorFileForBatchPayoutsCSVExistingBulkRupeesUserExperimentOff()
    {
        $this->turnBulkRolloutExperimentOff();

        $this->changeMerchantToExistingBulkRupeesType();

        $entries = [
            [
                Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                Batch\Header::PAYOUT_AMOUNT             => 1000,
                Batch\Header::PAYOUT_CURRENCY           => 'INR',
                // Changed mode to an incorrect value
                Batch\Header::PAYOUT_MODE               => 'ABCD',
                Batch\Header::PAYOUT_PURPOSE            => 'refund',
                Batch\Header::FUND_ACCOUNT_ID           => '',
                Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account',
                Batch\Header::FUND_ACCOUNT_NAME         => 'Mehul Kaushik',
                Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0010720',
                Batch\Header::FUND_ACCOUNT_NUMBER       => '100200300400',
                Batch\Header::FUND_ACCOUNT_VPA          => '',
                Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '',
                Batch\Header::CONTACT_NAME_2            => 'Mehul Kaushik',
                Batch\Header::PAYOUT_NARRATION          => 'test123',
                Batch\Header::PAYOUT_REFERENCE_ID       => '',
                Batch\Header::FUND_ACCOUNT_EMAIL        => '',
                Batch\Header::CONTACT_TYPE              => 'employee',
                Batch\Header::CONTACT_EMAIL_2           => 'mehul.kaushik@razorpay.com',
                Batch\Header::CONTACT_MOBILE_2          => '',
                Batch\Header::CONTACT_REFERENCE_ID      => '',
            ],
        ];

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $fileContent = file($response['signed_url']);

        $expectedHeaderRow =  'Error Description,RazorpayX Account Number,Payout Amount,Payout Currency,Payout Mode,'.
            'Payout Purpose,Fund Account Id,Fund Account Type,Fund Account Name,Fund Account Ifsc,Fund Account Number,'.
            'Fund Account Vpa,Fund Account Phone Number,Contact Name,Payout Narration,Payout Reference Id,Fund Account Email,' .
            'Contact Type,Contact Email,Contact Mobile,Contact Reference Id';

        $expectedDataRow = 'Payout mode is invalid,2323230041626905,1000,INR,ABCD,refund,,bank_account,Mehul Kaushik,SBIN0010720,100200300400,,' .
            ',Mehul Kaushik,test123,,,employee,mehul.kaushik@razorpay.com,,';

        $this->assertEquals($expectedHeaderRow, trim($fileContent[0]));
        $this->assertEquals($expectedDataRow, trim($fileContent[1]));
    }

    public function testValidateErrorFileForBatchPayoutsCSVAmountInRupeesExistingBulkRupeesUserExperimentOff()
    {
        $this->turnBulkRolloutExperimentOff();

        $this->changeMerchantToExistingBulkRupeesType();

        $entries = [
            [
                Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                Batch\Header::PAYOUT_AMOUNT_RUPEES      => 10,
                Batch\Header::PAYOUT_CURRENCY           => 'INR',
                // Changed mode to an incorrect value
                Batch\Header::PAYOUT_MODE               => 'ABCD',
                Batch\Header::PAYOUT_PURPOSE            => 'refund',
                Batch\Header::PAYOUT_NARRATION          => 'test123',
                Batch\Header::PAYOUT_REFERENCE_ID       => '',
                Batch\Header::FUND_ACCOUNT_ID           => '',
                Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account',
                Batch\Header::FUND_ACCOUNT_NAME         => 'Mehul Kaushik',
                Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0010720',
                Batch\Header::FUND_ACCOUNT_NUMBER       => '100200300400',
                Batch\Header::FUND_ACCOUNT_VPA          => '',
                Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '',
                Batch\Header::FUND_ACCOUNT_EMAIL        => '',
                Batch\Header::CONTACT_TYPE              => 'employee',
                Batch\Header::CONTACT_NAME_2            => 'Mehul Kaushik',
                Batch\Header::CONTACT_EMAIL_2           => 'mehul.kaushik@razorpay.com',
                Batch\Header::CONTACT_MOBILE_2          => '',
                Batch\Header::CONTACT_REFERENCE_ID      => '',
            ],
        ];

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    // Upload a CSV file and it gets successfully validated.
    // We shall also add assertions to the `batch/validated` file since that is the file that gets sent to Batch Service
    public function testValidateBatchPayoutsCSVFundAccountStartingWith0()
    {
        $entries = [
            [
                Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                Batch\Header::PAYOUT_AMOUNT_RUPEES      => 10,
                Batch\Header::PAYOUT_CURRENCY           => 'INR',
                Batch\Header::PAYOUT_MODE               => 'NEFT',
                Batch\Header::PAYOUT_PURPOSE            => 'refund',
                Batch\Header::FUND_ACCOUNT_ID           => '',
                Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account',
                Batch\Header::FUND_ACCOUNT_NAME         => 'Mehul Kaushik',
                Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0010720',
                Batch\Header::FUND_ACCOUNT_NUMBER       => '00100200300400',
                Batch\Header::FUND_ACCOUNT_VPA          => '',
                Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '',
                Batch\Header::CONTACT_NAME_2            => 'Mehul Kaushik',
                Batch\Header::PAYOUT_NARRATION          => 'test123',
                Batch\Header::PAYOUT_REFERENCE_ID       => '',
                Batch\Header::FUND_ACCOUNT_EMAIL        => '',
                Batch\Header::CONTACT_TYPE              => 'employee',
                Batch\Header::CONTACT_EMAIL_2           => 'mehul.kaushik@razorpay.com',
                Batch\Header::CONTACT_MOBILE_2          => '',
                Batch\Header::CONTACT_REFERENCE_ID      => '',
            ],
        ];

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertEquals(1000, $response['total_payout_amount']);

        $fileContent = file($response['signed_url']);

        $expectedHeaderRow =  'Error Description,RazorpayX Account Number,Payout Amount (in Rupees),Payout Currency,Payout Mode,'.
            'Payout Purpose,Fund Account Id,Fund Account Type,Fund Account Name,Fund Account Ifsc,Fund Account Number,'.
            'Fund Account Vpa,Fund Account Phone Number,Contact Name,Payout Narration,Payout Reference Id,Fund Account Email,' .
            'Contact Type,Contact Email,Contact Mobile,Contact Reference Id';

        $expectedDataRow = ',2323230041626905,10,INR,NEFT,refund,,bank_account,Mehul Kaushik,SBIN0010720,00100200300400,,' .
            ',Mehul Kaushik,test123,,,employee,mehul.kaushik@razorpay.com,,';

        $this->assertEquals($expectedHeaderRow, trim($fileContent[0]));
        $this->assertEquals($expectedDataRow, trim($fileContent[1]));
    }

    public function testValidateBatchPayoutsCSVForAmazonPayPhoneNumberWithoutExtension()
    {
        $entries = [
            [
                Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                Batch\Header::PAYOUT_AMOUNT_RUPEES      => 10,
                Batch\Header::PAYOUT_CURRENCY           => 'INR',
                Batch\Header::PAYOUT_MODE               => 'amazonpay',
                Batch\Header::PAYOUT_PURPOSE            => 'refund',
                Batch\Header::FUND_ACCOUNT_ID           => '',
                Batch\Header::FUND_ACCOUNT_TYPE         => 'wallet',
                Batch\Header::FUND_ACCOUNT_NAME         => 'Mehul Kaushik',
                Batch\Header::FUND_ACCOUNT_IFSC         => '',
                Batch\Header::FUND_ACCOUNT_NUMBER       => '',
                Batch\Header::FUND_ACCOUNT_VPA          => '',
                Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '8124632237',
                Batch\Header::CONTACT_NAME_2            => 'Mehul Kaushik',
                Batch\Header::PAYOUT_NARRATION          => 'test123',
                Batch\Header::PAYOUT_REFERENCE_ID       => '',
                Batch\Header::FUND_ACCOUNT_EMAIL        => 'sample@example.com',
                Batch\Header::CONTACT_TYPE              => 'employee',
                Batch\Header::CONTACT_EMAIL_2           => 'mehul.kaushik@razorpay.com',
                Batch\Header::CONTACT_MOBILE_2          => '',
                Batch\Header::CONTACT_REFERENCE_ID      => '',
                Batch\Header::NOTES_CODE                => 'test',
                Batch\Header::NOTES_PLACE               => 'Bangalore'
            ],
        ];

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertEquals(1000, $response['total_payout_amount']);

        $fileContent = file($response['signed_url']);

        $expectedHeaderRow =  'Error Description,RazorpayX Account Number,Payout Amount (in Rupees),Payout Currency,Payout Mode,'.
            'Payout Purpose,Fund Account Id,Fund Account Type,Fund Account Name,Fund Account Ifsc,Fund Account Number,'.
            'Fund Account Vpa,Fund Account Phone Number,Contact Name,Payout Narration,Payout Reference Id,Fund Account Email,Contact Type,' .
            'Contact Email,Contact Mobile,Contact Reference Id,notes[code],notes[place]';

        $expectedDataRow = ',2323230041626905,10,INR,amazonpay,refund,,wallet,Mehul Kaushik,,,,8124632237,' .
            'Mehul Kaushik,test123,,sample@example.com,employee,mehul.kaushik@razorpay.com,,,test,Bangalore';

        $this->assertEquals($expectedHeaderRow, trim($fileContent[0]));
        $this->assertEquals($expectedDataRow, trim($fileContent[1]));
    }

    public function testValidateBatchPayoutsCSVForAmazonPayPhoneNumberWithExtension()
    {
        $entries = [
            [
                Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                Batch\Header::PAYOUT_AMOUNT_RUPEES      => 10,
                Batch\Header::PAYOUT_CURRENCY           => 'INR',
                Batch\Header::PAYOUT_MODE               => 'amazonpay',
                Batch\Header::PAYOUT_PURPOSE            => 'refund',
                Batch\Header::FUND_ACCOUNT_ID           => '',
                Batch\Header::FUND_ACCOUNT_TYPE         => 'wallet',
                Batch\Header::FUND_ACCOUNT_NAME         => 'Mehul Kaushik',
                Batch\Header::FUND_ACCOUNT_IFSC         => '',
                Batch\Header::FUND_ACCOUNT_NUMBER       => '',
                Batch\Header::FUND_ACCOUNT_VPA          => '',
                Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '+918124632237',
                Batch\Header::CONTACT_NAME_2            => 'Mehul Kaushik',
                Batch\Header::PAYOUT_NARRATION          => 'test123',
                Batch\Header::PAYOUT_REFERENCE_ID       => '',
                Batch\Header::FUND_ACCOUNT_EMAIL        => 'sample@example.com',
                Batch\Header::CONTACT_TYPE              => 'employee',
                Batch\Header::CONTACT_EMAIL_2           => 'mehul.kaushik@razorpay.com',
                Batch\Header::CONTACT_MOBILE_2          => '',
                Batch\Header::CONTACT_REFERENCE_ID      => '',
                Batch\Header::NOTES_CODE                => 'test',
                Batch\Header::NOTES_PLACE               => 'Bangalore'
            ],
        ];

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertEquals(1000, $response['total_payout_amount']);

        $fileContent = file($response['signed_url']);

        $expectedHeaderRow =  'Error Description,RazorpayX Account Number,Payout Amount (in Rupees),Payout Currency,Payout Mode,'.
            'Payout Purpose,Fund Account Id,Fund Account Type,Fund Account Name,Fund Account Ifsc,Fund Account Number,'.
            'Fund Account Vpa,Fund Account Phone Number,Contact Name,Payout Narration,Payout Reference Id,Fund Account Email,Contact Type,' .
            'Contact Email,Contact Mobile,Contact Reference Id,notes[code],notes[place]';

        $expectedDataRow = ',2323230041626905,10,INR,amazonpay,refund,,wallet,Mehul Kaushik,,,,+918124632237,' .
            'Mehul Kaushik,test123,,sample@example.com,employee,mehul.kaushik@razorpay.com,,,test,Bangalore';

        $this->assertEquals($expectedHeaderRow, trim($fileContent[0]));
        $this->assertEquals($expectedDataRow, trim($fileContent[1]));
    }

    public function testValidateBatchPayoutsCSVForAmazonPayEmptyPhoneNumber()
    {
        $entries = [
            [
                Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                Batch\Header::PAYOUT_AMOUNT_RUPEES      => 10,
                Batch\Header::PAYOUT_CURRENCY           => 'INR',
                Batch\Header::PAYOUT_MODE               => 'amazonpay',
                Batch\Header::PAYOUT_PURPOSE            => 'refund',
                Batch\Header::FUND_ACCOUNT_ID           => '',
                Batch\Header::FUND_ACCOUNT_TYPE         => 'wallet',
                Batch\Header::FUND_ACCOUNT_NAME         => 'Mehul Kaushik',
                Batch\Header::FUND_ACCOUNT_IFSC         => '',
                Batch\Header::FUND_ACCOUNT_NUMBER       => '',
                Batch\Header::FUND_ACCOUNT_VPA          => '',
                Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '',
                Batch\Header::CONTACT_NAME_2            => 'Mehul Kaushik',
                Batch\Header::PAYOUT_NARRATION          => 'test123',
                Batch\Header::PAYOUT_REFERENCE_ID       => '',
                Batch\Header::FUND_ACCOUNT_EMAIL        => 'sample@example.com',
                Batch\Header::CONTACT_TYPE              => 'employee',
                Batch\Header::CONTACT_EMAIL_2           => 'mehul.kaushik@razorpay.com',
                Batch\Header::CONTACT_MOBILE_2          => '',
                Batch\Header::CONTACT_REFERENCE_ID      => '',
                Batch\Header::NOTES_CODE                => 'test',
                Batch\Header::NOTES_PLACE               => 'Bangalore'
            ],
        ];

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertEquals(1000, $response['total_payout_amount']);

        $fileContent = file($response['signed_url']);

        $expectedHeaderRow =  'Error Description,RazorpayX Account Number,Payout Amount (in Rupees),Payout Currency,Payout Mode,'.
            'Payout Purpose,Fund Account Id,Fund Account Type,Fund Account Name,Fund Account Ifsc,Fund Account Number,'.
            'Fund Account Vpa,Fund Account Phone Number,Contact Name,Payout Narration,Payout Reference Id,Fund Account Email,Contact Type,' .
            'Contact Email,Contact Mobile,Contact Reference Id,notes[code],notes[place]';

        $expectedDataRow = 'The fund account phone number field is required when fund account type is wallet.,2323230041626905,10,INR,amazonpay,refund,,wallet,Mehul Kaushik,,,,,' .
            'Mehul Kaushik,test123,,sample@example.com,employee,mehul.kaushik@razorpay.com,,,test,Bangalore';

        $this->assertEquals($expectedHeaderRow, trim($fileContent[0]));
        $this->assertEquals($expectedDataRow, trim($fileContent[1]));
    }



    protected function changeMerchantToExistingBulkRupeesType()
    {
        $this->changeMerchantToExistingBulkPaiseType();

        $this->ba->proxyAuth();

        $request  = [
            'method'  => 'PATCH',
            'url'     => '/payouts/bulk/amount_type'
        ];

        $this->sendRequest($request);

        $payoutAmountType = (new Payout\Service)->getSettingsAccessor($this->merchant)->get(Batch\Constants::TYPE);

        $this->assertEquals(Payout\BatchHelper::RUPEES, $payoutAmountType);
    }

    protected function changeMerchantToExistingBulkPaiseType()
    {
        $this->ba->adminAuth();

        $request  = [
            'method'  => 'POST',
            'url'     => '/payouts/bulk/amount_type',
            'content' => [
                'merchant_ids' => ['10000000000000']
            ]
        ];

        $this->sendRequest($request);

        $payoutAmountType = (new Payout\Service)->getSettingsAccessor($this->merchant)->get(Batch\Constants::TYPE);

        $this->assertEquals(Payout\BatchHelper::PAISE, $payoutAmountType);
    }

    protected function turnBulkRolloutExperimentOff()
    {
        $this->mockRazorxTreatment(
            'yesbank',
            'off',
            'off',
            'off',
            'off',
            'on',
            'on',
            'off',
            'on',
            'on',
            'off',
            'control');
    }
}
