<?php

namespace Models\Merchant\BankAccount;

use Constants\Mode;
use Models\Base;
use Models\Merchant\BankAccount;
use Mail;
use Trace\TraceCode;

class Core extends Base\Core
{
    public function __construct()
    {
        parent::__construct();

        $this->repo = new Repository;

        $this->trace = \Trace::getFacadeRoot();
    }

    public function createOrChangeBankAccount($input, $merchant)
    {
        $bankAccount = $this->repo->getBankAccount($merchant);

        if ($bankAccount === null)
        {
            return $this->createBankAccount($input, $merchant, $this->mode);
        }

        $ba = $this->buildBankAccount($input, $merchant, $this->mode);

        if ($ba->equals($bankAccount))
        {
            $this->trace->info(
                TraceCode::MISC_TRACE_CODE,
                [
                    'old' => $ba->toArray(),
                    'new' => $bankAccount->toArray(),
                ]);

            return $bankAccount;
        }

        return $this->changeBankAccount($input, $merchant, $bankAccount);
    }

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
            'ifsc_code'             => 'RZPB0000000',
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

        $this->generateBeneficiaryCode($ba, $mode);

        $this->repo->saveOrFail($ba);

        return $ba;
    }

    protected function buildBankAccount($input, $merchant, $mode)
    {
        $ba = new BankAccount\Entity;

        $ba->setConnection($mode);

        $ba = $ba->build($input);

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

    /**
     * generate the benificiary code from benificiary name
     *
     * @param  string $name benificiary name
     * @param  string $mode the mode that should be used
     * @return string       generated benificiary code
     */
    protected function generateBeneficiaryCode($ba, $mode)
    {
        $name = $ba->getBeneficiaryName();

        // Caps all then remove spaces then cut first 4.
        $code = substr(str_replace(' ', '', strtoupper($name)), 0, 4);

        $count = $this->repo->getBeneficiaryCodeCountByPattern($code, $mode);

        $count = ($count === 0) ? '' : $count + 1;

        $code .= $count;

        $ba->setBeneficiaryCode($code);
    }

    protected function sendEmail($template, $subject, $data)
    {
        Mail::queue($template, $data, function($message) use ($data, $subject){

            $message->to($data['email'], $data['name'])
                ->subject($subject);
        });
    }
}
