<?php

namespace RZP\Models\BankTransfer;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Transaction\Channel;
use RZP\Models\FundTransfer\Attempt as FundTransferAttempt;

class Refund extends Base\Core
{
    const INVALID_ACC_CREDIT_NARRATION = 'ACC DOESNT EXIST';

    const MAX_NARRATION_LENGTH = 39;

    /**
     * Entry point for refund flow
     *
     * @param array $input
     */
    public function process(array $input)
    {
        $bankTransfer = $this->getBankTransfer($input['payment']);

        $bankTransfer->getValidator()->validateRefundIsAllowed();

        $this->updatePayerBankAccount($bankTransfer);

        $this->createRefundAttemptEntity($input, $bankTransfer);
    }

    /**
     * Gets bank transfer corresponding to the payment that is being refunded
     *
     * @param array $payment
     *
     * @return mixed
     */
    protected function getBankTransfer(array $payment)
    {
        $bankTransfer = $this->repo
                             ->bank_transfer
                             ->findByPaymentId($payment['id']);

        return $bankTransfer;
    }

    /**
     * BankTransfer refunds are powered via payouts through Kotak. To refund a payment,
     * we create a refund and a corresponding fund transfer attempt, which is later
     * picked up by the payouts cron. Assumes payer bank acc is already created.
     *
     * @param array  $input
     * @param Entity $bankTransfer
     *
     * @return FundTransferAttempt\Entity
     */
    protected function createRefundAttemptEntity(array $input, Entity $bankTransfer)
    {
        $fundTransferAttempt = new FundTransferAttempt\Entity;

        $data = [
            FundTransferAttempt\Entity::CHANNEL   => Channel::KOTAK,
            FundTransferAttempt\Entity::VERSION   => FundTransferAttempt\Version::V3,
            FundTransferAttempt\Entity::STATUS    => FundTransferAttempt\Status::CREATED,
            FundTransferAttempt\Entity::NARRATION => $this->getNarration($bankTransfer),
        ];

        $fundTransferAttempt->fillAndGenerateId($data);

        $fundTransferAttempt->setSourceType(FundTransferAttempt\Type::REFUND);
        $fundTransferAttempt->setSourceId($input['refund']['id']);

        $fundTransferAttempt->merchant()->associate($bankTransfer->merchant);

        $fundTransferAttempt->bankAccount()->associate($bankTransfer->payerBankAccount);

        $this->repo->saveOrFail($fundTransferAttempt);

        $this->trace->info(
            TraceCode::FUND_TRANSFER_ATTEMPT_CREATED,
            [
                'id'              => $fundTransferAttempt->getId(),
                'data'            => $data,
                'source_id'       => $input['refund']['id'],
                'merchant_id'     => $bankTransfer->merchant->getId(),
                'bank_account_id' => $bankTransfer->payerBankAccount->getId(),
            ]);

        return $fundTransferAttempt;
    }

    /**
     * As bank transfer refunds show up on the customer's statement as independent transactions,
     * we use the fund transfer attempt narration field to inform of the refund relation.
     *
     * @param Entity $bankTransfer
     *
     * @return string
     */
    protected function getNarration(Entity $bankTransfer)
    {
        $utr = $bankTransfer->getUtr();

        $availableLength = self::MAX_NARRATION_LENGTH - strlen($utr) - 1;

        if ($bankTransfer->isExpected() === true)
        {
            $billingLabel = $bankTransfer->merchant->getBillingLabel();

            $label = substr($billingLabel, 0, $availableLength);
        }
        else
        {
            $label = self::INVALID_ACC_CREDIT_NARRATION;
        }

        return $label . '-' . $utr;
    }

    /**
     * This exists for older bank transfer payments. For new payments, we
     * create the payer bank account with the mapped IFSC. For older ones,
     * if the bank account has no IFSC, we set it to the mapped IFSC now.
     *
     * @param Entity $bankTransfer
     */
    protected function updatePayerBankAccount(Entity $bankTransfer)
    {
        $payerAccount = $bankTransfer->payerBankAccount;

        if ($payerAccount->getIfscCode() === null)
        {
            $ifsc = (new Processor)->getPayerIfsc($bankTransfer);

            $payerAccount->setIfsc($ifsc);

            $this->repo->saveOrFail($payerAccount);
        }
    }
}
