<?php


namespace RZP\Tests\Functional\Batch;

use RZP\Models\Batch;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\BadRequestException;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\BankingAccount\Activation\Comment\Service;

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
        $refno = '191919';

        $ba = $this->createBankingAccount([
            'bank_reference_number' => $refno
        ]);

        $comment = 'This is a sample comment from the bank.';

        if (empty($entries) === true)
        {
            $entries = [
                [
                    Batch\Header::RZP_REF_NO => $refno,
                    Batch\Header::COMMENT => $comment,
                    Batch\Header::NEW_BANK_STATUS => 'merchant is Not Available', // to test case-insensitivity
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
                    Batch\Header::DOCKET_REQUESTED_DATE => '28-Aug-22',
                    Batch\Header::ESTIMATED_DOCKET_DELIVERY_DATE => '29-Aug-22',
                    Batch\Header::DOCKET_DELIVERED_DATE => '30-Aug-22',
                    Batch\Header::COURIER_SERVICE_NAME => 'dtdc',
                    Batch\Header::COURIER_TRACKING_ID => 'Name2',
                    Batch\Header::REASON_WHY_DOCKET_IS_NOT_DELIVERED => '',
                ],
                [
                    Batch\Header::RZP_REF_NO => $refno,
                    Batch\Header::COMMENT => $comment,
                    Batch\Header::NEW_BANK_STATUS => 'razorpay dependent',
                    Batch\Header::NEW_STATUS => 'SentToBank',
                    Batch\Header::NEW_SUBSTATUS => 'Needs Clarification from RZP',
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
                    Batch\Header::DOCKET_REQUESTED_DATE => '28-Aug-22',
                    Batch\Header::ESTIMATED_DOCKET_DELIVERY_DATE => '29-Aug-22',
                    Batch\Header::DOCKET_DELIVERED_DATE => '30-Aug-22',
                    Batch\Header::COURIER_SERVICE_NAME => 'dtdc',
                    Batch\Header::COURIER_TRACKING_ID => 'Name2',
                    Batch\Header::REASON_WHY_DOCKET_IS_NOT_DELIVERED => '',
                ],
                [
                    Batch\Header::RZP_REF_NO => '102020', // non-existent
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
                    Batch\Header::DOCKET_REQUESTED_DATE => '28-Aug-22',
                    Batch\Header::ESTIMATED_DOCKET_DELIVERY_DATE => '29-Aug-22',
                    Batch\Header::DOCKET_DELIVERED_DATE => '30-Aug-22',
                    Batch\Header::COURIER_SERVICE_NAME => 'dtdc',
                    Batch\Header::COURIER_TRACKING_ID => 'Name2',
                    Batch\Header::REASON_WHY_DOCKET_IS_NOT_DELIVERED => '',
                ]
            ];
        }

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $response = $this->startTest();
    }

    public function testBatchUploadIcici(array $entries = [])
    {
        $comment = 'This is a sample comment from the icici bank.';

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

    public function testBatchUploadIciciStpMis(array $entries = [])
    {
        if (empty($entries) === true)
        {
            $entries = [
                [
                    Batch\Header::STP_ACCOUNT_NO                          => '198734',
                    Batch\Header::STP_FCRM_SR_DATE                        => '2/8/21',
                    Batch\Header::STP_SR_NUMBER                           => 'SR776607308',
                    Batch\Header::STP_SR_STATUS                           => 'Closed',
                    Batch\Header::STP_SR_CLOSED_DATE                      => '1/9/2021',
                    Batch\Header::STP_REMARKS                             => 'Assigned to COG_COP',
                    Batch\Header::STP_CONNECTED_BANKING                   => 'Reg done',
                    Batch\Header::STP_RZP_INTERVENTION                    => 'Verificaiton pending from RZP',
                    Batch\Header::STP_T3_DATE                             => '8/3/2021',
                    Batch\Header::STP_HELPDESK_SR_STATUS                  => 'Closed',
                    Batch\Header::STP_HELPDESK_SR                         => 'SR205973505',

                ],
                [
                    Batch\Header::STP_ACCOUNT_NO                          => '000405569238',
                    Batch\Header::STP_FCRM_SR_DATE                        => '11/8/2021',
                    Batch\Header::STP_SR_NUMBER                           => 'SR787535989',
                    Batch\Header::STP_SR_STATUS                           => 'Open',
                    Batch\Header::STP_SR_CLOSED_DATE                      => '11/9/2021',
                    Batch\Header::STP_REMARKS                             => 'Requirement not mentioned in BR and ' .
                                                                            'request form, kindly check',
                    Batch\Header::STP_CONNECTED_BANKING                   => 'Reg Not done',
                    Batch\Header::STP_RZP_INTERVENTION                    => 'Please verify docs',
                    Batch\Header::STP_T3_DATE                             => '9/3/2021',
                    Batch\Header::STP_HELPDESK_SR_STATUS                  => 'Open',
                    Batch\Header::STP_HELPDESK_SR                         => 'SR205973522',

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

    public function testBatchUploadIncorrectHeadersForIciciStpMis()
    {
        $entries = [
            [
                'abc' => '1921919',
                Batch\Header::STP_REMARKS => 'Sample Test comment'
            ]
        ];

        $this->expectException(BadRequestException::class);

        $this->testBatchUploadIciciStpMis($entries);
    }

    public function testBatchUploadIncorrectValuesForIciciStpMis()
    {
        $entries = [
            [
                Batch\Header::STP_ACCOUNT_NO => 'abc',
                Batch\Header::STP_FCRM_SR_DATE => 'Invalid Date',
            ]
        ];

        $this->expectException(BadRequestException::class);

        $this->testBatchUploadIciciStpMis($entries);
    }

    public function testBatchUploadForRblBulkUploadComments(array $entries = [])
    {
        if (empty($entries) === true)
        {
            $entries = [
                [
                    Batch\Header::MERCHANT_ID               =>  'merchant_id',
                    Batch\Header::DATE_TIME                 =>  '12/05/2022 12:00:00',
                    Batch\Header::FIRST_DISPOSITION         =>  'first_disposition',
                    Batch\Header::SECOND_DISPOSITION        =>  'second_disposition',
                    Batch\Header::THIRD_DISPOSISTION        =>  'third_disposition',
                    Batch\Header::OPS_CALL_COMMENT          =>  'comment'
                ],
                [
                    Batch\Header::MERCHANT_ID               =>  'merchant_id',
                    Batch\Header::DATE_TIME                 =>  '12/05/2022 12:05:00',
                    Batch\Header::FIRST_DISPOSITION         =>  'first_disposition1',
                    Batch\Header::SECOND_DISPOSITION        =>  'second_disposition1',
                    Batch\Header::THIRD_DISPOSISTION        =>  'third_disposition1',
                    Batch\Header::OPS_CALL_COMMENT          =>  'comment1'
                ]
            ];
        }

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->startTest();
    }

    public function testBatchUploadForRblBulkUploadCommentsIncorrectHeaders()
    {
        $entries = [
            [
                Batch\Header::MERCHANT_ID               =>  'merchant_id',
                'date_time'                             =>  '12/05/2022 12:05:00',
            ],
        ];

        $this->expectException(BadRequestValidationFailureException::class);

        $this->testBatchUploadForRblBulkUploadComments($entries);
    }

    public function testBatchUploadForIciciBulkUploadComments(array $entries = [])
    {
        if (empty($entries) === true)
        {
            $entries = [
                [
                    Batch\Header::MERCHANT_ID               =>  'merchant_id1',
                    Batch\Header::DATE_TIME                 =>  '12/05/2022 12:00:00',
                    Batch\Header::COMMENT                   =>  'comment',
                    Batch\Header::FIRST_DISPOSITION         =>  'first_disposition',
                    Batch\Header::SECOND_DISPOSITION        =>  'second_disposition',
                    Batch\Header::THIRD_DISPOSISTION        =>  'third_disposition',

                ],
                [
                    Batch\Header::MERCHANT_ID               =>  'merchant_id2',
                    Batch\Header::DATE_TIME                 =>  '19/07/2022 19:00:00',
                    Batch\Header::COMMENT                   =>  'comment1',
                    Batch\Header::FIRST_DISPOSITION         =>  'first_disposition1',
                    Batch\Header::SECOND_DISPOSITION        =>  'second_disposition1',
                    Batch\Header::THIRD_DISPOSISTION        =>  'third_disposition1',
                ]
            ];
        }

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->startTest();
    }

    public function testBatchUploadForIciciVideoKycBulkUpload(array $entries = [])
    {
        if (empty($entries) === true)
        {
            $entries = [
                [
                    Batch\Header::APPLICATION_NO                                   =>  '777-000011055',
                    Batch\Header::TRACKER_ID                                       =>  'WEB111111111114700',
                    Batch\Header::CLIENT_NAME                                      =>  'ABC Pvt Ltd. ',
                    Batch\Header::F_NAME                                           =>  'First Name',
                    Batch\Header::L_NAME                                           =>  'Last Name',
                    Batch\Header::ICICI_CA_ACCOUNT_NUMBER                          =>  '1234543122',
                    Batch\Header::LEADID                                           =>  '123456789',
                    Batch\Header::COMMENT_OR_REMARKS                               =>  'this is test comment',
                    Batch\Header::ICICI_LEADID_CREATION_DATE                       =>  'comment',
                    Batch\Header::ICICI_T3_VKYC_COMPLETION_DATE                    =>  '19/07/2022',
                    Batch\Header::ICICI_VKYC_INELIGIBLE_DATE                       =>  '19/07/2022',
                    Batch\Header::ICICI_VKYC_INELIGIBLE_REASON                     =>  'vkyc ineligible reason',
                    Batch\Header::ICICI_VKYC_COMPLETION_DATE                       =>  '12/05/2022',
                    Batch\Header::ICICI_VKYC_DROP_OFF_DATE                         =>  '19/07/2022',
                    Batch\Header::ICICI_VKYC_UNSUCCESSFUL_DATE                     =>  '19/07/2022',
                    Batch\Header::ICICI_LEAD_ASSIGNED_TO_PHYSICAL_TEAM_DATE        =>  '19/07/2022',
                    Batch\Header::ICICI_VKYC_STATUS        =>  'Sent to bank',
                ],
                [
                    Batch\Header::APPLICATION_NO                                   =>  '888-000011055',
                    Batch\Header::TRACKER_ID                                       =>  'WEB111111111114700',
                    Batch\Header::CLIENT_NAME                                      =>  'ASD Pvt Ltd. ',
                    Batch\Header::F_NAME                                           =>  'First Name',
                    Batch\Header::L_NAME                                           =>  'Last Name',
                    Batch\Header::ICICI_CA_ACCOUNT_NUMBER                          =>  '1234543122',
                    Batch\Header::LEADID                                           =>  '123456789',
                    Batch\Header::COMMENT_OR_REMARKS                               =>  'this is test comment',
                    Batch\Header::ICICI_LEADID_CREATION_DATE                       =>  'comment',
                    Batch\Header::ICICI_T3_VKYC_COMPLETION_DATE                    =>  '19/07/2022',
                    Batch\Header::ICICI_VKYC_INELIGIBLE_DATE                       =>  '19/07/2022',
                    Batch\Header::ICICI_VKYC_INELIGIBLE_REASON                     =>  'vkyc ineligible reason',
                    Batch\Header::ICICI_VKYC_COMPLETION_DATE                       =>  '12/05/2022',
                    Batch\Header::ICICI_VKYC_DROP_OFF_DATE                         =>  '19/07/2022',
                    Batch\Header::ICICI_VKYC_UNSUCCESSFUL_DATE                     =>  '19/07/2022',
                    Batch\Header::ICICI_LEAD_ASSIGNED_TO_PHYSICAL_TEAM_DATE        =>  '19/07/2022',
                    Batch\Header::ICICI_VKYC_STATUS                                =>  'Sent to bank',
                ],
            ];
        }

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->startTest();
    }

    public function testBatchUploadForIciciBulkUploadCommentsIncorrectHeaders()
    {
        $entries = [
            [
                Batch\Header::MERCHANT_ID               =>  'merchant_id',
                Batch\Header::DATE_TIME                 =>  '12/05/2022 12:00:00',
                "not-correct"                           =>  'definitely not correct'
            ]
        ];

        $this->expectException(BadRequestValidationFailureException::class);

        $this->testBatchUploadForIciciBulkUploadComments($entries);

    }

    public function testBatchUploadForIciciVideoKycBulkUploadIncorrectHeaders()
    {
        $entries = [
            [
                Batch\Header::APPLICATION_NO               =>  '123123123',
                Batch\Header::TRACKER_ID                 =>  '34342312312',
                "not-correct"                           =>  'definitely not correct'
            ]
        ];

        $this->expectException(BadRequestValidationFailureException::class);

        $this->testBatchUploadForIciciVideoKycBulkUpload($entries);

    }

    public function testBatchUploadForRblBulkUploadCommentsFailureCase()
    {
        $this->fixtures->create('banking_account', ['id' => '01234567890123', 'account_type' => 'current', 'status' => 'archived']);

        $this->fixtures->create('banking_account', ['id' => '12345678901234','account_type' => 'current', 'status' => 'activated']);

        $admin = $this->fixtures->create('admin', [
            'org_id'  => '100000razorpay',
            'email'  => 'xyz@rzp.com',
            'name' => 'test_admin',
        ]);

        $mid = '10000000000000';

        $requestPayload = [
            'merchant_id'           => $mid,
            'date_time'             => '27/02/2022 12:00:00',
            'ops_call_comment'      => 'test',
            'first_disposition'     => 'test',
            'second_disposition'    => 'test',
            'third_disposition'     => 'test',
            'admin_id'              => $admin->getPublicId(),
            'admin_email'           => $admin->getEmail(),
            'admin_name'            => $admin->getName(),
        ];

        $bankingAccountService = new Service();

        $this->expectException(BadRequestException::class);

        $bankingAccountService->createCommentFromBatch($requestPayload);
    }
}
