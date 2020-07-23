<?php

namespace RZP\Models\Payout;

use RZP\Constants\Mode;
use RZP\Models\Merchant;
use RZP\Models\Settings;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\FundTransfer\Attempt\Purpose as FTAPurpose;

class Purpose
{
    const REFUND          = 'refund';
    const CASHBACK        = 'cashback';
    const SALARY          = 'salary';
    const UTILITY_BILL    = 'utility bill';
    const VENDOR_BILL     = 'vendor bill';
    const PAYOUT          = 'payout';
    const RZP_FEES        = 'rzp_fees';
    const RZP_TAX_PAYMENT = 'rzp_tax_pay';

    protected static $default = [
        self::REFUND,
        self::CASHBACK,
        self::PAYOUT,
        self::SALARY,
        self::UTILITY_BILL,
        self::VENDOR_BILL,
    ];

    protected static $defaultPurposeTypeMap = [
        self::REFUND          => FTAPurpose::REFUND,
        self::CASHBACK        => FTAPurpose::REFUND,
        self::PAYOUT          => FTAPurpose::SETTLEMENT,
        self::SALARY          => FTAPurpose::SETTLEMENT,
        self::UTILITY_BILL    => FTAPurpose::SETTLEMENT,
        self::VENDOR_BILL     => FTAPurpose::SETTLEMENT,
    ];

    protected static $internalPurposeTypeMap = [
        self::RZP_FEES        => FTAPurpose::SETTLEMENT,
        self::RZP_TAX_PAYMENT => FTAPurpose::SETTLEMENT,
    ];

    public static function isInDefaults(string $purpose): bool
    {
        return (in_array($purpose, array_keys(self::$defaultPurposeTypeMap), true) === true);
    }

    public static function isInInternal(string $purpose = null): bool
    {
        return (in_array($purpose, array_keys(self::$internalPurposeTypeMap), true) === true);
    }

    public function setPurposeAndTypeForPayout(Entity $payout,
                                               string $purpose,
                                               bool $isInternal = false)
    {
        // If $purpose is one of the defaults, set and return
        if (self::isInDefaults($purpose) === true)
        {
            $payout->setPurpose($purpose);
            $payout->setPurposeType(self::$defaultPurposeTypeMap[$purpose]);

            return;
        }

        // If payout is an internally generated payout and purpose is part of internal purpose, set and return
        if (($isInternal === true) and
            (self::isInInternal($purpose) === true))
        {
            $payout->setPurpose($purpose);
            $payout->setPurposeType(self::$internalPurposeTypeMap[$purpose]);

            return;
        }

        //
        // If purpose sent is not one of the defaults defined. We hence fetch and
        // check against the custom list, if available.
        //
        $custom = $this->getCustom($payout->merchant);

        if (isset($custom[$purpose]) === true)
        {
            $payout->setPurpose($purpose);
            $payout->setPurposeType($custom[$purpose]);

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

    public function validatePurpose(Merchant\Entity $merchant, string $purpose)
    {
        if(self::isInDefaults($purpose) === true)
        {
            return;
        }

        //
        // If purpose sent is not one of the defaults defined. We hence fetch and
        // check against the custom list, if available.
        //
        $custom = $this->getCustom($merchant);

        if (isset($custom[$purpose]) === true)
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

        // array_merge cannot be used here because numeric keys in php arrays
        // can cause the function to give unexpected results.
        $all = $custom + $default;

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

        $maxPurposes = Validator::MAX_PURPOSES_ALLOWED;

        if (count($allCustomKeys) >= $maxPurposes)
        {
            throw new BadRequestValidationFailureException(
                "You have reached the maximum limit ($maxPurposes) of custom payout purposes that can be created.",
                Entity::PURPOSE_TYPE);
        }

        // If purpose is 'rzp_fees' we won't allow adding it as a custom purpose
        if (self::isInInternal($purpose) === true)
        {
            throw new BadRequestValidationFailureException(
                "Purpose '$purpose' is an internal purpose used by Razorpay and cannot be added.",
                Entity::PURPOSE);
        }

        if ((self::isInDefaults(strtolower($purpose))) or
            (array_search_ci($purpose, $allCustomKeys) !== false))
        {
            throw new BadRequestValidationFailureException(
                "Purpose '$purpose' is already defined and cannot be added.",
                Entity::PURPOSE);
        }

        $data = [
            $purpose => $type
        ];

        $this->getSettingsAccessor($merchant)
             ->upsert($data)
             ->save();
    }

    protected function getSettingsAccessor(Merchant\Entity $merchant): Settings\Accessor
    {
        return Settings\Accessor::for($merchant, Settings\Module::PAYOUT_PURPOSE, Mode::LIVE);
    }
}
