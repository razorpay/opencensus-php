<?php

namespace RZP\Models\Merchant\Account;

use RZP\Models\Merchant;
use RZP\Models\Pricing;

class Core extends Merchant\Core
{
    public function createAccount(array $input, Merchant\Entity $merchant)
    {
        $account = (new Entity)->build($input);

        $account->generateId();

        // $account->setAuditAction(Action::CREATE_account);

        $email['email'] = $input['email'];

        $account->getValidator()->validateInput('unique_email', $email);

        $account->setPricingPlan(Pricing\DefaultPlan::STARTUP_PLAN_ID);

        $account->parent()->associate($merchant);

        $this->repo->saveOrFail($account);

        $this->addMerchantSupportingEntities($account);

        return $account;
    }
}
