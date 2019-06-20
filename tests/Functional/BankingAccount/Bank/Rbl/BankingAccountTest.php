<?php

use RZP\Models\BankingAccount;
use RZP\Tests\Functional\TestCase;
use RZP\Models\BankingAccount\Entity;
use RZP\Mail\BankingAccount\NotifyStatusUpdate;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class BankingAccountTest extends TestCase
{
    use RequestResponseFlowTrait;
    use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/BankingAccountTestData.php';

        parent::setUp();

        // storing the below in redis for purpose of test cases.
        $pincodeList = ['560030', '560034'];

        $this->app['redis']->sadd('rbl_pincode_set', $pincodeList);

        $this->ba->proxyAuth();
    }

    public function testCreateBankingAccount()
    {
        Mail::fake();

        $this->startTest();

        Mail::assertQueued(NotifyStatusUpdate::class, function ($mail)
        {
            $this->assertArrayHasKey('contact_name', $mail->viewData);

            $this->assertArrayHasKey('contact_email', $mail->viewData);

            $this->assertArrayHasKey('status', $mail->viewData);

            $this->assertEquals('emails.banking_account.notify_status_created', $mail->view);

            return $mail->hasTo('test@razorpay.com');
        });
    }

    public function testCreateBankingAccountWithUnserviceablePincode()
    {
        Mail::fake();

        $this->startTest();

        Mail::assertQueued(NotifyStatusUpdate::class, function ($mail)
        {
            $this->assertArrayHasKey('contact_name', $mail->viewData);

            $this->assertArrayHasKey('contact_email', $mail->viewData);

            $this->assertArrayHasKey('status', $mail->viewData);

            $this->assertEquals('emails.banking_account.notify_status_created', $mail->view);

            return $mail->hasTo('test@razorpay.com');
        });
    }

    public function testCreateBankingAccountWithInvalidBank()
    {
        $data = $this->testData[__FUNCTION__];

        Mail::fake();

        Mail::assertNotQueued(NotifyStatusUpdate::class);

        $this->runRequestResponseFlow($data, function()
        {
            $this->createBankingAccount([Entity::CHANNEL => 'TEST']);
        });
    }

    public function testCreateBankingAccountWithEmptyPincode()
    {
        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function()
        {
            $this->createBankingAccount([Entity::PINCODE => '']);
        });
    }

    protected function createBankingAccount(array $attributes = [])
    {
        $data = [
            Entity::PINCODE => '560030',
            Entity::CHANNEL => 'rbl'
        ];

        $data = array_merge($data, $attributes);

        $request = [
            'method'  => 'post',
            'url'     => '/banking_accounts',
            'content' => $data
        ];

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    public function testNotificationOnBankingAccountProcessedFromProcessing()
    {
        $merchant = $this->activatedMerchant();

        $this->ba->proxyAuth('rzp_test_' . $merchant['id']);

        $bankingAccount = $this->createBankingAccount();

        $ba = $this->getDbLastEntity('banking_account');

        $ba->setStatus(BankingAccount\Status::PROCESSING);

        $ba->save();

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_account/' . $bankingAccount['id'],
                'method'  => 'PATCH',
            ],
            'response' => [
                'content' => [
                    'merchant_id' => $merchant['id'],
                ],
            ],
        ];

        Mail::fake();

        $this->ba->adminAuth();

        $this->startTest($dataToReplace);

        Mail::assertQueued(NotifyStatusUpdate::class, function ($mail)
        {
            $this->assertArrayHasKey('contact_name', $mail->viewData);

            $this->assertArrayHasKey('contact_email', $mail->viewData);

            $this->assertArrayHasKey('status', $mail->viewData);

            $this->assertEquals('emails.banking_account.notify_status_processed', $mail->view);

            return true;
        });
    }

    public function testNotificationOnBankingAccountCancelledFromProcessing()
    {
        $merchant = $this->activatedMerchant();

        $this->ba->proxyAuth('rzp_test_' . $merchant['id']);

        $bankingAccount = $this->createBankingAccount();

        $ba = $this->getDbLastEntity('banking_account');

        $ba->setStatus(BankingAccount\Status::PROCESSING);

        $ba->save();

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_account/' . $bankingAccount['id'],
                'method'  => 'PATCH',
            ],
            'response' => [
                'content' => [
                    'merchant_id' => $merchant['id'],
                ],
            ],
        ];

        Mail::fake();

        $this->ba->adminAuth();

        $this->startTest($dataToReplace);

        Mail::assertQueued(NotifyStatusUpdate::class, function ($mail)
        {
            $this->assertArrayHasKey('contact_name', $mail->viewData);

            $this->assertArrayHasKey('contact_email', $mail->viewData);

            $this->assertArrayHasKey('status', $mail->viewData);

            $this->assertEquals('emails.banking_account.notify_status_cancelled', $mail->view);

            return true;
        });
    }

    public function testNotificationOnBankingAccountCancelledFromInitiated()
    {
        $merchant = $this->activatedMerchant();

        $this->ba->proxyAuth('rzp_test_' . $merchant['id']);

        $bankingAccount = $this->createBankingAccount();

        $ba = $this->getDbLastEntity('banking_account');

        $ba->setStatus(BankingAccount\Status::INITIATED);

        $ba->save();

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_account/' . $bankingAccount['id'],
                'method'  => 'PATCH',
            ],
            'response' => [
                'content' => [
                    'merchant_id' => $merchant['id'],
                ],
            ],
        ];

        Mail::fake();

        $this->ba->adminAuth();

        $this->startTest($dataToReplace);

        Mail::assertNotQueued(NotifyStatusUpdate::class);
    }

    public function testNotificationOnBankingAccountProcessingFromInitiated()
    {
        $merchant = $this->activatedMerchant();

        $this->ba->proxyAuth('rzp_test_' . $merchant['id']);

        $bankingAccount = $this->createBankingAccount();

        $ba = $this->getDbLastEntity('banking_account');

        $ba->setStatus(BankingAccount\Status::INITIATED);

        $ba->save();

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_account/' . $bankingAccount['id'],
                'method'  => 'PATCH',
            ],
            'response' => [
                'content' => [
                    'merchant_id' => $merchant['id'],
                ],
            ],
        ];

        Mail::fake();

        $this->ba->adminAuth();

        $this->startTest($dataToReplace);

        Mail::assertQueued(NotifyStatusUpdate::class, function ($mail)
        {
            $this->assertArrayHasKey('contact_name', $mail->viewData);

            $this->assertArrayHasKey('contact_email', $mail->viewData);

            $this->assertArrayHasKey('status', $mail->viewData);

            $this->assertEquals('emails.banking_account.notify_status_processing', $mail->view);

            return true;
        });
    }

    public function testNotificationOnBankingAccountCreatedFromCreated()
    {
        $merchant = $this->activatedMerchant();

        $this->ba->proxyAuth('rzp_test_' . $merchant['id']);

        $bankingAccount = $this->createBankingAccount();

        $ba = $this->getDbLastEntity('banking_account');

        $ba->setStatus(BankingAccount\Status::INITIATED);

        $ba->save();

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_account/' . $bankingAccount['id'],
                'method'  => 'PATCH',
            ],
            'response' => [
                'content' => [
                    'merchant_id' => $merchant['id'],
                ],
            ],
        ];

        Mail::fake();

        $this->ba->adminAuth();

        $this->startTest($dataToReplace);

        Mail::assertNotQueued(NotifyStatusUpdate::class);
    }

    protected function activatedMerchant()
    {
        $attribute = ['activation_status' => 'activated'];

        $merchantDetail = $this->fixtures->create('merchant_detail', $attribute);

        return $merchantDetail->merchant;
    }
}
