<?php

namespace RZP\Models\Merchant\Account;

use RZP\Base\Fetch;
use RZP\Models\Merchant;
use RZP\Models\Feature;
use RZP\Constants\Entity as CE;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Merchant\Validator
{
    protected static $createRules = [
        Entity::NAME                                          => 'required|alpha_space_num|max:200',
        Entity::EMAIL                                         => 'required|email',
        Entity::TNC_ACCEPTED                                  => 'required|boolean|in:1',
        Entity::NOTES                                         => 'sometimes|array|max:15',
        Entity::ACCOUNT_DETAILS                               => 'required|array',
        Entity::ACCOUNT_DETAILS . '.' . Entity::BUSINESS_NAME => 'required|string|max:255',
        Entity::ACCOUNT_DETAILS . '.' . Entity::BUSINESS_TYPE => 'required|string|max:100',

        // For following only key presence is validated here.
        // Sub keys are validated in respective validators.
        Entity::BANK_ACCOUNT                                  => 'required|array',

        Entity::CODE                                          => 'sometimes|string|min:3|max:20|regex:"^([0-9A-Za-z-._])+$"',
     ];

    protected static $fetchRules = [
        EsRepository::SEARCH_HITS => 'filled|boolean',
        EsRepository::QUERY       => 'filled|string|min:2|max:100',
        Entity::EMAIL             => 'sometimes|email',
        Fetch::SKIP               => 'sometimes|integer',
        Fetch::COUNT              => 'sometimes|integer',
        Entity::ID                => 'sometimes|string|min:14',
        Entity::CODE              => 'sometimes|string|min:3|max:20|regex:"^([0-9A-Za-z-._])+$"',
    ];

    protected static $createAccountRules = [
        Constants::ENTITY            => 'required|string|in:' . CE::ACCOUNT,
        Constants::BUSINESS_ENTITY   => 'sometimes|string',
        Constants::EXTERNAL_ID       => 'sometimes|string',
        Constants::LEGAL_EXTERNAL_ID => 'sometimes|string',
        Constants::LEGAL_ENTITY_ID   => 'sometimes|string',
        Constants::MANAGED           => 'sometimes|boolean',
        Constants::EMAIL             => 'sometimes|email',
        Constants::PHONE             => 'sometimes|numeric',
        Constants::NOTES             => 'sometimes|notes',
        Constants::PROFILE           => 'required|array',
        Constants::SETTLEMENT        => 'sometimes|array',
        Constants::SETTINGS          => 'sometimes|array',
        Constants::TNC               => 'sometimes|array',
        Constants::CONTACT_INFO      => 'sometimes|array',
    ];

    protected static $editAccountRules = [
        Constants::PHONE           => 'filled|numeric',
        Constants::NOTES           => 'sometimes|notes',
        Constants::PROFILE         => 'sometimes|array',
        Constants::TNC             => 'sometimes|array',
    ];

    protected static $profileRules = [
        Constants::ADDRESSES         => 'required|array|max:2',
        Constants::ADDRESSES . '.*'  => 'filled|array',
        Constants::NAME              => 'required|string',
        Constants::DESCRIPTION       => 'sometimes|string',
        Constants::BUSINESS_MODEL    => 'sometimes|string|custom',
        Constants::MCC               => 'required|numeric',
        Constants::BRAND             => 'sometimes|array',
        Constants::DASHBOARD_DISPLAY => 'sometimes|string',
        Constants::WEBSITE           => 'sometimes|string',
        Constants::APPS              => 'sometimes|array',
        Constants::SUPPORT           => 'sometimes|array',
        Constants::CHARGEBACK        => 'sometimes|array',
        Constants::REFUND            => 'sometimes|array',
        Constants::DISPUTE           => 'sometimes|array',
        Constants::BILLING_LABEL     => 'required|string',
        Constants::IDENTIFICATION    => 'sometimes|array',
        Constants::OWNER_INFO        => 'sometimes|array',
    ];

    protected static $editProfileRules = [
        Constants::ADDRESSES         => 'sometimes|array|max:2',
        Constants::NAME              => 'filled|string',
        Constants::DESCRIPTION       => 'sometimes|string',
        Constants::BUSINESS_MODEL    => 'sometimes|string|custom',
        Constants::MCC               => 'filled|numeric',
        Constants::BRAND             => 'sometimes|array',
        Constants::DASHBOARD_DISPLAY => 'sometimes|string|nullable',
        Constants::WEBSITE           => 'sometimes|string|nullable',
        Constants::APPS              => 'sometimes|array',
        Constants::SUPPORT           => 'sometimes|array',
        Constants::CHARGEBACK        => 'sometimes|array',
        Constants::REFUND            => 'sometimes|array',
        Constants::DISPUTE           => 'sometimes|array',
        Constants::BILLING_LABEL     => 'sometimes|string',
    ];

    protected static $accountAddressRules = [
        Constants::TYPE          => 'required|string|custom:address_type',
        Constants::LINE1         => 'required|string|max:100',
        Constants::LINE2         => 'sometimes|string',
        Constants::CITY          => 'required|string',
        Constants::DISTRICT_NAME => 'sometimes|string',
        Constants::STATE         => 'required|string',
        Constants::PIN           => 'required|string',
        Constants::COUNTRY       => 'required|string',
    ];

    protected static $editAccountAddressRules = [
        Constants::TYPE          => 'required|string|custom:address_type',
        Constants::LINE1         => 'filled|string|max:100',
        Constants::LINE2         => 'filled|string',
        Constants::CITY          => 'filled|string',
        Constants::DISTRICT_NAME => 'filled|string',
        Constants::STATE         => 'filled|string',
        Constants::PIN           => 'filled|string',
        Constants::COUNTRY       => 'filled|string',
    ];

    protected static $brandRules = [
        Constants::ICON  => 'sometimes|string',
        Constants::LOGO  => 'sometimes|string',
        Constants::COLOR => 'sometimes|string',
    ];

    protected static $merchantEmailRules = [
        Constants::EMAIL  => 'required|string',
        Constants::PHONE  => 'required|numeric|digits:10',
        Constants::POLICY => 'sometimes|string|nullable',
        Constants::URL    => 'sometimes|string|nullable',
    ];

    protected static $editMerchantEmailRules = [
        Constants::EMAIL  => 'filled|string',
        Constants::PHONE  => 'filled|numeric|digits:10',
        Constants::POLICY => 'sometimes|string|nullable',
        Constants::URL    => 'sometimes|string|nullable',
    ];

    protected static $settlementRules = [
        Constants::BALANCE_RESERVED => 'sometimes|numeric',
        Constants::SCHEDULES        => 'sometimes|array',
        Constants::FUND_ACCOUNTS    => 'sometimes|array|size:1',
    ];

    protected static $fundAccountRules = [
        Constants::CONTACT_ID    => 'sometimes|string',
        Constants::BANK_ACCOUNT  => 'required|associative_array',
    ];

    protected static $bankAccountRules = [
        Constants::NOTES          => 'sometimes|notes',
        Constants::IFSC           => 'required|string',
        Constants::NAME           => 'required|string',
        Constants::ACCOUNT_NUMBER => 'required|string',
    ];

    protected static $listAccountsRules = [
        Merchant\Constants::COUNT    => 'required|integer|min:1|max:30',
        Merchant\Constants::SKIP     => 'integer',
        Merchant\Entity::EXTERNAL_ID => 'sometimes|string',
    ];

    protected static $documentRules = [
        Constants::TYPE                  => 'required|string|custom:document_type',
        Constants::IDENTIFICATION_NUMBER => 'required|string',
        Constants::DOCUMENT              => 'sometimes|string',
    ];

    protected static $ownerInfoRules = [
        Constants::NAME           => 'required|string',
        Constants::IDENTIFICATION => 'required|array|size:1',
    ];

    protected static $contactInfoRules = [
        Constants::NAME  => 'required|string',
        Constants::EMAIL => 'required|email',
        Constants::PHONE => 'required|numeric',
    ];

    protected static $settingsRules = [
        Constants::PAYMENT => 'sometimes|array|custom:payment_settings',
    ];

    protected static $paymentSettingsRules = [
        Constants::INTERNATIONAL => 'sometimes|boolean',
    ];

    protected static $createAMCLinkedAccountViaAdminRules = [
        Constants::MERCHANT_IDS => 'required|min:0'
    ];

    protected static $createAccountValidators = [
        'profile_input',
        'settlement_input',
        'contact_info_input',
        'settings_input',
    ];

    protected static $editAccountValidators = [
        'edit_profile_input',
    ];

    protected function validateEditProfileInput(array $input)
    {
        if (isset($input[Constants::PROFILE]) === false)
        {
            return;
        }

        $profileInput = $input[Constants::PROFILE];

        $this->validateInput('edit_profile', $profileInput);

        if (isset($profileInput[Constants::ADDRESSES]) === true)
        {
            $this->validateAddresses($profileInput, 'edit');
        }

        $this->validateBrandInput($profileInput);

        $this->validateEmails($profileInput, 'edit');
    }

    protected function validateAddressType($attribute, $value)
    {
        if (in_array(strtolower($value), Constants::$validAddressTypes, true) === false)
        {
            throw new BadRequestValidationFailureException('Invalid address type: ' . $value);
        }
    }

    protected function validateProfileInput(array $input)
    {
        $profileInput = $input[Constants::PROFILE];

        $this->validateInput('profile', $profileInput);

        $this->validateAddresses($profileInput);

        $this->validateBrandInput($profileInput);

        $this->validateEmails($profileInput);

        $this->validateOwnerInfo($profileInput);

        $this->validateIdentificationDocuments($profileInput);
    }

    protected function validateSettingsInput(array $input)
    {
        if (isset($input[Constants::SETTINGS]) === false)
        {
            return;
        }

        $this->validateInput('settings', $input[Constants::SETTINGS]);
    }

    protected function validatePaymentSettings($attribute, $value)
    {
        $this->validateInput('payment_settings', $value);
    }

    protected function validateContactInfoInput(array $input)
    {
        if (isset($input[Constants::CONTACT_INFO]) === false)
        {
            return;
        }

        $this->validateInput('contact_info', $input[Constants::CONTACT_INFO]);
    }

    protected function validateAddresses(array $profileInput, string $action = '')
    {
        foreach ($profileInput[Constants::ADDRESSES] as $address)
        {
            $this->validateInput($action . 'AccountAddress', $address);
        }
    }

    protected function validateIdentificationDocuments(array $profileInput)
    {
        if (isset($profileInput[Constants::IDENTIFICATION]) === false)
        {
            return;
        }

        foreach ($profileInput[Constants::IDENTIFICATION] as $document)
        {
            $this->validateInput('document', $document);
        }
    }

    protected function validateOwnerInfo(array $profileInput)
    {
        if (isset($profileInput[Constants::OWNER_INFO]) === false)
        {
            return;
        }

        $this->validateInput('owner_info', $profileInput[Constants::OWNER_INFO]);

        foreach ($profileInput[Constants::OWNER_INFO][Constants::IDENTIFICATION] as $document)
        {
            $this->validateInput('document', $document);
        }
    }

    protected function validateEmails(array $profileInput, string $action = '')
    {
        $fieldNames = [
            Constants::SUPPORT,
            Constants::CHARGEBACK,
            Constants::REFUND,
            Constants::DISPUTE,
        ];

        foreach ($fieldNames as $fieldName)
        {
            if (isset($profileInput[$fieldName]) === true)
            {
                $this->validateInput($action . 'MerchantEmail', $profileInput[$fieldName]);
            }
        }
    }

    protected function validateBrandInput(array $profileInput)
    {
        if (isset($profileInput[Constants::BRAND]) === true)
        {
            $this->validateInput('brand', $profileInput[Constants::BRAND]);
        }
    }

    protected function validateSettlementInput(array $input)
    {
        if (isset($input[Constants::SETTLEMENT]) === false)
        {
            return;
        }

        $this->validateInput('settlement', $input[Constants::SETTLEMENT]);

        if (isset($input[Constants::SETTLEMENT][Constants::FUND_ACCOUNTS]) === true)
        {
            $fundAccounts = $input[Constants::SETTLEMENT][Constants::FUND_ACCOUNTS];

            foreach ($fundAccounts as $fundAccount)
            {
                $this->validateInput('fund_account', $fundAccount);
                $this->validateInput('bank_account', $fundAccount[Constants::BANK_ACCOUNT]);
            }
        }
    }

    protected function validateDocumentType($attribute, $value)
    {
        DocumentType::validate($value);
    }

    protected function validateBusinessModel($attribute, $value)
    {
        if (in_array($value, Constants::$validBusinessModels, true) === false)
        {
            throw new BadRequestValidationFailureException('Invalid business model: ' . $value);
        }
    }

    public function validateAMCLinkedAccountCreationAllowed(Merchant\Entity $merchant)
    {
        if (($merchant->getCategory() !== Merchant\Constants::AUTO_CREATE_AMC_LINKED_ACCOUNT_MCC[Entity::CATEGORY]) or
            ($merchant->getCategory2() !== Merchant\Constants::AUTO_CREATE_AMC_LINKED_ACCOUNT_MCC[Entity::CATEGORY2]))
        {
            throw new BadRequestValidationFailureException('AMC linked account creation not allowed for this merchant',[
                Entity::MERCHANT_ID   => $merchant->getId()
            ]);
        }

        if($merchant->isFeatureEnabled(Feature\Constants::MARKETPLACE) === false)
        {
            throw new BadRequestValidationFailureException('Feature marketplace not enabled',[
                Entity::MERCHANT_ID   => $merchant->getId()
            ]);
        }
    }
}
