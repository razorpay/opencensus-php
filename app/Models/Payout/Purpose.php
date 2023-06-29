<?php

namespace RZP\Models\Payout;

use RZP\Constants\Mode;
use RZP\Models\Merchant;
use RZP\Models\Settings;
use RZP\Traits\TrimSpace;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Feature\Constants as Features;
use RZP\Models\Admin\Service as AdminService;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\FundTransfer\Attempt\Purpose as FTAPurpose;

class Purpose
{
    use TrimSpace;

    const REFUND               = 'refund';
    const CASHBACK             = 'cashback';
    const SALARY               = 'salary';
    const UTILITY_BILL         = 'utility bill';
    const VENDOR_BILL          = 'vendor bill';
    const VENDOR_ADVANCE       = 'vendor advance';
    const BUSINESS_DISBURSAL   = 'business disbursal';
    const CREDIT_CARD_BILL     = 'credit card bill';
    const PAYOUT               = 'payout';
    const INTER_ACCOUNT_PAYOUT = 'inter_account_payout';
    const RZP_FEES             = 'rzp_fees';
    const RZP_TAX_PAYMENT      = 'rzp_tax_pay';
    const MERCHANT_ID          = 'merchant_id';
    const RZP_FUND_MANAGEMENT  = 'RZP Fund Management';

    protected static $default = [
        self::REFUND,
        self::CASHBACK,
        self::PAYOUT,
        self::SALARY,
        self::UTILITY_BILL,
        self::VENDOR_BILL,
        self::VENDOR_ADVANCE,
    ];

    protected static $defaultPurposeTypeMap = [
        self::REFUND          => FTAPurpose::REFUND,
        self::CASHBACK        => FTAPurpose::REFUND,
        self::PAYOUT          => FTAPurpose::SETTLEMENT,
        self::SALARY          => FTAPurpose::SETTLEMENT,
        self::UTILITY_BILL    => FTAPurpose::SETTLEMENT,
        self::VENDOR_BILL     => FTAPurpose::SETTLEMENT,
        self::VENDOR_ADVANCE  => FTAPurpose::SETTLEMENT,
    ];

    protected static $internalPurposeTypeMap = [
        self::RZP_FEES            => FTAPurpose::SETTLEMENT,
        self::RZP_TAX_PAYMENT     => FTAPurpose::SETTLEMENT,
        self::RZP_FUND_MANAGEMENT => FTAPurpose::RZP_FUND_MANAGEMENT,
    ];

    protected static $finopsPurposeTypeMap = [
        self::INTER_ACCOUNT_PAYOUT => FTAPurpose::INTER_ACCOUNT_PAYOUT,
    ];

    protected static $masterCardSendPurposeTypeMap = [
        self::BUSINESS_DISBURSAL => FTAPurpose::REFUND,
        self::CREDIT_CARD_BILL   => FTAPurpose::REFUND,
    ];

    public static function isInDefaults(string $purpose): bool
    {
        return (in_array($purpose, array_keys(self::$defaultPurposeTypeMap), true) === true);
    }

    public static function isInFinops(string $purpose): bool
    {
        return (in_array($purpose, array_keys(self::$finopsPurposeTypeMap), true) === true);
    }

    public static function isInInternal(string $purpose = null): bool
    {
        return (in_array($purpose, array_keys(self::$internalPurposeTypeMap), true) === true);
    }

    public static function isInMasterCardSend(string $purpose = null): bool
    {
        return (in_array($purpose, array_keys(self::$masterCardSendPurposeTypeMap), true) === true);
    }

    public function setPurposeAndTypeForPayout(Entity $payout,
                                               string $purpose,
                                               bool $isInternal = false)
    {
        $merchant = $payout->merchant;

        $trimmedPurpose = $this->trimSpaces($purpose);

        //Validate if the merchantId has access to interAccount(access has only to finops team merchantId's)
        //set purpose and purpose type if it is valid and return
        //or throw exception
        if (self::isInFinops($trimmedPurpose) === true)
        {
            $merchantId = $merchant->getMerchantId();

            // RZP_INTERNAL_ACCOUNTS contains the list of razorpay internal accounts
            $rzpInternalAccounts = (new AdminService)->getConfigKey(
                ['key' => ConfigKey::RZP_INTERNAL_ACCOUNTS]);
            $rzpInternalMerchantIds = [];
            for ($i = 0; $i < count($rzpInternalAccounts); $i++)
            {
                array_push($rzpInternalMerchantIds, $rzpInternalAccounts[$i][self::MERCHANT_ID]);
            }
            if (in_array($merchantId, $rzpInternalMerchantIds,true) === true)
            {
                $payout->setPurpose($trimmedPurpose);
                $payout->setPurposeType(self::$finopsPurposeTypeMap[$trimmedPurpose]);

                return;
            }

            throw new BadRequestValidationFailureException(
                "Purpose '$purpose' is an internal purpose used by Razorpay and cannot be accessed.",
                Entity::MERCHANT_ID);
        }

        // If $purpose is one of the defaults, set and return
        if (self::isInDefaults($trimmedPurpose) === true)
        {
            $payout->setPurpose($trimmedPurpose);
            $payout->setPurposeType(self::$defaultPurposeTypeMap[$trimmedPurpose]);

            return;
        }

        // These purposes are only available to merchants enabled on payout to cards feature flag.
        if(($merchant->isFeatureEnabled(Features::PAYOUT_TO_CARDS) === true) and
           (self::isInMasterCardSend($trimmedPurpose) === true))
        {
            $payout->setPurpose($trimmedPurpose);
            $payout->setPurposeType(self::$masterCardSendPurposeTypeMap[$trimmedPurpose]);

            return;
        }

        // If payout is an internally generated payout and purpose is part of internal purpose, set and return
        if (($isInternal === true) and
            (self::isInInternal($trimmedPurpose) === true))
        {
            $payout->setPurpose($trimmedPurpose);
            $payout->setPurposeType(self::$internalPurposeTypeMap[$trimmedPurpose]);

            return;
        }

        //
        // If purpose sent is not one of the defaults defined. We hence fetch and
        // check against the custom list, if available.
        //
        $custom = $this->getCustom($merchant);

        $trimmedCustom = $this->trimSpaces($custom);

        if (isset($trimmedCustom[$trimmedPurpose]) === true)
        {
            $payout->setPurpose($trimmedPurpose);
            $payout->setPurposeType($trimmedCustom[$trimmedPurpose]);

            return;
        }

        //
        // If not found anywhere, throw an exception. We expect payout purpose to be
        // defined before being used.
        //
        throw new BadRequestValidationFailureException(
            'Invalid purpose: ' . $purpose,
            Entity::PURPOSE,
            ['payout_id' => $payout->getId()]);
    }

    // Todo: To decide if MCS purposes need to be supported here
    public function setPurposeAndTypeForNewCompositePayoutFlow(Entity $payout, string $purpose)
    {
        if (self::isInDefaults($purpose) === false)
        {
            throw new BadRequestValidationFailureException(
                'Invalid purpose: ' . $purpose,
                Entity::PURPOSE,
                ['payout_id' => $payout->getId()]);
        }

        $payout->setPurpose($purpose);
        $payout->setPurposeType(self::$defaultPurposeTypeMap[$purpose]);
    }

    public function validatePurpose(Merchant\Entity $merchant, string $purpose)
    {
        $trimPurpose = $this->trimSpaces($purpose);

        if(self::isInDefaults($trimPurpose) === true)
        {
            return;
        }

        if (self::isInFinops($trimPurpose) === true)
        {
            return;
        }

        if(($merchant->isFeatureEnabled(Features::PAYOUT_TO_CARDS) === true) and
           (self::isInMasterCardSend($trimPurpose) === true))
        {
            return;
        }

        // If purpose sent is not one of the defaults and finopsType defined. We hence fetch and
        // check against the custom list, if available.
        //
        $custom = $this->getCustom($merchant);

        $trimCustoms = $this->trimSpaces($custom);

        if (isset($trimCustoms[$trimPurpose]) === true)
        {
            return;
        }

        //
        // If not found anywhere, throw an exception. We expect payout purpose to be
        // defined before being used.
        //
        throw new BadRequestValidationFailureException(
            'Invalid purpose: ' . $purpose,
            null,
            [
                Entity::MERCHANT_ID => $merchant->getPublicId(),
                Entity::PURPOSE     => $purpose

            ]);
    }

    public function getAll(Merchant\Entity $merchant): array
    {
        $default = self::$defaultPurposeTypeMap;

        $custom = $this->getSettingsAccessor($merchant)->all()->toArray();

        $finops = self::$finopsPurposeTypeMap;

        $merchantId = $merchant->getId();

        // RZP_INTERNAL_ACCOUNTS contains the list of razorpay internal accounts
        $rzpInternalAccounts = (new AdminService)->getConfigKey(
            ['key' => ConfigKey::RZP_INTERNAL_ACCOUNTS]);
        $rzpInternalMerchantIds = [];
        for ($i = 0; $i < count($rzpInternalAccounts); $i++)
        {
            array_push($rzpInternalMerchantIds, $rzpInternalAccounts[$i][self::MERCHANT_ID]);
        }

        // array_merge cannot be used here because numeric keys in php arrays
        // can cause the function to give unexpected results.

        if (in_array($merchantId, $rzpInternalMerchantIds, true) === true)
        {
            $all = $custom + $default + $finops;
        }
        else
        {
            $all = $custom + $default;
        }

        // Add MasterCard Send purposes if payout to cards feature is enabled.
        if ($merchant->isFeatureEnabled(Features::PAYOUT_TO_CARDS) === true)
        {
            $all = $all + self::$masterCardSendPurposeTypeMap;
        }

        $purposes = new PublicCollection;

        foreach ($all as $purpose => $type)
        {
            $purposes->push([
                Entity::PURPOSE      => $purpose,
                Entity::PURPOSE_TYPE => $type,
            ]);
        }

        return $purposes->toArrayWithItems();
    }

    public function getCustom(Merchant\Entity $merchant): array
    {
        return $this->getSettingsAccessor($merchant)->all()->toArray();
    }

    public function addNewCustom(string $purpose, string $type, Merchant\Entity $merchant)
    {
        $allCustomKeys = array_keys($this->getSettingsAccessor($merchant)->all()->toArray());

        $merchantId = $merchant->getId();

        $allCustomKeysTrimmed = $this->trimSpaces($allCustomKeys);

        $maxPurposes = Validator::MAX_PURPOSES_ALLOWED;

        if (count($allCustomKeysTrimmed) >= $maxPurposes)
        {
            throw new BadRequestValidationFailureException(
                "You have reached the maximum limit ($maxPurposes) of custom payout purposes that can be created.",
                Entity::PURPOSE_TYPE);
        }

        $trimmedPurpose = $this->trimSpaces($purpose);

        // If purpose is 'rzp_fees' or 'inter_account_payout' or 'RZP Fund Management' we won't allow adding it as a custom purpose
        if (self::isInInternal($trimmedPurpose) or self::isInFinops(strtolower($trimmedPurpose)) === true)
        {
            throw new BadRequestValidationFailureException(
                "Purpose '$trimmedPurpose' is an internal purpose used by Razorpay and cannot be added.",
                Entity::PURPOSE);
        }

        if ((self::isInDefaults(strtolower($trimmedPurpose))) or
            (array_search_ci($trimmedPurpose, $allCustomKeysTrimmed) !== false))
        {
            throw new BadRequestValidationFailureException(
                "Purpose '$trimmedPurpose' is already defined and cannot be added.",
                Entity::PURPOSE);
        }

        if(($merchant->isFeatureEnabled(Features::PAYOUT_TO_CARDS) === true) and
           (self::isInMasterCardSend(strtolower($trimmedPurpose)) === true))
        {
            throw new BadRequestValidationFailureException(
                "Purpose '$trimmedPurpose' is an internal purpose used for payout to cards and cannot be added.",
                Entity::PURPOSE);
        }

        $data = [
            $trimmedPurpose => $this->trimSpaces($type)
        ];

        $this->getSettingsAccessor($merchant)
             ->upsert($data)
             ->save();
    }

    public function addNewBulkCustom(array $input, Merchant\Entity $merchant)
    {
        $allCustomKeys = array_keys($this->getSettingsAccessor($merchant)->all()->toArray());

        $isPayoutToCardsFeatureEnabled = $merchant->isFeatureEnabled(Features::PAYOUT_TO_CARDS);

        $allCustomKeysTrimmed = $this->trimSpaces($allCustomKeys);

        $new_purpose = array();
        $purpose_type = array();
        foreach($input as $item){

            $trimmedPurpose = $this->trimSpaces($item['purpose']);

            if (self::isInInternal($trimmedPurpose) === true){
                continue;
            }
            if ((self::isInDefaults(strtolower($trimmedPurpose))) or
                (array_search_ci($trimmedPurpose, $allCustomKeysTrimmed) !== false)) {
                continue;
            }

            if(($isPayoutToCardsFeatureEnabled === true) and
               (self::isInMasterCardSend(strtolower($trimmedPurpose)) === true))
            {
                continue;
            }

            $data = [
                "purpose" => $trimmedPurpose,
                "purpose_type" => $item['purpose_type']
            ];

            (new Validator)->validateInput('create_purpose', $data);

            array_push($new_purpose, $trimmedPurpose);
            array_push($purpose_type,$item['purpose_type']);
        }

        $maxPurposes = Validator::MAX_PURPOSES_ALLOWED + Validator::MAX_PURPOSES_ALLOWED_TO_XPAYROLL;

        if (count($new_purpose) + count($allCustomKeysTrimmed) > $maxPurposes)
        {
            throw new BadRequestValidationFailureException(
                "You have reached the maximum limit ($maxPurposes) of custom payout purposes that can be created.",
                Entity::PURPOSE_TYPE);
        }

        $data = array();
        $i=0;

        foreach($new_purpose as $item){
            $data[$item] = $this->trimSpaces($purpose_type[$i]);
            $i++;
        }

        $this->getSettingsAccessor($merchant)
            ->upsert($data)
            ->save();
    }

    protected function getSettingsAccessor(Merchant\Entity $merchant): Settings\Accessor
    {
        return Settings\Accessor::for($merchant, Settings\Module::PAYOUT_PURPOSE, Mode::LIVE);
    }

    public function trimPurpose(Merchant\Entity $merchant, string $purpose, string $type)
    {
        $this->getSettingsAccessor($merchant)
             ->delete($purpose)
             ->save();

        $trimmedPurpose = trim(str_replace('\n', '', $purpose));

        $data = [
            $trimmedPurpose => $type
        ];

        $allCustomKeys = $this->getCustom($merchant);

        if (array_key_exists($trimmedPurpose, $allCustomKeys) === false)
        {
            $this->getSettingsAccessor($merchant)
                ->upsert($data)
                ->save();
        }
    }
}
