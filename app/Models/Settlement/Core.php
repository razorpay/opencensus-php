<?php

namespace RZP\Models\Settlement;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\Settlement;
use RZP\Models\Transaction;
use RZP\Constants\Timezone;
use RZP\Listeners\ApiEventSubscriber;

class Core extends Base\Core
{
    public function retrieveById($id)
    {
        Entity::verifyIdAndStripSign($id);

        $setl = $this->repo->settlement->findOrFail($id);

        return $setl;
    }

    public function postInitiateTransfer(array $input): array
    {
        if (isset($input[Payment\Entity::GATEWAY]) === true)
        {
            $gateway = $input[Payment\Entity::GATEWAY];

            $amount = $this->getAmountFromPaymentsForLastDay($gateway);

            $channel = Channel::getChannelFromGateway($gateway);
        }
        else
        {
            (new Validator)->validateInput('nodal_transfer', $input);

            $amount = $input[Entity::AMOUNT];

            $channel = $input[Entity::CHANNEL];
        }

        $response = [
            'message' => 'Amount to be transferred is zero or negative'
        ];

        if ($amount > 0)
        {
            $amount = number_format($amount / 100, 2, '.', '');

            $nodalClass = 'RZP\Models\FundTransfer\\' . ucwords($channel) . '\NodalAccount';

            $response = (new $nodalClass())->initiateTransfer($amount);
        }

        return $response;
    }

    public function addBeneficiary(string $channel, array $input)
    {
        (new Validator)->validateInput($channel . '_add_beneficiary', $input);

        $nodalClass = 'RZP\Models\FundTransfer\\' . ucwords($channel) . '\NodalAccount';

        return (new $nodalClass())->addBeneficiary($input);
    }

    protected function getAmountFromPaymentsForLastDay(string $gateway)
    {
        $from = Carbon::yesterday(Timezone::IST)->getTimestamp();

        $to = Carbon::today(Timezone::IST)->getTimestamp() - 1;

        // Get the amount for captured payments on gateway for last day
        $paymentAmount = $this->repo->payment->getCapturedAmountByGateway($gateway, $from, $to);

        // Get the amount for refunds on gateway for last day
        $refundAmount = $this->repo->refund->getRefundedAmountByGateway($gateway, $from, $to);

        // amount to be transferred in paisa
        $amount = $paymentAmount - $refundAmount;

        // Transfer 99% of the derived amount
        $amount = 0.99 * $amount;

        return $amount;
    }
  
    /**
     * Sends a webhook to the merchant for successfully settled payments
     *
     * @param Entity $settlement
     */
    public function triggerSettlementWebhook(Entity $settlement)
    {
        if ($this->shouldSendWebhook($settlement) === false)
        {
            return;
        }

        $eventPayload = [
            ApiEventSubscriber::MAIN => $settlement
        ];

        $this->app['events']->fire('api.settlement.processed', $eventPayload);

        $this->trace->info(
            TraceCode::SETTLEMENT_PROCESSED_WEBHOOOK_SENT,
            [
                'settlement_id' => $settlement->getId()
            ]);
    }

    /**
     * Returns false,
     *   if the settlement was not processed, or,
     *   if the settlement was not made for a linked account.
     *
     * @param Entity $settlement
     *
     * @return bool
     */
    protected function shouldSendWebhook(Entity $settlement): bool
    {
        // Proceed only if the settlement has successfully processed
        if ($settlement->isStatusProcessed() === false)
        {
            return false;
        }

        // Proceed only if the settlement was made to a linked account
        if ($settlement->merchant->isLinkedAccount() === false)
        {
            return false;
        }

        return true;
    }
}
