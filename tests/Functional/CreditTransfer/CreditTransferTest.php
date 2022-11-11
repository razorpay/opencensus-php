<?php

namespace RZP\Tests\Functional\CreditTransfer;

use Queue;
use RZP\Tests\Functional\TestCase;
use RZP\Jobs\QueuedCreditTransferRequests;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;

class CreditTransferTest extends TestCase
{
    use TestsBusinessBanking;
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/CreditTransferTestData.php';

        parent::setUp();

        $this->ba->privateAuth();

        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $this->app['config']->set('applications.banking_account_service.mock', true);
    }

    public function testCreditTransferCreateAsync()
    {
        Queue::fake();

        // setting up VA as Yes Bank Nodal Account VA
        $this->bankAccount->setAccountNumber("7878780111222");
        $this->bankAccount->setIfsc("YESB0CMSNOC");
        $this->bankAccount->save();

        $testData = &$this->testData['testCreditTransferCreateAsync'];

        $testData['request']['content']['payee_details']['account_number'] = $this->bankAccount->getAccountNumber();
        $testData['request']['content']['payee_details']['ifsc_code'] = $this->bankAccount->getIfscCode();

        $this->ba->payoutInternalAppAuth();

        $this->startTest();

        Queue::assertPushed(QueuedCreditTransferRequests::class, 1);
    }
}
