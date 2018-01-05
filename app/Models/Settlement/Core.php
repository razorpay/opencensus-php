<?php

namespace RZP\Models\Settlement;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Adjustment;
use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Models\Payment;
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
        (new Validator)->validateInput('nodal_transfer', $input);

        if (isset($input[Payment\Entity::GATEWAY]) === true)
        {
            $gateway = $input[Entity::GATEWAY];

            $channel = Channel::getChannelFromGateway($gateway);

            $amount = $this->getAmountFromPaymentsForLastDay($gateway);
        }
        else
        {
            $amount = $input[Entity::AMOUNT];

            $channel = $input[Entity::CHANNEL];
        }

        $response = [
            'message' => 'Amount to be transferred is zero or negative'
        ];

        if ($amount > 0)
        {
            $destination = $input[Entity::DESTINATION];

            $merchantId = Settlement\NodalAccount::ACCOUNT_MAP[$this->mode][$destination];

            $adjInput = [
                Adjustment\Entity::MERCHANT_ID  => $merchantId,
                Adjustment\Entity::AMOUNT       => $amount,
                Adjustment\Entity::CHANNEL      => $channel,
                Adjustment\Entity::DESCRIPTION  => 'Nodal Nodal Transfer',
                Adjustment\Entity::CURRENCY     => 'INR'
            ];

            $adjustment = (new Adjustment\Service)->addAdjustment($adjInput);

            return $adjustment;
        }

        return $response;
    }

    public function addBeneficiary(string $channel, array $input)
    {
        (new Validator)->validateInput($channel . '_add_beneficiary', $input);

        $nodalClass = 'RZP\Models\FundTransfer\\' . ucwords($channel) . '\NodalAccount';

        return (new $nodalClass())->addBeneficiary($input);
    }

    protected function getAmountFromPaymentsForLastDay(string $gateway) : int
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

        return (int)$amount;
    }
}
