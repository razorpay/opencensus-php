<?php


namespace RZP\Tests\Functional\Batch;

use RZP\Tests\Functional\TestCase;
use RZP\Models\Batch;

class PayoutApprovalTest extends TestCase
{
    use BatchTestTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/PayoutApprovalTestData.php';

        parent::setUp();

        $this->ba->proxyAuth();
    }

    public function testBatchFileValidation(){

        $entries = [
            [
                Batch\Header::APPROVE_REJECT_PAYOUT => 'A',
                Batch\Header::P_A_AMOUNT => 100.23,
                Batch\Header::P_A_CURRENCY => 'INR',
                Batch\Header::P_A_CONTACT_NAME => 'rakshit',
                Batch\Header::P_A_MODE => 'IMPS',
                Batch\Header::P_A_PURPOSE => 'payout',
                Batch\Header::P_A_PAYOUT_ID => 'pout_FEdazBZrwR6Bn4',
                Batch\Header::P_A_CONTACT_ID => 'FEcrWGP1ZAEU5S',
                Batch\Header::P_A_FUND_ACCOUNT_ID => 'FEcsAwD6TwN7gd',
                Batch\Header::P_A_CREATED_AT => '',
                Batch\Header::P_A_ACCOUNT_NUMBER => 7878780019112500,
                Batch\Header::P_A_STATUS => 'pending',
                Batch\Header::P_A_NOTES => '[]',
                Batch\Header::P_A_FEES => 0,
                Batch\Header::P_A_TAX =>0,
                Batch\Header::P_A_SCHEDULED_AT => ''
            ],
            [
                Batch\Header::APPROVE_REJECT_PAYOUT => 'R',
                Batch\Header::P_A_AMOUNT => 100,
                Batch\Header::P_A_CURRENCY => 'INR',
                Batch\Header::P_A_CONTACT_NAME => 'rakshit',
                Batch\Header::P_A_MODE => 'IMPS',
                Batch\Header::P_A_PURPOSE => 'payout',
                Batch\Header::P_A_PAYOUT_ID => 'pout_FEdazBZrwR6Bn8',
                Batch\Header::P_A_CONTACT_ID => 'FEcrWGP1ZAEU5S',
                Batch\Header::P_A_FUND_ACCOUNT_ID => 'FEcsAwD6TwN7gd',
                Batch\Header::P_A_CREATED_AT => '15/07/2020 09:51:47',
                Batch\Header::P_A_ACCOUNT_NUMBER => '',//7878780019112500,
                Batch\Header::P_A_STATUS => 'pending',
                Batch\Header::P_A_NOTES => '[]',
                Batch\Header::P_A_FEES => 0,
                Batch\Header::P_A_TAX =>0,
                Batch\Header::P_A_SCHEDULED_AT => ''
            ]
        ];

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $response = $this->startTest();
    }

    public function testBatchFileValidationFailure(){
        $entries = [
            [
                Batch\Header::APPROVE_REJECT_PAYOUT => 'F',
                Batch\Header::P_A_AMOUNT => 100.23,
                Batch\Header::P_A_CURRENCY => 'INR',
                Batch\Header::P_A_CONTACT_NAME => 'rakshit',
                Batch\Header::P_A_MODE => 'IMPS',
                Batch\Header::P_A_PURPOSE => 'payout',
                Batch\Header::P_A_PAYOUT_ID => 'pout_FEdazBZrwR6Bn4',
                Batch\Header::P_A_CONTACT_ID => 'FEcrWGP1ZAEU5S',
                Batch\Header::P_A_FUND_ACCOUNT_ID => 'FEcsAwD6TwN7gd',
                Batch\Header::P_A_CREATED_AT => '15/07/2020 09:51:43',
                Batch\Header::P_A_ACCOUNT_NUMBER =>  '',//7878780019112500,
                Batch\Header::P_A_STATUS => 'pending',
                Batch\Header::P_A_NOTES => '[]',
                Batch\Header::P_A_FEES => 0,
                Batch\Header::P_A_TAX =>0,
                Batch\Header::P_A_SCHEDULED_AT => ''
            ]
        ];

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $response = $this->startTest();
    }

}
