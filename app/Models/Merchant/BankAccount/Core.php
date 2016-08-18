<?php

namespace RZP\Models\Merchant\BankAccount;

use RZP\Constants\Mode;
use RZP\Models\Base;
use RZP\Models\Merchant\BankAccount;
use Mail;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    public function createOrChangeBankAccount($input, $merchant)
    {
        $oldBankAccount = $this->repo->bank_account->getBankAccount($merchant);

        if ($oldBankAccount === null)
        {
            return $this->createBankAccount($input, $merchant, $this->mode);
        }

        $newBankAccount = $this->buildBankAccount($input, $merchant, $this->mode);

        if ($newBankAccount->equals($oldBankAccount))
        {
            $this->trace->info(
                TraceCode::MISC_TRACE_CODE,
                [
                    'new' => $newBankAccount->toArray(),
                    'old' => $oldBankAccount->toArray(),
                ]);

            return $oldBankAccount;
        }

        return $this->changeBankAccount($input, $merchant, $oldBankAccount);
    }

    /**
     * This takes the oldBank Account as it's last parameter
     * @param  Array $input Input Array with new bank account details
     * @param  Merchant\Entity $merchant
     * @param  BankAccount\Entity $oldBankAccount
     */
    protected function changeBankAccount($input, $merchant, $oldBankAccount)
    {
        return $this->repo->transaction(
            function() use($merchant, $oldBankAccount, $input)
            {
                $this->repo->delete($oldBankAccount);

                $ba = $this->createBankAccount($input, $merchant, $this->mode);

                $this->sendBankAccountChangeEmail($ba, $merchant);

                return $ba;
            });
    }

    public function createTestBankAccount($merchant)
    {
        $input = array(
            'ifsc_code'             => Entity::SPECIAL_IFSC_CODE,
            'beneficiary_name'      => $merchant->getAttribute('name'),
            'beneficiary_email'     => $merchant->getAttribute('email'),
            'account_number'        => random_integer(11),
            'beneficiary_address1'  => 'Bengaluru Palace',
            'beneficiary_address2'  => 'Palace Rd, Vasanth Nagar',
            'beneficiary_city'      => 'Banglore',
            'beneficiary_state'     => 'KA',
            'beneficiary_country'   => 'IN',
            'beneficiary_pin'       => '560052',
            'beneficiary_mobile'    => '18002700323',
        );

        $ba = $this->createBankAccount($input, $merchant, Mode::TEST);

        return $ba;
    }

    /**
     * All bank account creation happens via this function
     *
     * @param  array  $input
     * @param  string $mode
     * @return BankAccount\Entity
     */
    protected function createBankAccount($input, $merchant, $mode)
    {
        $ba = $this->buildBankAccount($input, $merchant, $mode);

        $this->repo->saveOrFail($ba);

        return $ba;
    }

    protected function buildBankAccount($input, $merchant, $mode)
    {
        $ba = new BankAccount\Entity;

        $ba->setConnection($mode);

        $ba = $ba->build($input);

        $ba->getValidator()->validateIfscCode($mode);

        $ba->merchant()->associate($merchant);

        return $ba;
    }

    protected function sendBankAccountChangeEmail($newBankAccount, $merchant)
    {
        if ($this->mode === Mode::TEST)
        {
            return;
        }

        $label = $merchant->getBillingLabelElseName();

        $subject = 'Razorpay | Bank account change successful for ' . $label;

        $data = array_merge($merchant->toArray(), $newBankAccount->toArray());

        $emailView = 'emails.merchant.bankaccount_change';

        $this->sendEmail($emailView, $subject, $data);
    }

    protected function sendEmail($template, $subject, $data)
    {
        Mail::queue($template, $data, function($message) use ($data, $subject){

            $message->to($data['email'], $data['name'])
                ->subject($subject);
        });
    }
}
