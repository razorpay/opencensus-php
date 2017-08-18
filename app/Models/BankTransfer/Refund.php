<?php

namespace RZP\Models\BankTransfer;

use RZP\Models\Base;
use RZP\Models\Payout;
use RZP\Models\BankAccount;
use RZP\Models\Transaction\Channel;
use RZP\Models\Currency\Currency;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\FundTransfer\Attempt as FundTransferAttempt;

class Refund extends Base\Core
{
    const INVALID_ACC_CREDIT_NARRATION = 'ACC DOESNT EXIST';

    const MAX_NARRATION_LENGTH = 39;

    public function process(array $input, Merchant $merchant)
    {
        $bankTransfer = $this->getBankTransfer($input['payment']);

        $this->createRefundAttemptEntity($input, $bankTransfer);
    }

    protected function getBankTransfer(array $payment)
    {
        $bankTransfer = $this->repo
                             ->bank_transfer
                             ->findByPaymentId($payment['id']);

        return $bankTransfer;
    }

    protected function createRefundAttemptEntity(array $input, Entity $bankTransfer)
    {
        $fundTransferAttempt = new FundTransferAttempt\Entity;

        $fundTransferAttempt->fillAndGenerateId([
            FundTransferAttempt\Entity::CHANNEL   => Channel::KOTAK,
            FundTransferAttempt\Entity::VERSION   => FundTransferAttempt\Version::V3,
            FundTransferAttempt\Entity::STATUS    => FundTransferAttempt\Status::CREATED,
            FundTransferAttempt\Entity::NARRATION => $this->getNarration($bankTransfer),
        ]);

        $fundTransferAttempt->setSourceType(FundTransferAttempt\Type::REFUND);
        $fundTransferAttempt->setSourceId($input['refund']['id']);

        $fundTransferAttempt->merchant()->associate($bankTransfer->merchant);

        $fundTransferAttempt->bankAccount()->associate($bankTransfer->payerBankAccount);

        $this->repo->saveOrFail($fundTransferAttempt);

        return $fundTransferAttempt;
    }

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
}
