<?php

namespace RZP\Tests\Functional\PayoutSource;

use RZP\Models\Payout;
use RZP\Models\PayoutSource\Core;
use RZP\Tests\Functional\TestCase;
use RZP\Models\PayoutSource\Entity;
use RZP\Tests\Functional\Helpers\WebhookTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;

class PayoutSourceTest extends TestCase
{
    use WebhookTrait;
    use DbEntityFetchTrait;
    use TestsBusinessBanking;
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/PayoutSourceTestData.php';

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

        $this->mockStorkService();
    }

    public function testCreatePayoutSourceForPayout()
    {
        $this->createPayout();

        /** @var Payout\Entity $payout */
        $payout = $this->getDbLastEntity('payout');

        $input = [
            Entity::SOURCE_ID   => 'ECDyjIKwCWEFmh',
            Entity::SOURCE_TYPE => 'vendor_payments',
            Entity::PRIORITY    => 1,
        ];

        (new Core)->create($input, $payout);

        /** @var Entity $payoutSource */
        $payoutSource = $this->getDbLastEntity('payout_source');

        $this->assertEquals('ECDyjIKwCWEFmh', $payoutSource->getSourceId());
        $this->assertEquals('vendor_payments', $payoutSource->getSourceType());
        $this->assertEquals(1, $payoutSource->getPriority());
        $this->assertEquals($payout->getId(), $payoutSource->getPayoutId());
    }

    protected function createPayout()
    {
        $request = [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'mode'            => 'NEFT',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ];

        $this->makeRequestAndGetContent($request);
    }
}
