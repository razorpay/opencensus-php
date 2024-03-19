<?php

namespace Functional\Batch;

use RZP\Models\Batch;
use RZP\Tests\Functional\Batch\BatchTestTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\BadRequestException;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\BankingAccount\Activation\Comment\Service;

class S2pOnboardingTest extends TestCase
{
    use BatchTestTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/S2pOnboardingTestData.php';

        parent::setUp();

        $this->ba->adminAuth();
    }

    public function testBatchUploadForS2pGroupsOnboarding(array $entries = [])
    {
        if (empty($entries) === true)
        {
            $entries = [
                [
                    Batch\Header::S2P_GROUPS_ONBOARDING_MERCHANT_ID      => '100000Razorpay',
                    Batch\Header::S2P_GROUPS_ONBOARDING_GROUP_TYPE_ID    => '100001Razorpay',
                    Batch\Header::S2P_GROUPS_ONBOARDING_NAME             => 'CBU Team',
                    Batch\Header::S2P_GROUPS_ONBOARDING_GROUP_HEAD_EMAIL => 'john.doe@razorpay.com',
                    Batch\Header::S2P_GROUPS_ONBOARDING_REFERENCE_ID     => 'GRP_0001',
                    Batch\Header::S2P_GROUPS_ONBOARDING_DESCRIPTION      => 'CBU team',
                ],
                [
                    Batch\Header::S2P_GROUPS_ONBOARDING_MERCHANT_ID      => '100000Razorpay',
                    Batch\Header::S2P_GROUPS_ONBOARDING_GROUP_TYPE_ID    => '100001Razorpay',
                    Batch\Header::S2P_GROUPS_ONBOARDING_NAME             => 'VP Team',
                    Batch\Header::S2P_GROUPS_ONBOARDING_GROUP_HEAD_EMAIL => 'jane.smith@razorpay.com',
                    Batch\Header::S2P_GROUPS_ONBOARDING_REFERENCE_ID     => 'GRP_0002',
                    Batch\Header::S2P_GROUPS_ONBOARDING_DESCRIPTION      => 'VP team',
                ],
            ];
        }

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->startTest();
    }

    public function testBatchUploadForS2pGroupsOnboardingIncorrectHeaders()
    {
        $entries = [
            [
                Batch\Header::S2P_GROUPS_ONBOARDING_MERCHANT_ID   => '100000Razorpay',
                Batch\Header::S2P_GROUPS_ONBOARDING_GROUP_TYPE_ID => '100001Razorpay',
                'Invalid_Header'                                  => 'Invalid header value',
            ]
        ];

        $this->expectException(BadRequestValidationFailureException::class);

        $this->testBatchUploadForS2pGroupsOnboarding($entries);

    }

    public function testBatchUploadForS2pUsersOnboarding(array $entries = [])
    {
        if (empty($entries) === true)
        {
            $entries = [
                [
                    Batch\Header::S2P_USERS_ONBOARDING_MERCHANT_ID      => '100000Razorpay',
                    Batch\Header::S2P_USERS_ONBOARDING_FIRST_NAME       => 'John',
                    Batch\Header::S2P_USERS_ONBOARDING_LAST_NAME        => 'Doe',
                    Batch\Header::S2P_USERS_ONBOARDING_EMAIL_ID         => 'john.doe@razorpay.com',
                    Batch\Header::S2P_USERS_ONBOARDING_USER_ROLE        => 'admin',
                    Batch\Header::S2P_USERS_ONBOARDING_DESIGNATION      => 'Software Engineer',
                    Batch\Header::S2P_USERS_ONBOARDING_EMPLOYEE_ID      => 'EMP_0001',
                    Batch\Header::S2P_USERS_ONBOARDING_MANAGER_EMAIL_ID => 'jane.smith@razorpay.com',
                    Batch\Header::S2P_USERS_ONBOARDING_GROUP_IDS        => 'GRP_0001,GRP_0002',
                ],
                [
                    Batch\Header::S2P_USERS_ONBOARDING_MERCHANT_ID      => '100000Razorpay',
                    Batch\Header::S2P_USERS_ONBOARDING_FIRST_NAME       => 'John',
                    Batch\Header::S2P_USERS_ONBOARDING_LAST_NAME        => 'Wick',
                    Batch\Header::S2P_USERS_ONBOARDING_EMAIL_ID         => 'john.wick@razorpay.com',
                    Batch\Header::S2P_USERS_ONBOARDING_USER_ROLE        => 'admin',
                    Batch\Header::S2P_USERS_ONBOARDING_DESIGNATION      => 'Software Engineer',
                    Batch\Header::S2P_USERS_ONBOARDING_EMPLOYEE_ID      => 'EMP_0002',
                    Batch\Header::S2P_USERS_ONBOARDING_MANAGER_EMAIL_ID => 'jane.smith@razorpay.com',
                    Batch\Header::S2P_USERS_ONBOARDING_GROUP_IDS        => '',
                ],
            ];
        }

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->startTest();
    }

    public function testBatchUploadForS2pUsersOnboardingIncorrectHeaders()
    {
        $entries = [
            [
                Batch\Header::S2P_USERS_ONBOARDING_MERCHANT_ID   => '100000Razorpay',
                Batch\Header::S2P_USERS_ONBOARDING_EMAIL_ID         => 'john.doe@razorpay.com',
                'Invalid_Header'                                  => 'Invalid header value',
            ]
        ];

        $this->expectException(BadRequestValidationFailureException::class);

        $this->testBatchUploadForS2pUsersOnboarding($entries);

    }
}
