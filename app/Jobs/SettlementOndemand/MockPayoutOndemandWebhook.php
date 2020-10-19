<?php

namespace RZP\Jobs\SettlementOndemand;

use App;
use Config;

use RZP\Jobs\Job;
use Carbon\Carbon;
use RZP\Trace\TraceCode;
use RZP\Constants\HashAlgo;
use RZP\Constants\Timezone;
use RZP\Services\RazorpayXClient;
use RZP\Models\Base\UniqueIdEntity;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Settlement\Ondemand;
use RZP\Models\Settlement\OndemandPayout;
use RZP\Models\Settlement\OndemandFundAccount;

class MockPayoutOndemandWebhook extends Job
{
    const MAX_ALLOWED_ATTEMPTS = 3;

    protected $settlementOndemandPayout;

    public $delay = 60;

    public function __construct(string $mode , $settlementOndemandPayout)
    {
        parent::__construct($mode);

        $this->settlementOndemandPayout = $settlementOndemandPayout;
    }

    public function handle()
    {
        try
        {
            parent::handle();

            $this->app = App::getFacadeRoot();
            $this->repo = $this->app['repo'];

            $this->repo->transaction(function()
            {
                $this->mockWebhookRequest($this->settlementOndemandPayout);
            });
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SETTLEMENT_ONDEMAND_TEST_MODE_PAYOUT_UPDATE_WEBHOOK_FAILURE,
                [
                    'payout_id' => $this->settlementOndemandPayout['id'],
                ]);

            if ($this->attempts() <= self::MAX_ALLOWED_ATTEMPTS)
            {
                $this->release(1);
            }
            else
            {
                $this->delete();
            }
        }
    }

    public function mockWebhookRequest($settlementOndemandPayout)
    {
        /** @var OndemandFundAccount\Entity $fundAccount */
        $fundAccount = (new OndemandFundAccount\Repository)
                        ->findByMerchantId($settlementOndemandPayout->merchant->getId());

        $input = [
            'entity' => 'event',
            'event' => 'payout.processed',
            'contains' => ['payout'],
            'payload' => [
                'payout' => [
                    'entity' => [
                        'id' => $settlementOndemandPayout->getPayoutId(),
                        'entity' => 'payout',
                        'fund_account_id' => $fundAccount->getFundAccountId(),
                        'amount' => $settlementOndemandPayout->getAmount(),
                        'currency' => 'INR',
                        'notes' => [],
                        'fees' => 0,
                        'tax' => 0,
                        'status' => 'processed',
                        'purpose' => 'payout',
                        'utr' => random_integer(10),
                        'mode' => $settlementOndemandPayout->getMode(),
                        'reference_id' => $settlementOndemandPayout->getId(),
                        'narration' => 'Acme Fund Transfer',
                        'batch_id' => null,
                        'failure_reason' => null,
                        'created_at' => Carbon::now(Timezone::IST)->getTimestamp(),
                    ]
                ]
            ],
            'created_at' => Carbon::now(Timezone::IST)->getTimestamp()
        ];

        // Using amount 220000 & 880000 for testing reversal event
        if (($settlementOndemandPayout->getAmount() === 220000) or
            ($settlementOndemandPayout->getAmount() === 880000))
        {
            $input['event'] = 'payout.reversed';

            $input['payload']['payout']['entity']['utr'] = null;

            $input['payload']['payout']['entity']['failure_reason'] = 'dummy_reason';
        }

        $rawContent = 'dummy_raw_content';

        $key = OndemandPayout\Service::MOCK_WEBHOOK_KEY;

        $signature = hash_hmac(HashAlgo::SHA256,  $rawContent, $key);

        $headers =['x-razorpay-signature' => [$signature]];

        (new OndemandPayout\Service)->statusUpdate($input, $headers, $rawContent);
    }
}
