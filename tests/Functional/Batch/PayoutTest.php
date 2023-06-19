<?php

namespace RZP\Tests\Functional\Batch;

use RZP\Http\RequestHeader;
use PhpOffice\PhpSpreadsheet\IOFactory;

use RZP\Models\Admin;
use RZP\Models\Batch;
use RZP\Models\Payout;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Feature\Constants as Feature;
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

    /**
     * This test function validates all errors including errors arising from custom rules.
     * These are then all shown in the error description column corresponding to each row.
     */
    public function testValidateErrorDescriptionsInBatchPayoutsCSV()
    {
        $this->fixtures->merchant->addFeatures([Feature::ALLOW_COMPLETE_ERROR_DESC]);

        $entries = [
            [
                // This entry has account number with multiple rules violation in RAZORPAYX_ACCOUNT_NUMBER field
                // and all errors are shown in the error description.
                Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '232!',
                Batch\Header::PAYOUT_AMOUNT_RUPEES      => 10,
                Batch\Header::PAYOUT_CURRENCY           => 'INR',
                Batch\Header::PAYOUT_MODE               => 'NEFT',
                Batch\Header::PAYOUT_PURPOSE            => 'refund',
                Batch\Header::FUND_ACCOUNT_ID           => '',
                Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account',
                Batch\Header::FUND_ACCOUNT_NAME         => 'Chirag Chiranjib',
                Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0010720',
                Batch\Header::FUND_ACCOUNT_NUMBER       => '100200300400',
                Batch\Header::FUND_ACCOUNT_VPA          => '',
                Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '',
                Batch\Header::CONTACT_NAME_2            => "Chirag Chiranjib",
                Batch\Header::PAYOUT_NARRATION          => 'NarrationTest',
                Batch\Header::PAYOUT_REFERENCE_ID       => '',
                Batch\Header::FUND_ACCOUNT_EMAIL        => '',
                Batch\Header::CONTACT_TYPE              => 'employee',
                Batch\Header::CONTACT_EMAIL_2           => "chirag.chiranjib@razorpay.com",
                Batch\Header::CONTACT_MOBILE_2          => '',
                Batch\Header::CONTACT_REFERENCE_ID      => '',
                Batch\Header::NOTES_CODE                => 'test',
                Batch\Header::NOTES_PLACE               => 'Bhubaneswar'
            ],
            [
                // This entry has invalid account number with multiple rules violation, has fund account
                // type violation and violation of custom rules corresponding to non-utf8 characters
                // in contact email and contact name.
                // The errors for all of this are shown in the error description.
                Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '232!',
                Batch\Header::PAYOUT_AMOUNT_RUPEES      => 20,
                Batch\Header::PAYOUT_CURRENCY           => 'INR',
                Batch\Header::PAYOUT_MODE               => 'NEFT',
                Batch\Header::PAYOUT_PURPOSE            => 'refund',
                Batch\Header::FUND_ACCOUNT_ID           => '',
                Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account_yo',
                Batch\Header::FUND_ACCOUNT_NAME         => 'Chirag Chiranjib',
                Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0010720',
                Batch\Header::FUND_ACCOUNT_NUMBER       => '100200300400',
                Batch\Header::FUND_ACCOUNT_VPA          => '',
                Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '',
                Batch\Header::CONTACT_NAME_2            => "Chirag \xff Chiranjib",
                Batch\Header::PAYOUT_NARRATION          => 'NarrationTest',
                Batch\Header::PAYOUT_REFERENCE_ID       => '',
                Batch\Header::FUND_ACCOUNT_EMAIL        => '',
                Batch\Header::CONTACT_TYPE              => 'employeeNo',
                Batch\Header::CONTACT_EMAIL_2           => "chirag\xffchiranjib@razorpay.com",
                Batch\Header::CONTACT_MOBILE_2          => '',
                Batch\Header::CONTACT_REFERENCE_ID      => '',
                Batch\Header::NOTES_CODE                => 'test',
                Batch\Header::NOTES_PLACE               => 'Bhubaneswar'
            ],
            [
                // This entry has invalid utf-8 character in both CONTACT_NAME_2 field and CONTACT_EMAIL_2 field
                // and the errors for them are shown in the error description.
                Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                Batch\Header::PAYOUT_AMOUNT_RUPEES      => 30,
                Batch\Header::PAYOUT_CURRENCY           => 'INR',
                Batch\Header::PAYOUT_MODE               => 'NEFT',
                Batch\Header::PAYOUT_PURPOSE            => 'refund',
                Batch\Header::FUND_ACCOUNT_ID           => '',
                Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account',
                Batch\Header::FUND_ACCOUNT_NAME         => 'Chirag Chiranjib',
                Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0010720',
                Batch\Header::FUND_ACCOUNT_NUMBER       => '100200300400',
                Batch\Header::FUND_ACCOUNT_VPA          => '',
                Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '',
                Batch\Header::CONTACT_NAME_2            => "Chirag \xf8 Chiranjib",
                Batch\Header::PAYOUT_NARRATION          => 'NarrationTest',
                Batch\Header::PAYOUT_REFERENCE_ID       => '',
                Batch\Header::FUND_ACCOUNT_EMAIL        => '',
                Batch\Header::CONTACT_TYPE              => 'employee',
                Batch\Header::CONTACT_EMAIL_2           => "chirag\xffchiranjib@razorpay.com",
                Batch\Header::CONTACT_MOBILE_2          => '',
                Batch\Header::CONTACT_REFERENCE_ID      => '',
                Batch\Header::NOTES_CODE                => 'test',
                Batch\Header::NOTES_PLACE               => 'Bhubaneswar'
            ],
            [                     // This entry has no errors and So this entry will be parsed successfully.
                Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                Batch\Header::PAYOUT_AMOUNT_RUPEES      => 0,
                Batch\Header::PAYOUT_CURRENCY           => 'INR',
                Batch\Header::PAYOUT_MODE               => 'NEFT',
                Batch\Header::PAYOUT_PURPOSE            => 'refund',
                Batch\Header::FUND_ACCOUNT_ID           => '',
                Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account',
                Batch\Header::FUND_ACCOUNT_NAME         => 'Chirag Chiranjib',
                Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0010720',
                Batch\Header::FUND_ACCOUNT_NUMBER       => '100200300400',
                Batch\Header::FUND_ACCOUNT_VPA          => '',
                Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '',
                Batch\Header::CONTACT_NAME_2            => "Chirag Chiranjib",
                Batch\Header::PAYOUT_NARRATION          => 'NarrationTest',
                Batch\Header::PAYOUT_REFERENCE_ID       => '',
                Batch\Header::FUND_ACCOUNT_EMAIL        => '',
                Batch\Header::CONTACT_TYPE              => 'employee',
                Batch\Header::CONTACT_EMAIL_2           => "chirag.chiranjib@razorpay.com",
                Batch\Header::CONTACT_MOBILE_2          => '',
                Batch\Header::CONTACT_REFERENCE_ID      => '',
                Batch\Header::NOTES_CODE                => 'test',
                Batch\Header::NOTES_PLACE               => 'Bhubaneswar'
            ],
            [
                // This entry has no errors and So this entry will be parsed successfully.
                Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                Batch\Header::PAYOUT_AMOUNT_RUPEES      => 40,
                Batch\Header::PAYOUT_CURRENCY           => 'INR',
                Batch\Header::PAYOUT_MODE               => 'NEFT',
                Batch\Header::PAYOUT_PURPOSE            => 'refund',
                Batch\Header::FUND_ACCOUNT_ID           => '',
                Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account',
                Batch\Header::FUND_ACCOUNT_NAME         => 'Chirag Chiranjib',
                Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0010720',
                Batch\Header::FUND_ACCOUNT_NUMBER       => '100200300400',
                Batch\Header::FUND_ACCOUNT_VPA          => '',
                Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '',
                Batch\Header::CONTACT_NAME_2            => "Chirag Chiranjib",
                Batch\Header::PAYOUT_NARRATION          => 'NarrationTest',
                Batch\Header::PAYOUT_REFERENCE_ID       => '',
                Batch\Header::FUND_ACCOUNT_EMAIL        => '',
                Batch\Header::CONTACT_TYPE              => 'employee',
                Batch\Header::CONTACT_EMAIL_2           => "chirag.chiranjib@razorpay.com",
                Batch\Header::CONTACT_MOBILE_2          => '',
                Batch\Header::CONTACT_REFERENCE_ID      => '',
                Batch\Header::NOTES_CODE                => 'test',
                Batch\Header::NOTES_PLACE               => 'Bhubaneswar'
            ],
        ];
        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $fileContent = file($response['signed_url']);

        $this->assertEquals(10000, $response['total_payout_amount']);

        $expectedHeaderRow = 'Error Description,RazorpayX Account Number,Payout Amount (in Rupees),Payout Currency,'.
                             'Payout Mode,Payout Purpose,Fund Account Id,Fund Account Type,Fund Account Name,Fund Account Ifsc,'.
                             'Fund Account Number,Fund Account Vpa,Fund Account Phone Number,Contact Name,Payout Narration,'.
                             'Payout Reference Id,Fund Account Email,Contact Type,Contact Email,Contact Mobile,Contact Reference Id,'.
                             'notes[code],notes[place]';

        $expectedDataRow = [
            // 1st Row : Error Description includes all errors
            "\"The razorpay x account number may only contain alphabets, digits and spaces.".
            "\vThe razorpay x account number must be between 5 and 22 characters.\",232!,10,INR,NEFT,refund,".
            ",bank_account,Chirag Chiranjib,SBIN0010720,100200300400,,,Chirag Chiranjib,NarrationTest,,,employee,".
            "chirag.chiranjib@razorpay.com,,,test,Bhubaneswar",
            // 2nd Row : Custom errors with Laravel defined error
            "\"The razorpay x account number may only contain alphabets, digits and spaces.".
            "\vThe razorpay x account number must be between 5 and 22 characters.".
            "\vThe selected fund account type is invalid.".
            "\vInvalid encoding of Contact Name. Non UTF-8 character(s) found.".
            "\vInvalid encoding of Contact Email. Non UTF-8 character(s) found.\",232!,20,INR,NEFT,refund,".
            ",bank_account_yo,Chirag Chiranjib,SBIN0010720,100200300400,,,Chirag \xff Chiranjib,NarrationTest,,,employeeNo,".
            "chirag\xffchiranjib@razorpay.com,,,test,Bhubaneswar",
            // Third Row : Only Custom errors
            "Invalid encoding of Contact Name. Non UTF-8 character(s) found.".
            "\vInvalid encoding of Contact Email. Non UTF-8 character(s) found.,2323230041626905,30,INR,NEFT".
            ",refund,,bank_account,Chirag Chiranjib,SBIN0010720,100200300400,,,Chirag \xf8 Chiranjib,".
            "NarrationTest,,,employee,chirag\xffchiranjib@razorpay.com,,,test,Bhubaneswar",
            // Fourth Row : Show payout amount error
            "The payout amount (in rupees) may not be less than 1.00".
            ",2323230041626905,0,INR,NEFT,refund,,bank_account,Chirag Chiranjib,SBIN0010720,100200300400,,,Chirag Chiranjib,".
            "NarrationTest,,,employee,chirag.chiranjib@razorpay.com,,,test,Bhubaneswar",
            // Fifth Row : No errors
            ",2323230041626905,40,INR,NEFT,refund,,bank_account,Chirag Chiranjib,SBIN0010720,100200300400,,,Chirag Chiranjib,".
            "NarrationTest,,,employee,chirag.chiranjib@razorpay.com,,,test,Bhubaneswar",
        ];

        $this->assertEquals($expectedHeaderRow, trim($fileContent[0]));
        $this->assertEquals($expectedDataRow[0], trim($fileContent[1]));
        $this->assertEquals($expectedDataRow[1], trim($fileContent[2]));
        $this->assertEquals($expectedDataRow[2], trim($fileContent[3]));
        $this->assertEquals($expectedDataRow[3], trim($fileContent[4]));
    }
    /**
     * This test function validates that Contact Name and Contact Email attributes are
     * UTF-8 encoded while importing bulk contacts.
     */
    public function testValidateUtf8EncodingInBatchFundAccountsCSV()
    {
        $this->fixtures->merchant->addFeatures([Feature::ALLOW_COMPLETE_ERROR_DESC]);

        $entries = [
            [
                // this entry has non utf-8 character in CONTACT_NAME_2 field
                // and won't be parsed successfully.
                Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account',
                Batch\Header::FUND_ACCOUNT_NAME         => 'Sagnik Saha',
                Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0007679',
                Batch\Header::FUND_ACCOUNT_NUMBER       => '200200200200',
                Batch\Header::FUND_ACCOUNT_VPA          => '',
                Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '',
                Batch\Header::FUND_ACCOUNT_PROVIDER     => '',
                Batch\Header::FUND_ACCOUNT_EMAIL        => '',
                Batch\Header::CONTACT_ID                => '',
                Batch\Header::CONTACT_TYPE              => 'vendor',
                Batch\Header::CONTACT_NAME_2            => "Sagnik \xff Saha",
                Batch\Header::CONTACT_EMAIL_2           => "sagnik3012@gmail.com",
                Batch\Header::CONTACT_MOBILE_2          => '9876543210',
                Batch\Header::CONTACT_REFERENCE_ID      => '',
                Batch\Header::NOTES_CODE                => 'test',
                Batch\Header::NOTES_PLACE               => 'Kolkata'
            ],
            [
                // this entry has non utf-8 character in CONTACT_EMAIL_2 field
                // and won't be parsed successfully.
                Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account',
                Batch\Header::FUND_ACCOUNT_NAME         => 'Sagnik Saha',
                Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0007679',
                Batch\Header::FUND_ACCOUNT_NUMBER       => '200200200200',
                Batch\Header::FUND_ACCOUNT_VPA          => '',
                Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '',
                Batch\Header::FUND_ACCOUNT_PROVIDER     => '',
                Batch\Header::FUND_ACCOUNT_EMAIL        => '',
                Batch\Header::CONTACT_ID                => '',
                Batch\Header::CONTACT_TYPE              => 'vendor',
                Batch\Header::CONTACT_NAME_2            => "Sagnik Saha",
                Batch\Header::CONTACT_EMAIL_2           => "sagnik\xf83012@gmail.com",
                Batch\Header::CONTACT_MOBILE_2          => '9876543210',
                Batch\Header::CONTACT_REFERENCE_ID      => '',
                Batch\Header::NOTES_CODE                => 'test',
                Batch\Header::NOTES_PLACE               => 'Kolkata'
            ],
            [
                // this entry has invalid utf-8 character in both CONTACT_NAME_2 field and CONTACT_EMAIL_2 field
                // and won't be parsed successfully.
                Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account',
                Batch\Header::FUND_ACCOUNT_NAME         => 'Sagnik Saha',
                Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0007679',
                Batch\Header::FUND_ACCOUNT_NUMBER       => '200200200200',
                Batch\Header::FUND_ACCOUNT_VPA          => '',
                Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '',
                Batch\Header::FUND_ACCOUNT_PROVIDER     => '',
                Batch\Header::FUND_ACCOUNT_EMAIL        => '',
                Batch\Header::CONTACT_ID                => '',
                Batch\Header::CONTACT_TYPE              => 'vendor',
                Batch\Header::CONTACT_NAME_2            => "Sagnik \xff Saha",
                Batch\Header::CONTACT_EMAIL_2           => "sagnik\xf83012@gmail.com",
                Batch\Header::CONTACT_MOBILE_2          => '9876543210',
                Batch\Header::CONTACT_REFERENCE_ID      => '',
                Batch\Header::NOTES_CODE                => 'test',
                Batch\Header::NOTES_PLACE               => 'Kolkata'
            ],
            [
                // this entry has valid utf-8 characters in both CONTACT_NAME_2 field and CONTACT_EMAIL_2 field
                // So this entry WILL be parsed successfully.
                Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account',
                Batch\Header::FUND_ACCOUNT_NAME         => 'Sagnik Saha',
                Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0007679',
                Batch\Header::FUND_ACCOUNT_NUMBER       => '200200200200',
                Batch\Header::FUND_ACCOUNT_VPA          => '',
                Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '',
                Batch\Header::FUND_ACCOUNT_PROVIDER     => '',
                Batch\Header::FUND_ACCOUNT_EMAIL        => '',
                Batch\Header::CONTACT_ID                => '',
                Batch\Header::CONTACT_TYPE              => 'vendor',
                Batch\Header::CONTACT_NAME_2            => "Sagnik Saha",
                Batch\Header::CONTACT_EMAIL_2           => "sagnik3012@gmail.com",
                Batch\Header::CONTACT_MOBILE_2          => '9876543210',
                Batch\Header::CONTACT_REFERENCE_ID      => '',
                Batch\Header::NOTES_CODE                => 'test',
                Batch\Header::NOTES_PLACE               => 'Kolkata'
            ],
        ];
        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $fileContent = file($response['signed_url']);

        $expectedHeaderRow = "Fund Account Type,Fund Account Name,Fund Account Ifsc,Fund Account Number,".
            "Fund Account Vpa,Fund Account Provider,Fund Account Phone Number,Fund Account Email,Contact Id,".
            "Contact Type,Contact Name,Contact Email,Contact Mobile,Contact Reference Id,notes[code],notes[place],".
            "Error Code,Error Description";
        $expectedDataRow = [
            "bank_account,Sagnik Saha,SBIN0007679,200200200200,,,,,,vendor,Sagnik \xff Saha,".
            "sagnik3012@gmail.com,9876543210,,test,Kolkata,BAD_REQUEST_ERROR,".
            "Invalid encoding of Contact Name. Non UTF-8 character(s) found.",
            "bank_account,Sagnik Saha,SBIN0007679,200200200200,,,,,,vendor,Sagnik Saha,".
            "sagnik\xf83012@gmail.com,9876543210,,test,Kolkata,BAD_REQUEST_ERROR,".
            "Invalid encoding of Contact Email. Non UTF-8 character(s) found.",
            // Error Description in the last data row should state error in Contact Name only.
            "bank_account,Sagnik Saha,SBIN0007679,200200200200,,,,,,vendor,Sagnik \xff Saha,".
            "sagnik\xf83012@gmail.com,9876543210,,test,Kolkata,BAD_REQUEST_ERROR,".
            "Invalid encoding of Contact Name. Non UTF-8 character(s) found.".
            "\vInvalid encoding of Contact Email. Non UTF-8 character(s) found.",
            // No error for the last dat entry.
            "bank_account,Sagnik Saha,SBIN0007679,200200200200,,,,,,vendor,Sagnik Saha,sagnik3012@gmail.com,".
            "9876543210,,test,Kolkata,,"
        ];

        $this->assertEquals($expectedHeaderRow, trim($fileContent[0]));
        $this->assertEquals($expectedDataRow[0], trim($fileContent[1]));
        $this->assertEquals($expectedDataRow[1], trim($fileContent[2]));

        // this asserts error in CONTACT_NAME_2 even though there is non utf-8 character in CONTACT_EMAIL_2
        $this->assertEquals($expectedDataRow[2], trim($fileContent[3]));
        $this->assertEquals($expectedDataRow[3], trim($fileContent[4]));

    }

    /**
     * This test function validates that Contact Name and Contact Email attributes are
     * UTF-8 encoded while issuing bulk payouts.
     */
    public function testValidateUtf8EncodingInBatchPayoutsCSV()
    {
        $entries = [
            [
                // This entry has non utf-8 character in CONTACT_NAME_2 field
                // and won't be parsed successfully.
                Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2!323230041626905',
                Batch\Header::PAYOUT_AMOUNT_RUPEES      => 10,
                Batch\Header::PAYOUT_CURRENCY           => 'INRi',
                Batch\Header::PAYOUT_MODE               => 'NEFT',
                Batch\Header::PAYOUT_PURPOSE            => 'refund',
                Batch\Header::FUND_ACCOUNT_ID           => '',
                Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account',
                Batch\Header::FUND_ACCOUNT_NAME         => 'Sagnik Saha',
                Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0010720',
                Batch\Header::FUND_ACCOUNT_NUMBER       => '100200300400',
                Batch\Header::FUND_ACCOUNT_VPA          => '',
                Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '',
                Batch\Header::CONTACT_NAME_2            => "Sagnik Saha",
                Batch\Header::PAYOUT_NARRATION          => 'NarrationTest',
                Batch\Header::PAYOUT_REFERENCE_ID       => '',
                Batch\Header::FUND_ACCOUNT_EMAIL        => '',
                Batch\Header::CONTACT_TYPE              => 'employee',
                Batch\Header::CONTACT_EMAIL_2           => "sagnik.saha@razorpay.com",
                Batch\Header::CONTACT_MOBILE_2          => '',
                Batch\Header::CONTACT_REFERENCE_ID      => '',
                Batch\Header::NOTES_CODE                => 'test',
                Batch\Header::NOTES_PLACE               => 'Kolkata'
            ],
            [
                // This entry has non utf-8 character in CONTACT_EMAIL_2 field
                // and won't be parsed successfully.
                Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                Batch\Header::PAYOUT_AMOUNT_RUPEES      => 20,
                Batch\Header::PAYOUT_CURRENCY           => 'INR',
                Batch\Header::PAYOUT_MODE               => 'NEFT',
                Batch\Header::PAYOUT_PURPOSE            => 'refund',
                Batch\Header::FUND_ACCOUNT_ID           => '',
                Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account',
                Batch\Header::FUND_ACCOUNT_NAME         => 'Sagnik Saha',
                Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0010720',
                Batch\Header::FUND_ACCOUNT_NUMBER       => '100200300400',
                Batch\Header::FUND_ACCOUNT_VPA          => '',
                Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '',
                Batch\Header::CONTACT_NAME_2            => "Sagnik Saha",
                Batch\Header::PAYOUT_NARRATION          => 'NarrationTest',
                Batch\Header::PAYOUT_REFERENCE_ID       => '',
                Batch\Header::FUND_ACCOUNT_EMAIL        => '',
                Batch\Header::CONTACT_TYPE              => 'employee',
                Batch\Header::CONTACT_EMAIL_2           => "sagnik\xffsaha@razorpay.com",
                Batch\Header::CONTACT_MOBILE_2          => '',
                Batch\Header::CONTACT_REFERENCE_ID      => '',
                Batch\Header::NOTES_CODE                => 'test',
                Batch\Header::NOTES_PLACE               => 'Kolkata'
            ],
            [
                // This entry has invalid utf-8 character in both CONTACT_NAME_2 field and CONTACT_EMAIL_2 field
                // and won't be parsed successfully.
                Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                Batch\Header::PAYOUT_AMOUNT_RUPEES      => 30,
                Batch\Header::PAYOUT_CURRENCY           => 'INR',
                Batch\Header::PAYOUT_MODE               => 'NEFT',
                Batch\Header::PAYOUT_PURPOSE            => 'refund',
                Batch\Header::FUND_ACCOUNT_ID           => '',
                Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account',
                Batch\Header::FUND_ACCOUNT_NAME         => 'Sagnik Saha',
                Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0010720',
                Batch\Header::FUND_ACCOUNT_NUMBER       => '100200300400',
                Batch\Header::FUND_ACCOUNT_VPA          => '',
                Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '',
                Batch\Header::CONTACT_NAME_2            => "Sagnik \xf8 Saha",
                Batch\Header::PAYOUT_NARRATION          => 'NarrationTest',
                Batch\Header::PAYOUT_REFERENCE_ID       => '',
                Batch\Header::FUND_ACCOUNT_EMAIL        => '',
                Batch\Header::CONTACT_TYPE              => 'employee',
                Batch\Header::CONTACT_EMAIL_2           => "sagnik\xffsaha@razorpay.com",
                Batch\Header::CONTACT_MOBILE_2          => '',
                Batch\Header::CONTACT_REFERENCE_ID      => '',
                Batch\Header::NOTES_CODE                => 'test',
                Batch\Header::NOTES_PLACE               => 'Kolkata'
            ],
            [
                // This entry has valid utf-8 character in both CONTACT_NAME_2 field and CONTACT_EMAIL_2 field.
                // So this entry WILL be parsed successfully.
                Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                Batch\Header::PAYOUT_AMOUNT_RUPEES      => 40,
                Batch\Header::PAYOUT_CURRENCY           => 'INR',
                Batch\Header::PAYOUT_MODE               => 'NEFT',
                Batch\Header::PAYOUT_PURPOSE            => 'refund',
                Batch\Header::FUND_ACCOUNT_ID           => '',
                Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account',
                Batch\Header::FUND_ACCOUNT_NAME         => 'Sagnik Saha',
                Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0010720',
                Batch\Header::FUND_ACCOUNT_NUMBER       => '100200300400',
                Batch\Header::FUND_ACCOUNT_VPA          => '',
                Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '',
                Batch\Header::CONTACT_NAME_2            => "Sagnik Saha",
                Batch\Header::PAYOUT_NARRATION          => 'NarrationTest',
                Batch\Header::PAYOUT_REFERENCE_ID       => '',
                Batch\Header::FUND_ACCOUNT_EMAIL        => '',
                Batch\Header::CONTACT_TYPE              => 'employee',
                Batch\Header::CONTACT_EMAIL_2           => "sagnik.saha@razorpay.com",
                Batch\Header::CONTACT_MOBILE_2          => '',
                Batch\Header::CONTACT_REFERENCE_ID      => '',
                Batch\Header::NOTES_CODE                => 'test',
                Batch\Header::NOTES_PLACE               => 'Kolkata'
            ],
        ];
        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $fileContent = file($response['signed_url']);

        $this->assertEquals(10000, $response['total_payout_amount']);

        $expectedHeaderRow = 'Error Description,RazorpayX Account Number,Payout Amount (in Rupees),Payout Currency,'.
            'Payout Mode,Payout Purpose,Fund Account Id,Fund Account Type,Fund Account Name,Fund Account Ifsc,'.
            'Fund Account Number,Fund Account Vpa,Fund Account Phone Number,Contact Name,Payout Narration,'.
            'Payout Reference Id,Fund Account Email,Contact Type,Contact Email,Contact Mobile,Contact Reference Id,'.
            'notes[code],notes[place]';

        $expectedDataRow = [
            "\"The razorpay x account number may only contain alphabets, digits and spaces.\"".",2!323230041626905,10,INRi,".
            "NEFT,refund,,bank_account,Sagnik Saha,SBIN0010720,100200300400,," .
            ",Sagnik Saha,NarrationTest,,,employee,sagnik.saha@razorpay.com,,,test,Kolkata",
            "Invalid encoding of Contact Email. Non UTF-8 character(s) found.,2323230041626905,20,INR,".
            "NEFT,refund,,bank_account,Sagnik Saha,SBIN0010720,100200300400,," .
            ",Sagnik Saha,NarrationTest,,,employee,sagnik\xffsaha@razorpay.com,,,test,Kolkata",
            // Error Description in the third data row should state error in Contact Name only.
            "Invalid encoding of Contact Name. Non UTF-8 character(s) found.".
            ",2323230041626905,30,INR,NEFT,refund,,bank_account,Sagnik Saha,SBIN0010720,100200300400,," .
            ",Sagnik \xf8 Saha,NarrationTest,,,employee,sagnik\xffsaha@razorpay.com,,,test,Kolkata",
            // No error in the last data row
            ",2323230041626905,40,INR,NEFT,refund,,bank_account,Sagnik Saha,SBIN0010720,100200300400,,,Sagnik Saha,".
            "NarrationTest,,,employee,sagnik.saha@razorpay.com,,,test,Kolkata"
        ];

        $this->assertEquals($expectedHeaderRow, trim($fileContent[0]));
        $this->assertEquals($expectedDataRow[0], trim($fileContent[1]));
        $this->assertEquals($expectedDataRow[1], trim($fileContent[2]));
        // This asserts error in CONTACT_NAME_2 even though there is non utf-8 character in CONTACT_EMAIL_2
        $this->assertEquals($expectedDataRow[2], trim($fileContent[3]));
        $this->assertEquals($expectedDataRow[3], trim($fileContent[4]));
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

    public function testValidateBatchPayoutsCSVForAmazonPayPhoneNumberWithExtensionAndFormulaeInjection()
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
                Batch\Header::FUND_ACCOUNT_NAME         => '=SUM(A1,A2)',
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

        $expectedDataRow = ',2323230041626905,10,INR,amazonpay,refund,,wallet,"\'=SUM(A1,A2)",,,,+918124632237,' .
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

    // This test is to ensure that there are no floating point inaccuracy losses when converting rupees to paise.
    public function testLedgerTransactorEventAmounts()
    {
        $customTestCase = $this->testData[__FUNCTION__];

        $headers = [
            'HTTP_' . RequestHeader::X_Batch_Id => 'C3fzDCb4hA4F6b',
        ];

        // Add idempotency header to test data
        $customTestCase['request']['server'] = $headers;

        $this->ba->batchAuth();

        // We are not creating a payouts batch entity, to simulate a dashboard based bulk payout request

        $this->startTest($customTestCase);

        $payout = $this->getDbLastEntity('payout');
        $txn = $this->getDbLastEntity('transaction');

        $this->assertEquals($payout->getAmount(), $txn->getAmount() - $txn->getFee());
    }

    /**
     * This test asserts that balance is fetched from Ledger instead of using balance present in API DB
     * for bulk payout amount validation for merchant with the feature ledger_reverse_shadow enabled
     */
    public function testBalanceFetchedFromLedgerForBulkPayoutAmountValidationOnReverseShadow()
    {
        $this->fixtures->merchant->addFeatures([Feature::LEDGER_REVERSE_SHADOW]);

        $this->app['config']->set('applications.ledger.enabled', true);

        //Make balance low so that bulk validation fails if API balance is used
        $this->fixtures->edit('balance', $this->bankingBalance->getId(), ['balance' => 100]);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        //Mock ledger to return sufficient balance to pass amount validation.
        $mockLedger->shouldReceive('fetchMerchantAccounts')
                   ->andReturn([
                                   'code' => 200,
                                   'body' => [
                                       "merchant_id"      => "10000000000000",
                                       "merchant_balance" => [
                                           "balance"      => "6000.000000",
                                           "min_balance"  => "10000.000000"
                                       ],
                                       "reward_balance"  => [
                                           "balance"     => "20.000000",
                                           "min_balance" => "-20.000000"
                                       ],
                                   ],
                               ]);

        $entries = [
            [
                Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                Batch\Header::PAYOUT_AMOUNT_RUPEES      => 10,
                Batch\Header::PAYOUT_CURRENCY           => 'INR',
                Batch\Header::PAYOUT_MODE               => 'NEFT',
                Batch\Header::PAYOUT_PURPOSE            => 'refund',
                Batch\Header::FUND_ACCOUNT_ID           => '',
                Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account',
                Batch\Header::FUND_ACCOUNT_NAME         => 'Sagnik Saha',
                Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0010720',
                Batch\Header::FUND_ACCOUNT_NUMBER       => '100200300400',
                Batch\Header::FUND_ACCOUNT_VPA          => '',
                Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '',
                Batch\Header::CONTACT_NAME_2            => "Sagnik Saha",
                Batch\Header::PAYOUT_NARRATION          => 'NarrationTest',
                Batch\Header::PAYOUT_REFERENCE_ID       => '',
                Batch\Header::FUND_ACCOUNT_EMAIL        => '',
                Batch\Header::CONTACT_TYPE              => 'employee',
                Batch\Header::CONTACT_EMAIL_2           => "sagnik.saha@razorpay.com",
                Batch\Header::CONTACT_MOBILE_2          => '',
                Batch\Header::CONTACT_REFERENCE_ID      => '',
                Batch\Header::NOTES_CODE                => 'test',
                Batch\Header::NOTES_PLACE               => 'Kolkata'
            ],
            [
                Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                Batch\Header::PAYOUT_AMOUNT_RUPEES      => 40,
                Batch\Header::PAYOUT_CURRENCY           => 'INR',
                Batch\Header::PAYOUT_MODE               => 'NEFT',
                Batch\Header::PAYOUT_PURPOSE            => 'refund',
                Batch\Header::FUND_ACCOUNT_ID           => '',
                Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account',
                Batch\Header::FUND_ACCOUNT_NAME         => 'Sagnik S',
                Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0010720',
                Batch\Header::FUND_ACCOUNT_NUMBER       => '100200300400',
                Batch\Header::FUND_ACCOUNT_VPA          => '',
                Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '',
                Batch\Header::CONTACT_NAME_2            => "Sagnik Saha",
                Batch\Header::PAYOUT_NARRATION          => 'NarrationTest',
                Batch\Header::PAYOUT_REFERENCE_ID       => '',
                Batch\Header::FUND_ACCOUNT_EMAIL        => '',
                Batch\Header::CONTACT_TYPE              => 'employee',
                Batch\Header::CONTACT_EMAIL_2           => "sagnik.s@razorpay.com",
                Batch\Header::CONTACT_MOBILE_2          => '',
                Batch\Header::CONTACT_REFERENCE_ID      => '',
                Batch\Header::NOTES_CODE                => 'test',
                Batch\Header::NOTES_PLACE               => 'Kolkata'
            ],
        ];
        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertEquals(5000, $response['total_payout_amount']);
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

    public function testValidateBulkPayoutsWithOldTemplate()
    {
        $this->fixtures->merchant->addFeatures([Feature::ALLOW_COMPLETE_ERROR_DESC]);

        $entries = [
            [
                // This entry has account number with multiple rules violation in RAZORPAYX_ACCOUNT_NUMBER field
                // and all errors are shown in the error description.
                Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '232!',
                Batch\Header::PAYOUT_AMOUNT_RUPEES      => 10,
                Batch\Header::PAYOUT_CURRENCY           => 'INR',
                Batch\Header::PAYOUT_MODE               => 'NEFT',
                Batch\Header::PAYOUT_PURPOSE            => 'refund',
                Batch\Header::FUND_ACCOUNT_ID           => '',
                Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account',
                Batch\Header::FUND_ACCOUNT_NAME         => 'Chirag Chiranjib',
                Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0010720',
                Batch\Header::FUND_ACCOUNT_NUMBER       => '100200300400',
                Batch\Header::FUND_ACCOUNT_VPA          => '',
                Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '',
                Batch\Header::CONTACT_NAME_2            => "Chirag Chiranjib",
                Batch\Header::PAYOUT_NARRATION          => 'NarrationTest',
                Batch\Header::PAYOUT_REFERENCE_ID       => '',
                Batch\Header::FUND_ACCOUNT_EMAIL        => '',
                Batch\Header::CONTACT_TYPE              => 'employee',
                Batch\Header::CONTACT_EMAIL_2           => "chirag.chiranjib@razorpay.com",
                Batch\Header::CONTACT_MOBILE_2          => '',
                Batch\Header::CONTACT_REFERENCE_ID      => '',
                Batch\Header::NOTES_CODE                => 'test',
                Batch\Header::NOTES_PLACE               => 'Bhubaneswar'
            ],
            [
                // This entry has invalid account number with multiple rules violation, has fund account
                // type violation and violation of custom rules corresponding to non-utf8 characters
                // in contact email and contact name.
                // The errors for all of this are shown in the error description.
                Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '232!',
                Batch\Header::PAYOUT_AMOUNT_RUPEES      => 20,
                Batch\Header::PAYOUT_CURRENCY           => 'INR',
                Batch\Header::PAYOUT_MODE               => 'NEFT',
                Batch\Header::PAYOUT_PURPOSE            => 'refund',
                Batch\Header::FUND_ACCOUNT_ID           => '',
                Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account_yo',
                Batch\Header::FUND_ACCOUNT_NAME         => 'Chirag Chiranjib',
                Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0010720',
                Batch\Header::FUND_ACCOUNT_NUMBER       => '100200300400',
                Batch\Header::FUND_ACCOUNT_VPA          => '',
                Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '',
                Batch\Header::CONTACT_NAME_2            => "Chirag \xff Chiranjib",
                Batch\Header::PAYOUT_NARRATION          => 'NarrationTest',
                Batch\Header::PAYOUT_REFERENCE_ID       => '',
                Batch\Header::FUND_ACCOUNT_EMAIL        => '',
                Batch\Header::CONTACT_TYPE              => 'employeeNo',
                Batch\Header::CONTACT_EMAIL_2           => "chirag\xffchiranjib@razorpay.com",
                Batch\Header::CONTACT_MOBILE_2          => '',
                Batch\Header::CONTACT_REFERENCE_ID      => '',
                Batch\Header::NOTES_CODE                => 'test',
                Batch\Header::NOTES_PLACE               => 'Bhubaneswar'
            ],
            [
                // This entry has invalid utf-8 character in both CONTACT_NAME_2 field and CONTACT_EMAIL_2 field
                // and the errors for them are shown in the error description.
                Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                Batch\Header::PAYOUT_AMOUNT_RUPEES      => 30,
                Batch\Header::PAYOUT_CURRENCY           => 'INR',
                Batch\Header::PAYOUT_MODE               => 'NEFT',
                Batch\Header::PAYOUT_PURPOSE            => 'refund',
                Batch\Header::FUND_ACCOUNT_ID           => '',
                Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account',
                Batch\Header::FUND_ACCOUNT_NAME         => 'Chirag Chiranjib',
                Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0010720',
                Batch\Header::FUND_ACCOUNT_NUMBER       => '100200300400',
                Batch\Header::FUND_ACCOUNT_VPA          => '',
                Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '',
                Batch\Header::CONTACT_NAME_2            => "Chirag \xf8 Chiranjib",
                Batch\Header::PAYOUT_NARRATION          => 'NarrationTest',
                Batch\Header::PAYOUT_REFERENCE_ID       => '',
                Batch\Header::FUND_ACCOUNT_EMAIL        => '',
                Batch\Header::CONTACT_TYPE              => 'employee',
                Batch\Header::CONTACT_EMAIL_2           => "chirag\xffchiranjib@razorpay.com",
                Batch\Header::CONTACT_MOBILE_2          => '',
                Batch\Header::CONTACT_REFERENCE_ID      => '',
                Batch\Header::NOTES_CODE                => 'test',
                Batch\Header::NOTES_PLACE               => 'Bhubaneswar'
            ],
            [                     // This entry has no errors and So this entry will be parsed successfully.
                Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                Batch\Header::PAYOUT_AMOUNT_RUPEES      => 0,
                Batch\Header::PAYOUT_CURRENCY           => 'INR',
                Batch\Header::PAYOUT_MODE               => 'NEFT',
                Batch\Header::PAYOUT_PURPOSE            => 'refund',
                Batch\Header::FUND_ACCOUNT_ID           => '',
                Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account',
                Batch\Header::FUND_ACCOUNT_NAME         => 'Chirag Chiranjib',
                Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0010720',
                Batch\Header::FUND_ACCOUNT_NUMBER       => '100200300400',
                Batch\Header::FUND_ACCOUNT_VPA          => '',
                Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '',
                Batch\Header::CONTACT_NAME_2            => "Chirag Chiranjib",
                Batch\Header::PAYOUT_NARRATION          => 'NarrationTest',
                Batch\Header::PAYOUT_REFERENCE_ID       => '',
                Batch\Header::FUND_ACCOUNT_EMAIL        => '',
                Batch\Header::CONTACT_TYPE              => 'employee',
                Batch\Header::CONTACT_EMAIL_2           => "chirag.chiranjib@razorpay.com",
                Batch\Header::CONTACT_MOBILE_2          => '',
                Batch\Header::CONTACT_REFERENCE_ID      => '',
                Batch\Header::NOTES_CODE                => 'test',
                Batch\Header::NOTES_PLACE               => 'Bhubaneswar'
            ],
            [
                // This entry has no errors and So this entry will be parsed successfully.
                Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                Batch\Header::PAYOUT_AMOUNT_RUPEES      => 40,
                Batch\Header::PAYOUT_CURRENCY           => 'INR',
                Batch\Header::PAYOUT_MODE               => 'NEFT',
                Batch\Header::PAYOUT_PURPOSE            => 'refund',
                Batch\Header::FUND_ACCOUNT_ID           => '',
                Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account',
                Batch\Header::FUND_ACCOUNT_NAME         => 'Chirag Chiranjib',
                Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0010720',
                Batch\Header::FUND_ACCOUNT_NUMBER       => '100200300400',
                Batch\Header::FUND_ACCOUNT_VPA          => '',
                Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '',
                Batch\Header::CONTACT_NAME_2            => "Chirag Chiranjib",
                Batch\Header::PAYOUT_NARRATION          => 'NarrationTest',
                Batch\Header::PAYOUT_REFERENCE_ID       => '',
                Batch\Header::FUND_ACCOUNT_EMAIL        => '',
                Batch\Header::CONTACT_TYPE              => 'employee',
                Batch\Header::CONTACT_EMAIL_2           => "chirag.chiranjib@razorpay.com",
                Batch\Header::CONTACT_MOBILE_2          => '',
                Batch\Header::CONTACT_REFERENCE_ID      => '',
                Batch\Header::NOTES_CODE                => 'test',
                Batch\Header::NOTES_PLACE               => 'Bhubaneswar'
            ],
        ];

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $response = $this->startTest();
    }

    public function testValidateBulkPayoutsWithAmazonPayWithBeneDetail()
    {
        $this->fixtures->merchant->addFeatures([Feature::ALLOW_COMPLETE_ERROR_DESC]);

        $amazonPayWithBeneDetailsEntries = [
            [
                "Beneficiary Name (Mandatory) Special characters not supported"  => 'Ironman',
                "Beneficiary's Phone No. Linked with Amazon Pay (Mandatory)" => '9988998899',
                "Payout Amount (Mandatory) Amount should be in rupees" => 10,
            ],
        ];

        $this->createAndPutCsvFileInRequest($amazonPayWithBeneDetailsEntries, __FUNCTION__);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertEquals('payouts_amazonpay_bene_details', $response['batch_type_id']);
    }

    public function testValidateBulkPayoutsWithUPIWithBeneDetail()
    {
        $this->fixtures->merchant->addFeatures([Feature::ALLOW_COMPLETE_ERROR_DESC]);

        $upiWithBeneDetailsEntries = [
            [
                "Beneficiary Name (Mandatory) Special characters not supported"  => 'Ironman',
                "Beneficiary's UPI ID (Mandatory)" => 'test@okaxis',
                "Payout Amount (Mandatory) Amount should be in rupees" => 10,
            ],
        ];

        $this->createAndPutCsvFileInRequest($upiWithBeneDetailsEntries, __FUNCTION__);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertEquals('payouts_upi_bene_details', $response['batch_type_id']);
    }

    public function testValidateBulkPayoutsWithBankTransferWithBeneDetail()
    {
        $this->fixtures->merchant->addFeatures([Feature::ALLOW_COMPLETE_ERROR_DESC]);

        $banTransferWithBeneDetailsEntries = [
            [
                "Beneficiary Name (Mandatory) Special characters not supported"  => 'Ironman',
                "Beneficiary's Account Number (Mandatory)" => "100000000000",
                "IFSC Code (Mandatory)" => "HDFC00001",
                "Payout Amount (Mandatory) Amount should be in rupees" => 10,
                "Payout Mode (Mandatory)" => "IMPS"
            ],
        ];

        $this->createAndPutCsvFileInRequest($banTransferWithBeneDetailsEntries, __FUNCTION__);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertEquals('payouts_bank_transfer_bene_details', $response['batch_type_id']);
    }

    public function testValidateBulkPayoutsWithAmazonPayWithBeneId()
    {
        $this->fixtures->merchant->addFeatures([Feature::ALLOW_COMPLETE_ERROR_DESC]);

        $amazonPayWithBeneDetailsEntries = [
            [
                "Beneficiary's Fund Account ID Wallet (Mandatory) Unique id linked to a Razorpay Fund account." => 'fa_12345',
                "Payout Amount (Mandatory) Amount should be in rupees" => 10,
            ],
        ];

        $this->createAndPutCsvFileInRequest($amazonPayWithBeneDetailsEntries, __FUNCTION__);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertEquals('payouts_amazonpay_bene_id', $response['batch_type_id']);
    }

    public function testValidateBulkPayoutsWithBankTransferWithBeneId()
    {
        $this->fixtures->merchant->addFeatures([Feature::ALLOW_COMPLETE_ERROR_DESC]);

        $bankTransferWithBeneIDEntries = [
            [
                "Beneficiary's Fund Account ID (Mandatory) Unique id linked to a Razorpay Fund account." => 'fa_12345',
                "Payout Amount (Mandatory) Amount should be in rupees" => 10,
                "Payout Mode (Mandatory)" => "IMPS"
            ],
        ];

        $this->createAndPutCsvFileInRequest($bankTransferWithBeneIDEntries, __FUNCTION__);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertEquals('payouts_bank_transfer_bene_id', $response['batch_type_id']);
    }

    public function testValidateBulkPayoutsWithUPIWithBeneId()
    {
        $this->fixtures->merchant->addFeatures([Feature::ALLOW_COMPLETE_ERROR_DESC]);

        $upiWithBeneIDEntries = [
            [
                "Beneficiary's Fund Account ID (Mandatory) Unique id linked to a Razorpay Fund account." => 'fa_12345',
                "Payout Amount (Mandatory) Amount should be in rupees" => 10,
            ],
        ];

        $this->createAndPutCsvFileInRequest($upiWithBeneIDEntries, __FUNCTION__);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertEquals('payouts_upi_bene_id', $response['batch_type_id']);
    }

    public function testValidateEmptyBatchFile()
    {
        $this->fixtures->merchant->addFeatures([Feature::ALLOW_COMPLETE_ERROR_DESC]);

        $emptyEntries = [];

        $this->createAndPutExcelFileInRequest($emptyEntries, __FUNCTION__);

        $this->ba->proxyAuth();

        $response = $this->startTest();
    }

    public function testInvalidBankTransferBeneIdBatchFile()
    {
        $this->fixtures->merchant->addFeatures([Feature::ALLOW_COMPLETE_ERROR_DESC]);

        $InvalidBankTransferBeneIdEntries = [
            [
                "Beneficiary's Fund Account ID" => "fa_12345678901234",
                "Amount" => 100,
                "Payout Mode" => "IFSC"
            ]
        ];

        $this->createAndPutExcelFileInRequest($InvalidBankTransferBeneIdEntries, __FUNCTION__);

        $this->ba->proxyAuth();

        $response = $this->startTest();
    }

    public function testGetBatchRowsWithCreatorNameForTypePayouts()
    {
        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertArrayKeysExist($response, ['items', 'entity', 'count', 'has_more']);

        $this->assertNotNull($response['items']);

        $batchPayout = $response['items'][0];

        $this->assertNotNull($batchPayout['creator_name']);
    }

    public function testGetBatchRowsWithCreatorEmailForTypePayouts()
    {
        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertArrayKeysExist($response, ['items', 'entity', 'count', 'has_more']);

        $this->assertNotNull($response['items']);

        $batchPayout = $response['items'][0];

        $this->assertNotNull($batchPayout['creator_email']);

        $this->assertEquals("merchantuser01@razorpay.com", $batchPayout['creator_email']);
    }

    public function testGetBatchRowsWithCreatorNameForTypePaymentLinks()
    {
        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertArrayKeysExist($response, ['items', 'entity', 'count', 'has_more']);

        $this->assertNotNull($response['items']);

        $batchPayout = $response['items'][0];

        $this->assertNull($batchPayout['creator_name']);
    }

    public function testGetBatchRowsWithCreatorEmailForTypePaymentLinks()
    {
        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertArrayKeysExist($response, ['items', 'entity', 'count', 'has_more']);

        $this->assertNotNull($response['items']);

        $batchPayout = $response['items'][0];

        $this->assertNull($batchPayout['creator_email']);
    }

    public function testGetBatchDetails()
    {
        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertNotNull($response);
    }
}
