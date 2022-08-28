<?php

namespace RZP\Models\Settlement\OndemandPayout;

use Config;

use RZP\Exception;
use RZP\Error\Error;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\HashAlgo;
use RZP\Models\FundAccount;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Settlement\Ondemand\Attempt;

class Service extends Base\Service
{
    const X_RAZORPAY_SIGNATURE = 'x-razorpay-signature';

    const MOCK_WEBHOOK_KEY = 'DUMMY_KEY';

    public function createSettlementOndemandPayout($settlementOndemand, $requestDetails):array
    {
        return $this->core()->createSettlementOndemandPayout($settlementOndemand, $requestDetails);
    }

    public function statusUpdate($input, $headers,  $rawContent):array
    {
        if($this->mode === 'live')
        {
            $key = Config::get('applications.razorpayx_client.live.ondemand_x_merchant.webhook_key');
        }
        else
        {
            $key = self::MOCK_WEBHOOK_KEY;
        }

        $receivedSignature = $headers[self::X_RAZORPAY_SIGNATURE][0];

        $expectedSignature = hash_hmac(HashAlgo::SHA256,  $rawContent, $key);

        if ($receivedSignature !== $expectedSignature)
        {
            throw new Exception\InvalidArgumentException(
                'unauthorised request : signature send by payouts does not match the expected signature');
        }

        if (isset($input['event']) === false ||
            isset($input['payload']['payout']['entity']) === false)
        {
            throw new Exception\InvalidArgumentException(
                'payout sent an invalid status update payload');
        }

        $event = $input['event'];

        $payoutData = $input['payload']['payout']['entity'];

        $this->trace->info(TraceCode::SETTLEMENT_ONDEMAND_PAYOUT_WEBHOOK_UPDATE, [
            'payout_response'                  => $payoutData,
            'event'                            => $input['event'],
        ]);

        if($payoutData['fund_account_id'] ===
            Config::get('applications.razorpayx_client.live.ondemand_contact.fund_account_id'))
        {
            return $this->repo->transaction(function () use ($event, $payoutData)
            {
                return (new Attempt\Core)->updateOndemandBulkPayoutStatus($event, $payoutData);
            });
        }
        else
        {
            return $this->repo->transaction(function () use ($event, $payoutData)
            {
                return $this->core()->updateOndemandPayoutStatus($event, $payoutData);
            });
        }
    }

    public function updateStatusAfterPayoutRequest($payoutStatus, $payoutId, $settlementOndemandPayout, $response)
    {
        $this->core()->updateStatusAfterPayoutRequest($payoutStatus, $payoutId, $settlementOndemandPayout, $response);
    }

    public function makePayoutRequest($settlementOndemandPayoutId, $currency)
    {
        return $this->core()->makePayoutRequest($settlementOndemandPayoutId, $currency);
    }

    public function initiateReversal($settlementOndemandPayoutId, $merchantId, $failureReason)
    {
        $this->core()->initiateReversal($settlementOndemandPayoutId, $merchantId, $failureReason);
    }
}
