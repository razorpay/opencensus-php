<?php

namespace RZP\Models\Merchant\Account;

use RZP\Exception;
use RZP\Models\User;
use RZP\Models\State;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Constants\Product;
use RZP\Constants\IndianStates;
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

            $this->activateSubMerchant($subMerchant, $subMerchant->merchantDetail);

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

    protected function activateSubMerchant(Merchant\Entity $subMerchant, Detail\Entity $subMerchantDetails)
    {
        $this->repo->assertTransactionActive();

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

        $subMerchantCreateInput = $this->getSubMerchantCreateInput($partner, $input);

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

        if (isset($input[Constants::PROFILE]) === true)
        {
            $subMerchant = $this->fillBrandData($subMerchant, $input);

            if (array_key_exists(Constants::DASHBOARD_DISPLAY, $input[Constants::PROFILE]))
            {
                $subMerchant->setDisplayName($input[Constants::PROFILE][Constants::DASHBOARD_DISPLAY]);
            }
        }

        if (isset($input[Constants::NOTES]) === true)
        {
            $subMerchant->setNotes($input[Constants::NOTES]);
        }

        $this->repo->saveOrFail($subMerchant);

        return $subMerchant;
    }

    protected function fillSubMerchantDetails(Merchant\Entity $subMerchant, array $input): Merchant\Entity
    {
        $this->repo->assertTransactionActive();

        $detailInput = $this->getSubMerchantDetailInput($input);

        $subMerchantDetails = (new Detail\Core)->getMerchantDetails($subMerchant, $detailInput);

        $subMerchantDetails->edit($detailInput);

        (new Detail\Core)->autoUpdateMerchantCategoryDetailsIfApplicable($subMerchantDetails, $subMerchant);

        $this->validateMerchantDetails($subMerchantDetails);

        $this->repo->saveOrFail($subMerchantDetails);

        $subMerchant = $this->syncMerchantEntityFields($subMerchant, $detailInput);

        return $subMerchant;
    }

    protected function validateMerchantDetails(Detail\Entity $subMerchantDetails)
    {
        // check that registered address is present
        if ($subMerchantDetails->hasBusinessRegisteredAddress() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ACCOUNT_REGISTRATION_ADDRESS_REQUIRED,
                Constants::ADDRESSES,
                [
                    'account_id' => $subMerchantDetails->getKey(),
                ]);
        }
    }

    protected function fillBrandData(Merchant\Entity $subMerchant, array $input): Merchant\Entity
    {
        if (isset($input[Constants::PROFILE][Constants::BRAND]) === true)
        {
            $brand = $input[Constants::PROFILE][Constants::BRAND];

            if (isset($brand[Constants::LOGO]) === true)
            {
                $subMerchant->setLogoUrl($brand[Constants::LOGO]);
            }

            if (isset($brand[Constants::ICON]) === true)
            {
                $subMerchant->setIconUrl($brand[Constants::ICON]);
            }

            if (isset($brand[Constants::COLOR]) === true)
            {
                $subMerchant->setBrandColor($brand[Constants::COLOR]);
            }
        }

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

    protected function getSubMerchantCreateInput(Merchant\Entity $partner, array $input)
    {
        // adding partner user id here, as we have to add partner user as member of submerchant merchant account
        return [
            User\Entity::USER_ID   => $partner->primaryOwner()->getId(),
            Merchant\Entity::EMAIL => $input[Constants::EMAIL],
            Merchant\Entity::NAME  => $input[Constants::PROFILE][Constants::NAME],
        ];
    }

    protected function getSubMerchantDetailInput(array $input): array
    {
        $detailInput = [];

        if (isset($input[Constants::EMAIL]) === true)
        {
            $detailInput[Detail\Entity::CONTACT_EMAIL]            = $input[Constants::EMAIL];
            $detailInput[Detail\Entity::TRANSACTION_REPORT_EMAIL] = $input[Constants::EMAIL];
        }

        if (isset($input[Constants::PHONE]) === true)
        {
            $detailInput[Detail\Entity::CONTACT_MOBILE] = $input[Constants::PHONE];
        }

        if (isset($input[Constants::BUSINESS_ENTITY]) === true)
        {
            $businessType = Detail\BusinessType::getIndexFromKey($input[Constants::BUSINESS_ENTITY]);

            $detailInput[Detail\Entity::BUSINESS_TYPE] = $businessType;
        }

        $customFields = $this->getCustomFieldsFromInput($input);

        if (empty($customFields) === false)
        {
            $detailInput[Detail\Entity::CUSTOM_FIELDS] = $customFields;
        }

        if (isset($input[Constants::PROFILE]) === true)
        {
            // fill profile data
            $profileAttributesMapping = [
                Constants::DESCRIPTION    => Detail\Entity::BUSINESS_DESCRIPTION,
                Constants::BUSINESS_MODEL => Detail\Entity::BUSINESS_PAYMENTDETAILS,
                Constants::BILLING_LABEL  => Detail\Entity::BUSINESS_DBA,
                Constants::WEBSITE        => Detail\Entity::BUSINESS_WEBSITE,
                Constants::NAME           => Detail\Entity::BUSINESS_NAME,
            ];

            foreach ($profileAttributesMapping as $key => $value)
            {
                if (array_key_exists($key, $input[Constants::PROFILE]))
                {
                    $detailInput[$value] = $input[Constants::PROFILE][$key];
                }
            }

            $categoryData = [];

            if (isset($input[Constants::PROFILE][Constants::MCC]) === true)
            {
                $mccCode = $input[Constants::PROFILE][Constants::MCC];

                $categoryData = Detail\BusinessSubCategoryMetaData::fetchCategoryAndSubCategoryByMccCode($mccCode);
            }

            $detailInput = array_merge(
                $detailInput,
                $this->getRegisteredAddressFromInput($input),
                $this->getOperationAddressFromInput($input),
                $this->getDocumentDetailsFromInput($input),
                $categoryData
            );
        }

        $detailInput = array_merge($detailInput, $this->getBankAccountFromInput($input));

        return $detailInput;
    }

    protected function getCustomFieldsFromInput(array $input): array
    {
        $customFields = [];

        if (isset($input[Constants::TNC]) === true)
        {
            $customFields[Constants::TNC] = $input[Constants::TNC];
        }

        if ((isset($input[Constants::PROFILE]) === true) and
            (isset($input[Constants::PROFILE][Constants::APPS]) === true))
        {
            $customFields[Constants::APPS] = $input[Constants::PROFILE][Constants::APPS];
        }

        return $customFields;
    }

    protected function getBankAccountFromInput(array $input): array
    {
        if ((isset($input[Constants::SETTLEMENT]) === false) or
            (isset($input[Constants::SETTLEMENT][Constants::FUND_ACCOUNTS][0][Constants::BANK_ACCOUNT])) === false)
        {
            return [];
        }

        $bankAccount = $input[Constants::SETTLEMENT][Constants::FUND_ACCOUNTS][0][Constants::BANK_ACCOUNT];

        return [
            Detail\Entity::BANK_ACCOUNT_NAME           => $bankAccount[Constants::NAME],
            Detail\Entity::BANK_BRANCH_IFSC            => $bankAccount[Constants::IFSC],
            Detail\Entity::BANK_ACCOUNT_NUMBER         => $bankAccount[Constants::ACCOUNT_NUMBER],
        ];
    }

    protected function getDocumentDetailsFromInput(array $input): array
    {
        $details = [];

        if (isset($input[Constants::PROFILE][Constants::IDENTIFICATION]) === false)
        {
            return $details;
        }

        foreach ($input[Constants::PROFILE][Constants::IDENTIFICATION] as $document)
        {
            switch ($document[Constants::TYPE])
            {
                case DocumentType::COMPANY_PAN:
                    $details[Detail\Entity::COMPANY_PAN]      = $document[Constants::IDENTIFICATION_NUMBER];
                    break;
            }
        }

        return $details;
    }

    protected function getRegisteredAddressFromInput(array $input): array
    {
        $registeredAddress = [];

        foreach ($input[Constants::PROFILE][Constants::ADDRESSES] as $address)
        {
            if ($address[Constants::TYPE] === Constants::REGISTERED)
            {
                $mapping = [
                    Constants::LINE1         => Detail\Entity::BUSINESS_REGISTERED_ADDRESS,
                    Constants::LINE2         => Detail\Entity::BUSINESS_REGISTERED_ADDRESS_L2,
                    Constants::CITY          => Detail\Entity::BUSINESS_REGISTERED_CITY,
                    Constants::DISTRICT_NAME => Detail\Entity::BUSINESS_REGISTERED_DISTRICT,
                    Constants::PIN           => Detail\Entity::BUSINESS_REGISTERED_PIN,
                    Constants::COUNTRY       => Detail\Entity::BUSINESS_REGISTERED_COUNTRY,
                ];

                foreach ($mapping as $key => $value)
                {
                    if (isset($address[$key]) === true)
                    {
                        $registeredAddress[$value] = $address[$key];
                    }
                }

                if (isset($address[Constants::STATE]) === true)
                {
                    $stateCode  = IndianStates::getStateCode($address[Constants::STATE]);

                    $registeredAddress[Detail\Entity::BUSINESS_REGISTERED_STATE] = $stateCode;
                }
            }
        }

        return $registeredAddress;
    }

    protected function getOperationAddressFromInput(array $input): array
    {
        $operationAddress = [];

        foreach ($input[Constants::PROFILE][Constants::ADDRESSES] as $address)
        {
            if ($address[Constants::TYPE] === Constants::OPERATION)
            {
                $mapping = [
                    Constants::LINE1         => Detail\Entity::BUSINESS_OPERATION_ADDRESS,
                    Constants::LINE2         => Detail\Entity::BUSINESS_OPERATION_ADDRESS_L2,
                    Constants::CITY          => Detail\Entity::BUSINESS_OPERATION_CITY,
                    Constants::DISTRICT_NAME => Detail\Entity::BUSINESS_OPERATION_DISTRICT,
                    Constants::PIN           => Detail\Entity::BUSINESS_OPERATION_PIN,
                    Constants::COUNTRY       => Detail\Entity::BUSINESS_OPERATION_COUNTRY,
                ];

                foreach ($mapping as $key => $value)
                {
                    if (isset($address[$key]) === true)
                    {
                        $operationAddress[$value] = $address[$key];
                    }
                }

                if (isset($address[Constants::STATE]) === true)
                {
                    $stateCode = IndianStates::getStateCode($address[Constants::STATE]);

                    $operationAddress[Detail\Entity::BUSINESS_OPERATION_STATE] = $stateCode;
                }
            }
        }

        return $operationAddress;
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
