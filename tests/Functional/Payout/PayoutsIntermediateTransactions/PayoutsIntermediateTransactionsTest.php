<?php

namespace RZP\Tests\Functional\Payout\PayoutsIntermediateTransactions;

use App;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Constants\Entity as EntityConstants;
use RZP\Models\Payout\Entity as PayoutEntity;
use RZP\Tests\Functional\Helpers\WebhookTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\Payout\PayoutTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Models\Payout\PayoutsIntermediateTransactions\Entity;
use RZP\Models\Payout\PayoutsIntermediateTransactions\Core;
use RZP\Models\Payout\PayoutsIntermediateTransactions\Status;

class PayoutsIntermediateTransactionsTest extends TestCase
{
    use PayoutTrait;
    use WebhookTrait;
    use TestsWebhookEvents;
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;
    use TestsBusinessBanking;

    /** @var Service $client */
    private $client;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/PayoutsIntermediateTransactionsTestData.php';

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

        $this->client = new Core();

        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $this->mockStorkService();

        $this->app['config']->set('applications.banking_account_service.mock', true);
    }

    public function createPayoutForFundAccount($fundAccountId, $balance)
    {
        $content = [
            'account_number'        => $balance->getAccountNumber(),
            'amount'                => 10000,
            'currency'              => 'INR',
            'purpose'               => 'payout',
            'narration'             => 'Payout',
            'fund_account_id'       => 'fa_' . $fundAccountId,
            'mode'                  => 'IMPS',
            'queue_if_low_balance'  => true,
            'notes'                 => [
                'abc' => 'xyz',
            ],
        ];

        $request = [
            'url'       => '/payouts',
            'method'    => 'POST',
            'content'   => $content
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }


    public function testCreatePayoutIntermediateTransaction()
    {
        $this->ba->privateAuth();

        $this->createPayoutForFundAccount('100000000000fa', $this->bankingBalance);

        /** @var PayoutEntity $payout */
        $payout = $this->getDbLastEntity(EntityConstants::PAYOUT);

        $clientResponse = $this->client->create([
                                                    Entity::AMOUNT                 => $payout->getAmount(),
                                                    Entity::PAYOUT_ID              => $payout->getId(),
                                                    Entity::TRANSACTION_ID         => $payout->getTransactionId(),
                                                    Entity::TRANSACTION_CREATED_AT => $payout->transaction->getCreatedAt(),
                                                    Entity::CLOSING_BALANCE        => $payout->transaction->getBalance(),
                                                ]);

        // assertions
        $this->assertEquals(Status::PENDING, $clientResponse->getStatus());
        $this->assertEquals($payout->getAmount(), $clientResponse->getAttribute(Entity::AMOUNT));
        $this->assertEquals($payout->getId(), $clientResponse->getAttribute(Entity::PAYOUT_ID));
        // check relation is working or not
        $this->assertEquals($payout->getId(), $clientResponse->payout->getId());

        // assert null and non null fields
        $this->assertNotNull($clientResponse->getAttribute(Entity::PENDING_AT));
        $this->assertNull($clientResponse->getAttribute(Entity::COMPLETED_AT));
        $this->assertNull($clientResponse->getAttribute(Entity::REVERSED_AT));
    }

    public function testUpdatePayoutIntermediateTransactionsCron()
    {
        $oldDateTime = Carbon::create(2021, 3, 27, 12, 0, 0, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $this->ba->privateAuth();

        $this->createPayoutForFundAccount('100000000000fa', $this->bankingBalance);

        /** @var PayoutEntity $payout */
        $payout = $this->getDbLastEntity(EntityConstants::PAYOUT);

        $clientResponse = $this->client->create([
                                                    Entity::AMOUNT                 => $payout->getAmount(),
                                                    Entity::PAYOUT_ID              => $payout->getId(),
                                                    Entity::TRANSACTION_ID         => $payout->getTransactionId(),
                                                    Entity::TRANSACTION_CREATED_AT => $payout->transaction->getCreatedAt(),
                                                    Entity::CLOSING_BALANCE        => $payout->transaction->getBalance(),
                                                ]);

        $this->createPayoutForFundAccount('100000000000fa', $this->bankingBalance);

        /** @var PayoutEntity $payout2 */
        $payout2 = $this->getDbLastEntity(EntityConstants::PAYOUT);

        $clientResponse2 = $this->client->create([
                                                     Entity::AMOUNT                 => $payout2->getAmount(),
                                                     Entity::PAYOUT_ID              => $payout2->getId(),
                                                     Entity::TRANSACTION_ID         => $payout2->getTransactionId(),
                                                     Entity::TRANSACTION_CREATED_AT => $payout2->transaction->getCreatedAt(),
                                                     Entity::CLOSING_BALANCE        => $payout2->transaction->getBalance(),
                                                ]);


        $this->createPayoutForFundAccount('100000000000fa', $this->bankingBalance);

        Carbon::setTestNow();

        $this->ba->cronAuth('test');

        $request = [
            'url'     => '/payouts_intermediate_transactions/update',
            'method'  => 'POST',
            'content' => [],
        ];

        $response = $this->makeRequestAndGetContent($request);

        $expectedResponse = [
            'payout_ids_marked_completed' => [
                $payout->getId(),
                $payout2->getId(),
            ],
            'payout_ids_marked_reversed'  => []
        ];

        $this->assertArraySelectiveEquals($expectedResponse, $response);

        $intermediateTxn = $this->getDbEntityById(EntityConstants::PAYOUTS_INTERMEDIATE_TRANSACTIONS,
                                                  $clientResponse['id']);
        $intermediateTxn2 = $this->getDbEntityById(EntityConstants::PAYOUTS_INTERMEDIATE_TRANSACTIONS,
                                                   $clientResponse2['id']);

        // uncomment these once code is written for marking completed and reversed
        $this->assertEquals(Status::COMPLETED, $intermediateTxn[Entity::STATUS]);
        $this->assertEquals(Status::COMPLETED, $intermediateTxn2[Entity::STATUS]);
    }
}
