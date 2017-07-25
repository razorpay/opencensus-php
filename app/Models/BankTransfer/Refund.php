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
    public function process(array $input, Merchant $merchant)
    {
        $bankTransfer = $this->getBankTransfer($input['payment']);

        $bankAccount = $this->createPayerBankAccount($bankTransfer, $merchant);

        $this->createRefundAttemptEntity($input, $bankAccount);
    }

    protected function getBankTransfer(array $payment)
    {
        $bankTransfer = $this->repo
                             ->bank_transfer
                             ->findByPaymentId($payment['id']);

        return $bankTransfer;
    }

    protected function createPayerBankAccount(Entity $bankTransfer, Merchant $merchant)
    {
        $bankAccount = new BankAccount\Entity;

        $bankAccountInput = $this->getBankAccountInput($bankTransfer);

        $bankAccount = $bankAccount->build($bankAccountInput, 'addVirtualBankAccount');

        $bankAccount->merchant()->associate($merchant);

        $bankAccount->associateVirtualAccount($bankTransfer->virtualAccount);

        $this->repo->saveOrFail($bankAccount);

        return $bankAccount;
    }

    protected function createRefundAttemptEntity(array $input, BankAccount\Entity $bankAccount)
    {
        $fundTransferAttempt = new FundTransferAttempt\Entity;

        $fundTransferAttempt->fillAndGenerateId([
            FundTransferAttempt\Entity::CHANNEL => Channel::KOTAK,
            FundTransferAttempt\Entity::VERSION => FundTransferAttempt\Version::V3,
            FundTransferAttempt\Entity::STATUS  => FundTransferAttempt\Status::CREATED,
        ]);

        $fundTransferAttempt->setSourceType(FundTransferAttempt\Type::REFUND);
        $fundTransferAttempt->setSourceId($input['refund']['id']);

        $fundTransferAttempt->merchant()->associate($bankAccount->merchant);

        $fundTransferAttempt->bankAccount()->associate($bankAccount);

        $this->repo->saveOrFail($fundTransferAttempt);

        return $fundTransferAttempt;
    }

    protected function getBankAccountInput(Entity $bankTransfer)
    {
        return [
            BankAccount\Entity::IFSC_CODE        => $bankTransfer->getPayerIfsc(),
            BankAccount\Entity::ACCOUNT_NUMBER   => $bankTransfer->getPayerAccount(),
            BankAccount\Entity::BENEFICIARY_NAME => $bankTransfer->merchant->getBillingLabel(),
        ];
    }
}
