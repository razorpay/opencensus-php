<?php

namespace Models\Merchant;

use Constants\Mode;
use Models\Base;
use Models\Merchant;
use Models\Pricing;
use Models\Terminal;
use Trace\TraceCode;

class Core extends Base\Core
{
    public function __construct()
    {
        parent::__construct();

        $this->repo = new Merchant\Repository;
    }

    public function create($input)
    {
        $merchant = (new Merchant\Entity)->build($input);

        $merchant->setPricingPlan(Pricing\DefaultPlan::STARTUP_PLAN_ID);

        $this->repo->saveOrFail($merchant);

        $this->createBalance($merchant, Mode::TEST);

        $this->createTestBankAccount($merchant);

        (new Methods\Core)->setDefaultMethods($merchant);

        return $merchant;
    }

    public function edit($merchant, $input)
    {
        $merchant->edit($input);

        $this->repo->saveOrFail($merchant);

        $this->trace->info(
            TraceCode::MERCHANT_EDIT,
            [$input]);

        return $merchant;
    }

    public function editEmail($merchant, $input)
    {
        $this->trace->info(
            TraceCode::MERCHANT_EDIT,
            ['old_email' => $merchant->getEmail()],
            ['new_email' => $input['email']]);

        $merchant->edit($input, 'editEmail');

        $this->repo->saveOrFail($merchant);

        return $merchant;
    }

    public function createBalance($merchant, $mode)
    {
        $merchantBalance = Merchant\Balance\Entity::buildFromMerchant($merchant);

        $merchantBalance->setConnection($mode);

        (new Merchant\Balance\Repository)->createBalance($merchantBalance);

        return $merchantBalance;
    }

    public function createTestBankAccount($merchant)
    {
        $attributes = array(
            'ifsc_code'             => 'RZPB0000000',
            'beneficiary_name'      => $merchant->getAttribute('name'),
            'beneficiary_email'     => $merchant->getAttribute('email'),
            'account_number'        => random_integer(11),
            'beneficiary_address1'  => random_alpha_string(14),
            'beneficiary_city'      => 'Mumbai',
            'beneficiary_state'     => 'MH',
            'beneficiary_country'   => 'IN',
            'beneficiary_pin'       => random_integer(6),
            'beneficiary_mobile'    => random_integer(10),
        );

        $ba = (new BankAccount\Entity)->build($attributes);

        $ba->setConnection(Mode::TEST);

        $ba->beneficiary_code = strtoupper(random_alpha_string(4));

        $ba->merchant()->associate($merchant);

        $ba->save();
    }
}
