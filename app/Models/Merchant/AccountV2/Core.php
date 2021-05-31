<?php

namespace RZP\Models\Merchant\AccountV2;

use RZP\Exception;
use RZP\Models\User;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Account\Entity;
use RZP\Models\Merchant\Account\Constants;
use RZP\Models\Merchant\Detail\NeedsClarification;

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

        $subMerchantDetails = $this->repo->merchant_detail->findOrFailPublic($accountId);

        if(empty($subMerchantDetails) === false && $subMerchantDetails->getActivationStatus() !== Detail\Status::NEEDS_CLARIFICATION)
        {
            (new Validator)->validateInput('edit_account', $input);
        }
        $account = $this->repo->transactionOnLiveAndTest(function () use ($input, $partner, $accountId, $subMerchantDetails)
        {
            $subMerchant = $this->fillSubMerchant($accountId, $input);
            $subMerchant = $this->fillSubMerchantDetails($subMerchant, $input);

            $this->submitDetailsAndActivateIfApplicable($subMerchant, $subMerchantDetails);

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

        (new Validator())->validateNeedsClarificationRespondedIfApplicable($subMerchant, $detailInput);

        $merchantDetailsCore->saveMerchantDetails($detailInput, $subMerchant);

        $this->updateUserIfApplicable($detailInput, $subMerchant->getEmail());

        $this->updateNCFieldsAcknowledgedIfApplicable($detailInput, $subMerchant);

        return $subMerchant;
    }

    public function updateNCFieldsAcknowledgedIfApplicable(array $input, Merchant\Entity $subMerchant)
    {
        $subMerchantDetails = $subMerchant->merchantDetail;

        if (empty($subMerchantDetails) === true || $subMerchantDetails->getActivationStatus() !== Detail\Status::NEEDS_CLARIFICATION)
        {
            return;
        }

        $needsClarificationCore = new NeedsClarification\Core();

        foreach($input as $field => $value)
        {
            $needsClarificationCore->updateNCFieldAcknowledged($field, $subMerchantDetails);
        }
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

    public function submitDetailsAndActivateIfApplicable(Merchant\Entity $subMerchant, Detail\Entity $merchantDetails)
    {
        $merchantDetailCore = new Detail\Core;

        $input = [
            Detail\Entity::SUBMIT => '1',
        ];

        if(empty($merchantDetails) === true)
        {
            return;
        }

        if($merchantDetails->getActivationStatus() !== Detail\Status::NEEDS_CLARIFICATION)
        {
            // auto submit the activation form if all requirements are met
            $merchantDetailCore->saveMerchantDetails($input, $subMerchant);
        }
        else
        {
            $nonAcknowledgedNCFields = (new NeedsClarification\Core)->getNonAcknowledgedNCFields($subMerchant, $merchantDetails);

            if($nonAcknowledgedNCFields[Merchant\Constants::COUNT] === 0)
            {
                $merchantDetailCore->saveMerchantDetails($input, $subMerchant);
            }
        }

    }

    public function validateAccountSuspension(string $accountId)
    {
        $subMerchant = $this->repo->merchant->findOrFailPublic($accountId);

        if ($subMerchant->isSuspended() === true)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_SUSPENDED,  null);
        }
    }

    private function updateUserIfApplicable(array $input, string $subMerchantEmail): void
    {
        if(isset($input[Detail\Entity::CONTACT_MOBILE]) === true)
        {
            $subMerchantUser = $this->repo->user->getUserFromEmail($subMerchantEmail);

            $payload = [Detail\Entity::CONTACT_MOBILE => $input[Detail\Entity::CONTACT_MOBILE]];

            (new User\Core)->edit($subMerchantUser, $payload);

        }
    }
}
