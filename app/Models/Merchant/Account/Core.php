<?php

namespace RZP\Models\Merchant\Account;

use RZP\Models\Pricing;
use RZP\Models\Merchant;

class Core extends Merchant\Core
{
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

    public function uploadFiles(Entity $account, $input)
    {
        $accountDetails = $account->merchantDetail;

        $account->getValidator()->validateInput('files', $input);

        $accountDetails->getValidator()->validateIsNotLocked(true);

        $params = [];

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

        (new Validator)->validateInput('update_details', $input);

        // Convert nested input array to a flat structure
        $inputConverted = Detail::flattenInputArray($input);

        $detailService->setMerchant($account);

        $accountDetails = $detailService->saveMerchantDetails($inputConverted);

        // Convert the flat output back into a the nested structure
        // Structure defined in Detail::$detailMap
        $accountDetails = Detail::expandNestedDetailArray($accountDetails);

        return $accountDetails;
    }
}
