<?php

namespace RZP\Models\Merchant\Account;

use RZP\Models\Merchant;
use RZP\Models\Pricing;

class Core extends Merchant\Core
{
    public function createAccount(array $input, Merchant\Entity $merchant) : Entity
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

    public function uploadFiles(Entity $account, $input)
    {
        $accountDetails = $account->merchantDetail;

        $account->getValidator()->validateInput('upload', $input);

        // @todo: Change error
        $accountDetails->getValidator()->validateIsNotLocked();

        foreach ($input as $type => $content)
        {
            $type = FileType::getFieldForType($type);

            $ufhId = (new Merchant\Detail\Service)
                        ->processFileCreation($type, $content, $account, $accountDetails);

            $params[$type] = $ufhId;
        }

        $accountDetails->fill($params);

        $this->repo->saveOrFail($accountDetails);
    }

    public function updateDetails(Entity $account, array $input)
    {
        $detailService = new Merchant\Detail\Service;

        $detailService->setMerchant($account);

        $accountDetails = $detailService->saveMerchantDetails($input);

        return $accountDetails;
    }
}
