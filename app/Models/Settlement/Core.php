<?php

namespace RZP\Models\Settlement;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\Settlement;
use RZP\Models\Transaction;
use RZP\Constants\Timezone;

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

            $channel = Channel::getChannelFromGateway($gateway);

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
        }
        else
        {
            (new Validator)->validateInput('nodal_transfer', $input);

            $amount = $input[Entity::AMOUNT];

            $channel = $input[Entity::CHANNEL];
        }

        if ($amount > 0)
        {
            $amount = number_format($amount / 100, 2, '.', '');

            $nodalClass = 'RZP\Models\FundTransfer\\' . ucwords($channel) . '\NodalAccount';

            $response = (new $nodalClass())->generateTransferFile($amount);
        }
        else
        {
            $response = [
                'message' => 'amount to be transferred is zero or negative'
            ];
        }

        return $response;
    }

    /**
     * Sends a webhook to the merchantg for successfully settled payments
     *
     * @param Entity $settlement
     * @param array  $transactions
     */
    public function sendSettlementProcessedWebhook(Entity $settlement, array $transactions)
    {
        $eventPayload = [
            Entity::MAIN => $settlement,
            Entity::WITH => [
                'transactions' => $transactions,
            ]
        ];

        $this->app['events']->fire('api.settlement.processed', $eventPayload);
    }
}
