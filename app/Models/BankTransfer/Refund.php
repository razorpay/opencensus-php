<?php

namespace RZP\Models\BankTransfer;

use RZP\Models\Base;
// use RZP\Constants\Mode;
// use RZP\Trace\TraceCode;
// use RZP\Models\Payment\Method;
// use RZP\Models\VirtualAccount;
// use RZP\Models\Merchant\Account;
// use RZP\Models\Currency\Currency;
// use RZP\Models\Payment\Entity as Payment;
// use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\Payout;
use RZP\Models\BankAccount;
use RZP\Models\Currency\Currency;
// use RZP\Models\Payment\Processor\Processor as PaymentProcessor;

class Refund extends Base\Core
{
    public function process(array $input)
    {
        $bankTransfer = $this->getBankTransfer($input['payment']);

        $bankAccount = $this->createPayerBankAccount($bankTransfer);

        $this->createPayoutWithoutTransaction($input, $bankAccount);
    }

    protected function getBankTransfer(array $payment)
    {
        $bankTransfer = $this->repo
                             ->bank_transfer
                             ->findByPaymentId($payment['id']);

        $this->virtualAccount = $bankTransfer->virtualAccount;

        return $bankTransfer;
    }

    protected function createPayerBankAccount(Entity $bankTransfer)
    {
        $bankAccount = new BankAccount\Entity;

        $bankAccountInput = $this->getBankAccountInput($bankTransfer);

        $bankAccount = $bankAccount->build($bankAccountInput, 'addVirtualBankAccount');

        $bankAccount->merchant()->associate($this->merchant);

        // $bankAccount->associateVirtualAccount($virtualAccount);

        $this->repo->saveOrFail($bankAccount);

        return $bankAccount;
    }

    protected function createPayoutWithoutTransaction(array $input, BankAccount\Entity $bankAccount)
    {
        $payoutInput = $this->getPayoutInput($input, $bankAccount);

        $payout = (new Payout\Core)->createPayoutEntity($payoutInput, $this->merchant);

        $this->repo->saveOrFail($payout);
    }

    protected function getBankAccountInput(Entity $bankTransfer)
    {
        return [
            BankAccount\Entity::IFSC_CODE        => $bankTransfer->getPayerIfsc(),
            BankAccount\Entity::ACCOUNT_NUMBER   => $bankTransfer->getPayerAccount(),
            BankAccount\Entity::BENEFICIARY_NAME => 'beneficiary name',
        ];
    }

    protected function getPayoutInput(array $input, BankAccount\Entity $bankAccount)
    {
        $payoutArray = [
            Payout\Entity::METHOD          => Payout\Method::BANK_TRANSFER,
            Payout\Entity::AMOUNT          => $input['refund']['amount'],
            Payout\Entity::CURRENCY        => Currency::INR,
            Payout\Entity::DESTINATION     => $bankAccount->getPublicId(),
        ];

        if ($this->virtualAccount->hasCustomer() === true)
        {
            $customer = $this->virtualAccount->customer;

            $payoutArray[Payment::CUSTOMER_ID] = $customer->getPublicId();
        }

        return $payoutArray;
    }
}
