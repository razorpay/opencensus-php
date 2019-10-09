<?php

namespace RZP\Models\Merchant\Account;

use RZP\Exception;
use RZP\Models\State;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Constants\Product;
use RZP\Models\Merchant\Detail;
use RZP\Models\Base\PublicCollection;

class Core extends Merchant\Core
{
    /**
     * Creates a linked account and activates it
     *
     * @param array           $input
     * @param Merchant\Entity $parentMerchant
     *
     * @return Entity
     */
    public function createLinkedAccount(array $input, Merchant\Entity $parentMerchant): Entity
    {
        //
        // When a linked account is created, mainly, 2 functions are executed -
        // 1. createSubMerchant
        // 2. saveMerchantDetails
        //
        // The first function creates a merchant entity and other supporting
        // entities like MerchantDetail, ScheduleTask, Method, etc. It also creates
        // a BankAccount entity in the Test database with dummy values so that the
        // merchant can start the integration using the test mode immediately.
        //
        // The second function accepts the actual bank account details of the merchant
        // and runs the createOrChangeBankAccount function call. This function creates
        // or updates the bankAccount entity in the database corresponding to the mode
        // that is extracted from the basic auth key used. Hence, if the key used
        // corresponds to live mode, a BankAccount entity will be created in the live
        // mode, but if it is used in the test mode, the entity that is already created
        // with the dummy data will be updated with the actual data and no entity will
        // be created in the Live mode,
        //
        // Hence, forcing the input mode to be live mode here, if not already.
        //
        $this->setModeAndDefaultConnection(Mode::LIVE);

        (new Validator)->validateInput('create', $input);

        $merchantDetailsInput    = $this->getMerchantDetailsFromInput($input);
        $bankAccountDetailsInput = $this->getBankAccountDetailsFromInput($input[Entity::BANK_ACCOUNT] ?? []);
        $merchantDetailsInput    = array_merge($merchantDetailsInput, $bankAccountDetailsInput);

        $account = $this->repo->transactionOnLiveAndTest(function () use (
            $input,
            $parentMerchant,
            $merchantDetailsInput)
        {
            $account = (new Merchant\Core)->createSubMerchant(
                $input,
                $parentMerchant,
                true,
                true);

            (new Detail\Core)->saveMerchantDetails($merchantDetailsInput, $account);

            return $account;

        });

        $this->trace->info(
            TraceCode::ACCOUNT_CREATED,
            [
                'account_id' => $account->getId(),
                'parent_id'  => $parentMerchant->getId(),
                'input'      => $input,
            ]);

        return $account->reload();
    }

    /**
     * Creates a submerchant account and activates it
     *
     * @param Merchant\Entity $partner
     * @param array           $input
     *
     * @return Merchant\Entity
     * @throws Exception\BadRequestException
     * @throws \Throwable
     */
    public function createAccount(Merchant\Entity $partner, array $input): Merchant\Entity
    {
        $this->validatePartnerAccess($partner);

        (new Validator)->validateInput('create_account', $input);

        $partner->getValidator()->validateMerchantEmailUnique($input[Constants::EMAIL], $partner->getOrgId());

        $account = $this->repo->transactionOnLiveAndTest(function () use ($input, $partner)
        {
            $subMerchant = $this->createSubmerchantAndAssociatedEntities($partner, $input);

            $this->activateSubMerchantIfApplicable($partner, $subMerchant);

            return $subMerchant;
        });

        return $account;
    }

    public function fetchAccount(string $accountId)
    {
        $relations = ['merchantDetail', 'features', 'emails', 'bankAccount'];

        return $this->repo
                    ->merchant
                    ->findOrFailPublicWithRelations($accountId, $relations);
    }

    public function editAccount(Merchant\Entity $partner, string $accountId, array $input)
    {
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

    public function validatePartnerAccess(Merchant\Entity $partner, $accountId = null)
    {
        $partner->getValidator()->validateIsAggregatorPartner($partner);

        if ($accountId !== null)
        {
            Entity::verifyIdAndStripSign($accountId);

            $isMapped = $this->isMerchantMappedToNonPurePlatformPartner($accountId, $partner->getId());

            if ($isMapped === false)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_MERCHANT_NOT_UNDER_PARTNER,
                    null,
                    [
                        'account_id' => $accountId,
                        'partner_id' => $partner->getId(),
                    ]);
            }
        }
    }

    /**
     * Returns a list of submerchant accounts associated with a partner
     *
     * @param Merchant\Entity $partner
     * @param array           $input
     *
     * @return PublicCollection
     * @throws Exception\BadRequestException
     * @throws Exception\LogicException
     */
    public function listAccounts(Merchant\Entity $partner, array $input): PublicCollection
    {
        $this->validatePartnerAccess($partner);

        (new Validator)->validateInput('list_accounts', $input);

        $appIds = $this->getPartnerApplicationIds($partner);

        $relations = ['merchantDetail', 'features', 'emails', 'bankAccount'];

        return $this->repo
                    ->merchant
                    ->fetchSubmerchantsByAppIds($appIds, $input, $relations);
    }

    protected function activateSubMerchantIfApplicable(Merchant\Entity $partner, Merchant\Entity $subMerchant)
    {
        // if partner is handling kyc, directly activate the submerchant
        if ($partner->isKycHandledByPartner() === true)
        {
            $this->activateSubMerchant($subMerchant);
        }
        else
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
        }
    }

    protected function activateSubMerchant(Merchant\Entity $subMerchant)
    {
        $this->repo->assertTransactionActive();

        $subMerchantDetails = $subMerchant->merchantDetail;

        $stateCore          = new State\Core;
        $merchantDetailCore = new Detail\Core;

        $stateData = [
            State\Entity::NAME => Detail\Status::UNDER_REVIEW,
        ];

        $stateCore->createForMakerAndEntity($stateData, $subMerchant, $subMerchantDetails);

        $merchantDetailCore->checkAndMarkHasKeyAccess($subMerchantDetails, $subMerchant);

        $merchantDetailCore->markSubmittedAndLock($subMerchantDetails);

        $merchantDetailCore->updateActivationSource($subMerchant, Product::PRIMARY);

        // bank account can be optional in cases where submerchant payments can get settled to partner
        if ($subMerchantDetails->hasBankAccountDetails() === true)
        {
            $merchantDetailCore->setBankAccountForMerchant($subMerchantDetails);
        }

        $subMerchantDetails->edit([Detail\Entity::ACTIVATION_STATUS => Detail\Status::ACTIVATED]);

        $this->repo->saveOrFail($subMerchantDetails);

        $subMerchant->activate();

        $this->repo->saveOrFail($subMerchant);

        $stateData = [
            State\Entity::NAME => Detail\Status::ACTIVATED,
        ];

        $stateCore->createForMakerAndEntity($stateData, $subMerchant, $subMerchantDetails);

        // after activating, create live balance
        $this->createBalance($subMerchant, 'live');
    }

    protected function createSubmerchantAndAssociatedEntities(Merchant\Entity $partner, array $input): Merchant\Entity
    {
        $this->repo->assertTransactionActive();

        $subMerchantCreateInput = Helper::getSubMerchantCreateInput($input);

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

        $subMerchantInput = Helper::getSubMerchantInput($input);

        $subMerchant->fill($subMerchantInput);

        $this->repo->saveOrFail($subMerchant);

        return $subMerchant;
    }

    protected function fillSubMerchantDetails(Merchant\Entity $subMerchant, array $input): Merchant\Entity
    {
        $this->repo->assertTransactionActive();

        $detailInput = Helper::getSubMerchantDetailInput($input);

        $subMerchantDetails = (new Detail\Core)->getMerchantDetails($subMerchant, $detailInput);

        $subMerchantDetails->edit($detailInput);

        (new Detail\Core)->autoUpdateMerchantCategoryDetailsIfApplicable($subMerchantDetails, $subMerchant);

        $subMerchantDetails->getValidator()->validateMerchantHasRegisteredAddress();

        $this->repo->saveOrFail($subMerchantDetails);

        $subMerchant = $this->syncMerchantEntityFields($subMerchant, $detailInput);

        $this->repo->saveOrFail($subMerchant);

        return $subMerchant;
    }

    protected function upsertMerchantEmails(Merchant\Entity $subMerchant, array $input)
    {
        if (isset($input[Constants::PROFILE]) === false)
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
            if (array_key_exists($fieldName, $input[Constants::PROFILE]) === true)
            {
                $emailInput = $input[Constants::PROFILE][$fieldName];

                $emailInput[Constants::TYPE] = $fieldName;

                $emailCore->upsert($subMerchant, $emailInput);
            }
        }
    }

    /**
     * Extracts and returns the merchant details fields from the input.
     *
     * @param array $input
     *
     * @return array
     */
    protected function getMerchantDetailsFromInput(array $input): array
    {
        $accountDetails = $input[Entity::ACCOUNT_DETAILS];

        $businessName = $accountDetails[Entity::BUSINESS_NAME];
        $businessType = $accountDetails[Entity::BUSINESS_TYPE];

        $merchantDetails = [
            Detail\Entity::BUSINESS_NAME => $businessName,
            Detail\Entity::BUSINESS_TYPE => Detail\BusinessType::getIndexFromKey($businessType),

            // TODO: take `submit` from input for the next version
            Detail\Entity::SUBMIT        => '1',
        ];

        return $merchantDetails;
    }

    /**
     * Extracts and returns the bank account details fields from the input.
     *
     * @param array $input
     *
     * @return array
     */
    protected function getBankAccountDetailsFromInput(array $input): array
    {
        $whitelistedBankAccountKeys = [
            Entity::IFSC_CODE,
            Entity::ACCOUNT_NUMBER,
            Entity::BENEFICIARY_NAME,
        ];

        $bankAccountDetails = array_only($input, $whitelistedBankAccountKeys);

        $bankAccountToDetailAttributesMap = Entity::$bankAccountToDetailAttributesMap;

        $merchantDetails = [];

        foreach($bankAccountDetails as $key => $value)
        {
            $merchantDetails[$bankAccountToDetailAttributesMap[$key]] = $value;
        }

        return $merchantDetails;
    }
}
