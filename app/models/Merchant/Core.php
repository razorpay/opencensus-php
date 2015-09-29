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

        (new Methods\Core)->setAllPaymentBanks($merchant);

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
        $merchantBalance = Merchant\Balance::buildFromMerchant($merchant);

        $merchantBalance->setConnection($mode);

        $this->repo->updateBalance($merchantBalance);

        return $merchantBalance;
    }
}
