<?php


namespace RZP\Tests\Functional\Batch;

use RZP\Models\Batch;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\BadRequestException;

class BankingAccountActivationCommentsTest extends TestCase
{
    use BatchTestTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/BankingAccountActivationCommentsTestData.php';

        parent::setUp();

        $this->ba->adminAuth();
    }

    protected function createBankingAccount(array $extraParams = [])
    {
        $defaultParams = [
            'account_number'        => '2224440041626905',
            'account_type'          => 'current',
            'channel'               => 'rbl',
            'status'                => 'created',
            'pincode'               => '1',
            'bank_reference_number' => '',
            'account_ifsc'          => 'RATN0000156',
        ];

        $params = array_merge($defaultParams, $extraParams);

        $ba1 = $this->fixtures->create('banking_account', $params);

        return $ba1;
    }

    public function testBatchUpload(array $entries = [])
    {
        $refno = "191919";

        $ba = $this->createBankingAccount([
            "bank_reference_number" => $refno
        ]);

        $comment = "This is a sample comment from the bank.";

        if (empty($entries) === true)
        {
            $entries = [
                [
                    Batch\Header::RZP_REF_NO => $refno,
                    Batch\Header::COMMENT => $comment,
                    Batch\Header::NEW_BANK_STATUS => 'Merchant is not available',
                    Batch\Header::NEW_STATUS => 'RazorpayProcessing',
                    Batch\Header::NEW_SUBSTATUS => 'Merchant is not Available',
                    Batch\Header::NEW_ASSIGNEE => 'sales',
                    Batch\Header::RM_NAME => 'Name1',
                    Batch\Header::RM_PHONE_NUMBER => '1234543121',
                    Batch\Header::ACCOUNT_OPEN_DATE => '20/7/2020 12:00:00 AM',
                    Batch\Header::ACCOUNT_LOGIN_DATE => '20/7/2020 12:00:00 AM',
                    Batch\Header::SALES_TEAM => '',
                    Batch\Header::SALES_POC_EMAIL => '',
                    Batch\Header::API_ONBOARDED_DATE => '20/7/2020 12:00:00 AM',
                    Batch\Header::API_ONBOARDING_LOGIN_DATE => '20/7/2020 12:00:00 AM',
                    Batch\Header::MID_OFFICE_POC_NAME => 'Name2',
                ],
                [
                    Batch\Header::RZP_REF_NO => "102020", // non-existent
                    Batch\Header::COMMENT => $comment,
                    Batch\Header::NEW_STATUS => 'RazorpayProcessing',
                    Batch\Header::NEW_SUBSTATUS => 'Merchant is not Available',
                    Batch\Header::NEW_ASSIGNEE => 'sales',
                    Batch\Header::RM_NAME => 'Name1',
                    Batch\Header::RM_PHONE_NUMBER => '1234543121',
                    Batch\Header::ACCOUNT_OPEN_DATE => '20/7/2020 12:00:00 AM',
                    Batch\Header::ACCOUNT_LOGIN_DATE => '20/7/2020 12:00:00 AM',
                    Batch\Header::SALES_TEAM => '',
                    Batch\Header::SALES_POC_EMAIL => '',
                    Batch\Header::API_ONBOARDED_DATE => '20/7/2020 12:00:00 AM',
                    Batch\Header::API_ONBOARDING_LOGIN_DATE => '20/7/2020 12:00:00 AM',
                    Batch\Header::MID_OFFICE_POC_NAME => 'Name3',
                ]
            ];
        }

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $response = $this->startTest();
    }

    public function testBatchUploadIcici(array $entries = [])
    {
        $comment = "This is a sample comment from the icici bank.";

        if (empty($entries) === true)
        {
            $entries = [
                [
                    Batch\Header::APPLICATION_NO                          => '198734',
                    Batch\Header::TRACKER_ID                              => '2022345678',
                    Batch\Header::CLIENT_NAME                             => 'Test Account',
                    Batch\Header::F_NAME                                  => 'Razorpay',
                    Batch\Header::L_NAME                                  => 'Private Limited',
                    Batch\Header::LEADID                                  => '345612367',
                    Batch\Header::ICICI_CA_ACCOUNT_NUMBER                 => '1234543122',
                    Batch\Header::ICICI_CA_ACCOUNT_STATUS                 => '1234543121',
                    Batch\Header::ICICI_LEAD_SUB_STATUS                   => 'dummy',
                    Batch\Header::LAST_UPDATED_ON_DATE                    => '20/7/2020 12:00:00 AM',
                    Batch\Header::LAST_UPDATED_ON_TIME                    => '20/7/2020 12:00:00 AM',
                    Batch\Header::COMMENT_OR_REMARKS                      => $comment,
                    Batch\Header::DOCS_COLLECTED_DATE                     => '2021-12-01',
                    Batch\Header::LEAD_SENT_TO_BANK_DATE                  => '2021-12-01',
                    Batch\Header::DATE_ON_WHICH_1ST_APPOINTMENT_WAS_FIXED => '2021-12-01',
                    Batch\Header::CASE_INITIATION_DATE                    => '2021-12-01',
                    Batch\Header::ACCOUNT_OPENED_DATE                     => '2021-12-01',
                    Batch\Header::MULTI_LOCATION                          => '2021-12-01',
                    Batch\Header::DROP_OFF_REASON                         => '2021-12-01',
                    Batch\Header::STP_DOCS_COLLECTED                      => 'Y',
                    Batch\Header::ACCOUNT_NUMBER_CHANGE                   => 'N',
                    Batch\Header::FOLLOW_UP_DATE                          => '20/7/2020'
                ],
                [
                    Batch\Header::APPLICATION_NO                          => '198735',
                    Batch\Header::TRACKER_ID                              => '2022345678',
                    Batch\Header::CLIENT_NAME                             => 'Test Account',
                    Batch\Header::F_NAME                                  => 'Razorpay',
                    Batch\Header::L_NAME                                  => 'Private Limited',
                    Batch\Header::LEADID                                  => '345612367',
                    Batch\Header::ICICI_CA_ACCOUNT_NUMBER                 => '1234543122',
                    Batch\Header::ICICI_CA_ACCOUNT_STATUS                 => '1234543121',
                    Batch\Header::ICICI_LEAD_SUB_STATUS                   => 'dummy',
                    Batch\Header::LAST_UPDATED_ON_DATE                    => '20/7/2020 12:00:00 AM',
                    Batch\Header::LAST_UPDATED_ON_TIME                    => '20/7/2020 12:00:00 AM',
                    Batch\Header::COMMENT_OR_REMARKS                      => $comment,
                    Batch\Header::DOCS_COLLECTED_DATE                     => '2021-12-01',
                    Batch\Header::LEAD_SENT_TO_BANK_DATE                  => '2021-12-01',
                    Batch\Header::DATE_ON_WHICH_1ST_APPOINTMENT_WAS_FIXED => '2021-12-01',
                    Batch\Header::CASE_INITIATION_DATE                    => '2021-12-01',
                    Batch\Header::ACCOUNT_OPENED_DATE                     => '2021-12-01',
                    Batch\Header::MULTI_LOCATION                          => '2021-12-01',
                    Batch\Header::DROP_OFF_REASON                         => '2021-12-01',
                    Batch\Header::STP_DOCS_COLLECTED                      => 'Y',
                    Batch\Header::ACCOUNT_NUMBER_CHANGE                   => 'N',
                    Batch\Header::FOLLOW_UP_DATE                          => '20/7/2020'
                ]
            ];
        }

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->startTest();
    }

    public function testBatchUploadIncorrectHeaders()
    {
        $entries = [
            [
                'abc' => '191919',
                Batch\Header::COMMENT => 'Sample comment'
            ]
        ];

        $this->expectException(BadRequestException::class);

        $this->testBatchUpload($entries);
    }

    public function testBatchUploadIncorrectValues()
    {
        $entries = [
            [
                Batch\Header::RZP_REF_NO => 'abc',
                Batch\Header::COMMENT => 'Sample comment',
                Batch\Header::NEW_STATUS => 'Razorpay Processing'
            ]
        ];

        $this->expectException(BadRequestException::class);

        $this->testBatchUpload($entries);
    }

    public function testBatchUploadIncorrectHeadersForIcici()
    {
        $entries = [
            [
                'abc' => '191919',
                Batch\Header::COMMENT => 'Sample comment'
            ]
        ];

        $this->expectException(BadRequestException::class);

        $this->testBatchUploadIcici($entries);
    }

    public function testBatchUploadIncorrectValuesForIcici()
    {
        $entries = [
            [
                Batch\Header::APPLICATION_NO => 'abc',
                Batch\Header::COMMENT => 'Sample comment',
                Batch\Header::TRACKER_ID => 'Razorpay Processing'
            ]
        ];

        $this->expectException(BadRequestException::class);

        $this->testBatchUploadIcici($entries);
    }
}
