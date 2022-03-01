<?php

namespace RZP\Models\Merchant\Account;

use RZP\Exception;
use RZP\Constants\HyperTrace;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Detail;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Merchant\WebhookV2\Stork;
use RZP\Trace\TraceCode;
use RZP\Trace\Tracer;

class Core extends Merchant\Core
{
    /**
     * Creates a linked account and activates it
     *
     * @param array           $input
     * @param Merchant\Entity $parentMerchant
     *
     * @return Entity
     * @throws \Throwable
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

        $this->app->hubspot->trackLinkedAccountCreation($account->getEmail());

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
        $this->trace->info(
            TraceCode::ACCOUNT_CREATION_REQUEST,
            [
                'input'      => $input,
            ]);

        $this->validatePartnerAccess($partner);

        (new Validator)->validateInput('create_account', $input);

        $input = Helper::modifyAccountInput($input);

        $subMerchant = $this->repo->transactionOnLiveAndTest(function () use ($input, $partner)
        {
            $subMerchant = $this->createSubmerchantAndAssociatedEntities($partner, $input);

            $this->submitDetailsAndActivateIfApplicable($partner, $subMerchant);

            return $subMerchant;
        });

        // since response from Stork during affected owners cache invalidation can come even before the above DB transaction
        // completion, send cache invalidation request again. Jira - https://razorpay.atlassian.net/browse/PRTS-1085
        $this->invalidateAffectedOwnersCache($subMerchant->getId());

        $subMerchantDetails = (new Detail\Core)->getMerchantDetails($subMerchant);

        $currentActivationStatus = $subMerchantDetails->getActivationStatus();

        if($currentActivationStatus=="activated")
        {
            $this->app['terminals_service']->requestDefaultMerchantInstruments($subMerchant->getId());
        }

        $businessType = $input[Constants::BUSINESS_ENTITY] ?? null;

        $dimensions = $this->getDimensionsForAccountMetrics($partner, $businessType);

        $this->trace->count(Metric::ACCOUNT_V1_CREATE_SUCCESS_TOTAL, $dimensions);

        return $subMerchant;
    }

    public function fetchAccountByExternalId(Merchant\Entity $partner, string $externalId)
    {
        $input[Entity::EXTERNAL_ID] = $externalId;

        $accounts = $this->listAccounts($partner, $input);

        return $accounts->firstOrFail();
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

        $input = Helper::modifyAccountInput($input);

        $account = $this->repo->transactionOnLiveAndTest(function () use ($input, $partner, $accountId)
        {
            $subMerchant = $this->fillSubMerchant($accountId, $input);
            $subMerchant = $this->fillSubMerchantDetails($subMerchant, $input);

            $this->upsertMerchantEmails($subMerchant, $input);

            return $subMerchant;
        });

        $dimensions = $this->getDimensionsForAccountMetrics($partner, null);

        $this->trace->count(Metric::ACCOUNT_V1_EDIT_SUCCESS_TOTAL, $dimensions);

        return $account;
    }

    public function validatePartnerAccess(Merchant\Entity $partner, $accountId = null)
    {
        Tracer::inspan(['name' => HyperTrace::VALIDATE_PARTNER_ACCESS], function () use ($partner, $accountId) {
            $partner->getValidator()->validateIsAggregatorPartner($partner);

            if ($accountId !== null) {
                Entity::verifyIdAndSilentlyStripSign($accountId);

                $isMapped = $this->isMerchantManagedByPartner($accountId, $partner->getId());

                if ($isMapped === false) {
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_MERCHANT_NOT_UNDER_PARTNER,
                        null,
                        [
                            'account_id' => $accountId,
                            'partner_id' => $partner->getId(),
                        ]);
                }
            }
        });
    }

    /**
     * Returns a list of submerchant accounts associated with a partner
     *
     * @param Merchant\Entity $partner
     * @param array           $input
     *
     * @return PublicCollection
     * @throws Exception\BadRequestException
     */
    public function listAccounts(Merchant\Entity $partner, array $input): PublicCollection
    {
        $input[Merchant\Constants::COUNT] = $input[Merchant\Constants::COUNT] ?? Constants::DEFAULT_ACCOUNT_COUNT;

        $this->validatePartnerAccess($partner);

        (new Validator)->validateInput('list_accounts', $input);

        $appIds = $this->getPartnerApplicationIds($partner);

        $relations = ['merchantDetail', 'features', 'emails', 'bankAccount'];

        return $this->repo
                    ->merchant
                    ->fetchSubmerchantsByAppIds($appIds, $input, $relations);
    }

    public function invalidateAffectedOwnersCache(string $merchantId)
    {
        (new Stork('live'))->invalidateAffectedOwnersCache($merchantId);
        (new Stork('test'))->invalidateAffectedOwnersCache($merchantId);
    }

    protected function submitDetailsAndActivateIfApplicable(Merchant\Entity $partner, Merchant\Entity $subMerchant)
    {
        $merchantDetailCore = new Detail\Core;

        // if partner is handling kyc, directly activate the submerchant
        if ($partner->isKycHandledByPartner() === true)
        {
            $merchantDetailCore->submitActivationForm($subMerchant);

            $input = [
                Detail\Entity::ACTIVATION_STATUS => Detail\Status::ACTIVATED,
            ];

            $merchantDetailCore->updateActivationStatus($subMerchant, $input, $partner);
        }
        else
        {
            // auto submit the activation form if all requirements are met
            $input = [
                Detail\Entity::SUBMIT => '1',
            ];

            $merchantDetailCore->saveMerchantDetails($input, $subMerchant);
        }
    }

    protected function createSubmerchantAndAssociatedEntities(Merchant\Entity $partner, array $input): Merchant\Entity
    {
        $this->repo->assertTransactionActive();

        Helper::validateCreateInputForKyc($partner, $input);

        $subMerchantCreateInput = Helper::getSubMerchantCreateInput($input);

        // this creates only test balance
        $subMerchantArray = (new Merchant\Service)->createSubMerchant($subMerchantCreateInput, $partner);
        $subMerchantId    = Entity::verifyIdAndStripSign($subMerchantArray[Entity::ID]);

        $subMerchant = $this->fillSubMerchant($subMerchantId, $input);
        $subMerchant = $this->fillSubMerchantDetails($subMerchant, $input);

        $this->fillBankAccountNotes($subMerchant, $input);

        $this->updateActivationFlows($partner, $subMerchant);

        $this->upsertMerchantEmails($subMerchant, $input);

        return $subMerchant;
    }

    protected function updateActivationFlows(Merchant\Entity $partner, Merchant\Entity $subMerchant)
    {
        $merchantDetailsCore = new Detail\Core;

        $merchantDetailsCore->autoUpdateMerchantActivationFlows($subMerchant, null, $partner);

        // fetch merchant details and save to db as above method does not save it
        $subMerchantDetails = $merchantDetailsCore->getMerchantDetails($subMerchant);

        $this->repo->saveOrFail($subMerchantDetails);
    }

    /**
     * This fills notes only in test mode.
     * The notes will be copied to live mode once bank account is created in live mode
     *
     * @param Merchant\Entity $subMerchant
     * @param array           $input
     */
    protected function fillBankAccountNotes(Merchant\Entity $subMerchant, array $input)
    {
        $notes = Helper::getBankAccountNotesFromInput($input);

        if (empty($notes) === false)
        {
            $bankAccount = $this->repo->bank_account->getBankAccountOnConnection($subMerchant, Mode::TEST);

            $bankAccount->setNotes($notes);

            $this->repo->bank_account->saveOrFail($bankAccount);
        }
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

        $merchantDetailsCore = new Detail\Core;

        $subMerchantDetails = $merchantDetailsCore->editMerchantDetailFields($subMerchant, $detailInput);

        $subMerchantDetails->getValidator()->validateMerchantHasRegisteredAddress();

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

    private function getDimensionsForAccountMetrics(Merchant\Entity $partner, $businessType): array
    {
        $dimensions = [
            'partner_name' => $partner->getName() // adding this because v1 apis are exposed to limited partners
        ];

        if (empty($businessType) === false)
        {
            $dimensions['submerchant_business_type'] = $businessType;
        }

        return $dimensions;
    }
}
