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
                ]
            ];
        }

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $response = $this->startTest();
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
}
