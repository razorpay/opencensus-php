<?php

namespace RZP\Models\Batch\Processor;

use Carbon\Carbon;
use RZP\Models\Batch;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Models\Payment\Method;
use RZP\Constants\Environment;
use RZP\Exception\BadRequestException;
use RZP\Models\Feature\Constants as Feature;
use RZP\Models\Payment\Refund\Entity as RefundEntity;
use RZP\Models\Payment\Processor\Processor as PaymentProcessor;

class Refund extends Base
{
    protected function processEntry(array & $entry)
    {
        $paymentId = trim($entry[Batch\Header::PAYMENT_ID]);

        /** @var Payment\Entity $payment */
        $payment = $this->repo->payment->findByPublicIdAndMerchant(
                                            $paymentId,
                                            $this->merchant);

        if (($this->merchant->isFeatureEnabled(Feature::DISABLE_CARD_REFUNDS) === true) and
            ($payment->getMethod() === Method::CARD))
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_CARD_REFUND_NOT_ALLOWED,
                Payment\Entity::METHOD,
                [
                    Payment\Entity::MERCHANT_ID => $this->merchant->getId(),
                    RefundEntity::PAYMENT_ID    => $paymentId,
                ]);
        }

        $paymentProcessor = (new PaymentProcessor($this->merchant));

        $input = [
            Payment\Refund\Entity::AMOUNT => (string) $entry[Batch\Header::AMOUNT],
            Payment\Refund\Entity::NOTES  => $entry[Batch\Header::NOTES] ?? [],
        ];

        $refund = $paymentProcessor->refundPaymentViaBatchEntry($payment, $input, $this->batch);

        // Update the entry with output values
        $entry[Batch\Header::STATUS]          = Batch\Status::SUCCESS;
        $entry[Batch\Header::REFUND_ID]       = $refund->getPublicId();
        $entry[Batch\Header::REFUNDED_AMOUNT] = $refund->getAmount();
    }

    /**
     * Besides what parent's method does:
     * - Sets aggregate processed amount of batch entity.
     *
     * @param $entries
     */
    protected function postProcessEntries(array & $entries)
    {
        parent::postProcessEntries($entries);

        $processedAmount = 0;

        foreach ($entries as $entry)
        {
            if ($entry[Batch\Header::STATUS] === Batch\Status::SUCCESS)
            {
                $processedAmount += $entry[Batch\Header::REFUNDED_AMOUNT];
            }
        }

        $this->batch->setProcessedAmount($processedAmount);
    }

    /**
     * Schedules the batch at: current time + 1 hour.
     * Skipping scheduling for QA envs, otherwise all the existing tests related to refund batch
     * will be affected.
     */
    public function addSettingsIfRequired(& $input)
    {
        if (Environment::isEnvironmentQA($this->env) === false)
        {
            $input[Batch\Constants::SCHEDULE] = Carbon::now(Timezone::IST)->addHour()->addMinutes(10)->getTimestamp() * 1000;
        }
    }
}
