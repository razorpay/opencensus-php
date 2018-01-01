<?php

namespace RZP\Models\Merchant\Account;

use RZP\Models\Pricing;
use RZP\Models\Merchant;

class Core extends Merchant\Core
{
    /**
     * @param array           $input
     * @param Merchant\Entity $merchant
     *
     * @return Entity
     */
    public function createAccount(array $input, Merchant\Entity $merchant) : Entity
    {
        $account = (new Entity)->build($input);

        $account->generateId();

        $email['email'] = $input['email'];

        $account->getValidator()->validateInput('unique_email', $email);

        $account->setPricingPlan(Pricing\DefaultPlan::STARTUP_PLAN_ID);

        $account->parent()->associate($merchant);

        $this->repo->saveOrFail($account);

        $this->addMerchantSupportingEntities($account);

        return $account;
    }
}
