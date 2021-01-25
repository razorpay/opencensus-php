<?php

namespace RZP\Models\Merchant\AccountV2;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Account\Entity;
use RZP\Models\Merchant\Account\Constants;
use RZP\Trace\TraceCode;

class Core extends Merchant\Core
{
    public function createAccountV2(Merchant\Entity $partner, array $input): Merchant\Entity
    {
        $this->trace->info(TraceCode::ACCOUNT_CREATION_V2_REQUEST, ['input' => $input,]);

        $accountCoreV1 = new Merchant\Account\Core();

        $accountCoreV1->validatePartnerAccess($partner);

        (new Validator)->validateInput('create_account', $input);

        $account = $this->repo->transactionOnLiveAndTest(function () use ($input, $partner)
        {
            $subMerchant = $this->createSubmerchantAndAssociatedEntities($partner, $input);

            $this->submitDetailsAndActivateIfApplicable($partner, $subMerchant);

            return $subMerchant;
        });

        return $account;
    }

    public function fetchAccountV2(string $accountId)
    {
        $accountCoreV1 = new Merchant\Account\Core();

        $accountCoreV1->validatePartnerAccess($this->merchant, $accountId);

        Entity::verifyIdAndStripSign($accountId);

        $relations = ['merchantDetail', 'features', 'emails'];

        return $this->repo
            ->merchant
            ->findOrFailPublicWithRelations($accountId, $relations);
    }

    public function editAccountV2(Merchant\Entity $partner, string $accountId, array $input)
    {
        $accountCoreV1 = new Merchant\Account\Core();

        $accountCoreV1->validatePartnerAccess($partner, $accountId);

        Entity::verifyIdAndStripSign($accountId);

        $this->validateAccountSuspension($accountId);

        (new Validator)->validateInput('edit_account', $input);

        $account = $this->repo->transactionOnLiveAndTest(function () use ($input, $partner, $accountId)
        {
            $subMerchant = $this->fillSubMerchant($accountId, $input);
            $subMerchant = $this->fillSubMerchantDetails($subMerchant, $input);

            $this->upsertMerchantEmails($subMerchant, $input);

            return $subMerchant;
        });

        return $account;
    }

    protected function createSubmerchantAndAssociatedEntities(Merchant\Entity $partner, array $input): Merchant\Entity
    {
        $this->repo->assertTransactionActive();

        $subMerchantCreateInput = InputHelper::getSubMerchantCreateInput($input);

        // this creates only test balance
        $subMerchantArray = (new Merchant\Service)->createSubMerchant($subMerchantCreateInput, $partner);
        $subMerchantId    = Entity::verifyIdAndStripSign($subMerchantArray[Entity::ID]);

        $subMerchant = $this->fillSubMerchant($subMerchantId, $input);
        $subMerchant = $this->fillSubMerchantDetails($subMerchant, $input);

        $this->upsertMerchantEmails($subMerchant, $input);

        return $subMerchant;
    }

    protected function fillSubMerchant(string $subMerchantId, array $input): Merchant\Entity
    {
        $this->repo->assertTransactionActive();

        $subMerchant = $this->repo->merchant->findOrFailPublic($subMerchantId);

        $subMerchantInput = InputHelper::getSubMerchantInput($input);

        $merchantCore = new Merchant\Core;

        $merchantCore->editConfig($subMerchant, $subMerchantInput);

        return $subMerchant;
    }

    protected function fillSubMerchantDetails(Merchant\Entity $subMerchant, array $input): Merchant\Entity
    {
        $this->repo->assertTransactionActive();

        $detailInput = InputHelper::getSubMerchantDetailInput($input);

        $merchantDetailsCore = new Detail\Core;

        $merchantDetailsCore->saveMerchantDetails($detailInput, $subMerchant);

        return $subMerchant;
    }

    protected function upsertMerchantEmails(Merchant\Entity $subMerchant, array $input)
    {
        if (isset($input[Constants::CONTACT_INFO]) === false)
        {
            return;
        }

        $fieldNames = [
            Constants::SUPPORT,
            Constants::CHARGEBACK,
            Constants::REFUND,
            Constants::DISPUTE,
        ];

        $emailCore = new Merchant\Email\Core;

        foreach ($fieldNames as $fieldName)
        {
            if (array_key_exists($fieldName, $input[Constants::CONTACT_INFO]) === true)
            {
                $emailInput = $input[Constants::CONTACT_INFO][$fieldName];

                $emailInput[Constants::TYPE] = $fieldName;

                if (isset($emailInput[Constants::POLICY_URL]) === true)
                {
                    $policyUrl = $emailInput[Constants::POLICY_URL];

                    $emailInput[Constants::URL] = $policyUrl;

                    unset($emailInput[Constants::POLICY_URL]);
                }

                $emailCore->upsert($subMerchant, $emailInput);
            }
        }
    }

    protected function submitDetailsAndActivateIfApplicable(Merchant\Entity $partner, Merchant\Entity $subMerchant)
    {
        $merchantDetailCore = new Detail\Core;

        // auto submit the activation form if all requirements are met
        $input = [
            Detail\Entity::SUBMIT => '1',
        ];

        $merchantDetailCore->saveMerchantDetails($input, $subMerchant);

    }

    public function validateAccountSuspension(string $accountId)
    {
        $subMerchant = $this->repo->merchant->findOrFailPublic($accountId);

        if ($subMerchant->isSuspended() === true)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_SUSPENDED,  null);
        }
    }
}
