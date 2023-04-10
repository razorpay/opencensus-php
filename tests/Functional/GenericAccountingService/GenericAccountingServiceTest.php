<?php

namespace Functional\GenericAccountingService;

use Mockery;
use RZP\Tests\Traits\MocksSplitz;
use RZP\Tests\Functional\TestCase;
use RZP\Services\GenericAccountingIntegration\Service;
use RZP\Models\Payout\SourceUpdater\Core as SourceUpdater;
use RZP\Models\Payout\SourceUpdater\GenericAccountingUpdater;

class GenericAccountingServiceTest extends TestCase
{
    use MocksSplitz;


    public function testExperimentEnabled()
    {
        $resp = GenericAccountingUpdater::isGAIExperimentEnabled("test_merchant_id");

        $this->assertFalse($resp);

        $this->app['config']->set('app.generic_ai_enabled_experiment_result_mock', true);

        $resp = GenericAccountingUpdater::isGAIExperimentEnabled("test_merchant_id");

        $this->assertTrue($resp);
    }

    public function testPayoutStatusPushForPayoutLinkAsSource()
    {
        $plMock = Mockery::mock('RZP\Services\PayoutLinks');

        $this->app->instance('payout-links', $plMock);

        $gaiMock = Mockery::mock('RZP\Services\GenericAccountingIntegration\Service');

        $this->app->instance('accounting-integration-service', $gaiMock);

        $this->app['config']->set('app.generic_ai_enabled_experiment_result_mock', true);

        // For processed payout, both PL and GAI should be called
        $payout = $this->fixtures->create('payout', [
            'status' => 'processed'
        ]);

        $this->fixtures->create('payout_source', [
            'payout_id' => $payout->getId(),
            'source_id' => 'poutlk_1',
            'source_type' => 'payout_links',
            'priority' => 1
        ]);

        $plMock->shouldReceive('pushPayoutStatus')->times(1);

        $gaiMock->shouldReceive('pushPayoutStatusUpdate')->times(1);

        SourceUpdater::update($payout);

        // For reversed payout, both PL and GAI should be called
        $payout2 = $this->fixtures->create('payout', [
            'status' => 'reversed'
        ]);

        $this->fixtures->create('payout_source', [
            'payout_id' => $payout2->getId(),
            'source_id' => 'poutlk_2',
            'source_type' => 'payout_links',
            'priority' => 1
        ]);

        $plMock->shouldReceive('pushPayoutStatus')->times(1);

        $gaiMock->shouldReceive('pushPayoutStatusUpdate')->times(1);

        SourceUpdater::update($payout2);

        //For status updates other than processed, reversed GAI should not be called
        $payout3 = $this->fixtures->create('payout', [
            'status' => 'processing'
        ]);

        $this->fixtures->create('payout_source', [
            'payout_id' => $payout3->getId(),
            'source_id' => 'poutlk_3',
            'source_type' => 'payout_links',
            'priority' => 1
        ]);

        $plMock->shouldReceive('pushPayoutStatus')->times(1);

        $gaiMock->shouldReceive('pushPayoutStatusUpdate')->times(0);

        SourceUpdater::update($payout3);

        // If experiment is disabled, gai should not be called
        $this->app['config']->set('app.generic_ai_enabled_experiment_result_mock', false);

        $payout4 = $this->fixtures->create('payout', [
            'status' => 'processed'
        ]);

        $this->fixtures->create('payout_source', [
            'payout_id' => $payout4->getId(),
            'source_id' => 'poutlk_4',
            'source_type' => 'payout_links',
            'priority' => 1
        ]);

        $plMock->shouldReceive('pushPayoutStatus')->times(1);

        $gaiMock->shouldReceive('pushPayoutStatusUpdate')->times(0);

        SourceUpdater::update($payout4);
    }

    public function testMakePayoutRequestBody()
    {
        $this->fixtures->create('contact', ['id' => '1000001contact', 'active' => 1]);

        $this->fixtures->create('fund_account:bank_account',
            [
                'id'          => 'D6Z9Jfir2egAUT',
                'source_type' => 'contact',
                'source_id'   => '1000001contact',
            ]);

        $this->fixtures->create('balance',
            [
                'id'             => 'D6Z9Jfir2egAUX',
                'account_number' => "2224440041626904",
                "balance"        => 100000
            ]
        );

        $payout = $this->fixtures->create('payout', [
            'id'              => 'D6Z9Jfir2egAUZ',
            'status'          => 'processed',
            'fund_account_id' => 'D6Z9Jfir2egAUT',
            'pricing_rule_id' => '1nvp2XPMmaRLxb',
            'reference_id'    => 'reference_id',
            'utr'             => 'utr',
            'user_id'         => 'user_id',
            'mode'            => 'NEFT',
            'narration'       => 'narration',
            'balance_id'      => 'D6Z9Jfir2egAUX'
        ]);

        $response = (new Service($this->app))->makePayoutRequestBody($payout);

        $expectedResponse = [
            'id'                     => 'pout_D6Z9Jfir2egAUZ',
            'reference_id'           => "reference_id",
            'merchant_id'            => '10000000000000',
            'status'                 => 'processed',
            'banking_account_number' => "2224440041626904",
            'fund_account_id'        => 'fa_D6Z9Jfir2egAUT',
            'contact_id'             => 'cont_1000001contact',
            'utr'                    => "utr",
            'amount'                 => 100,
            'user_id'                => "user_id",
            'mode'                   => "NEFT",
            'purpose'                => 'refund',
            'currency'               => 'INR',
            'narration'              => "narration"
        ];

        $this->assertArraySelectiveEquals($expectedResponse, $response);
    }
}
