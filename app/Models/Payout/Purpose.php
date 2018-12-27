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
    const REFUND   = 'refund';
    const CASHBACK = 'cashback';
    const PAYOUT   = 'payout';

    protected static $default = [
        self::REFUND,
        self::CASHBACK,
        self::PAYOUT,
    ];

    protected static $defaultPurposeTypeMap = [
        self::REFUND   => FTAPurpose::REFUND,
        self::CASHBACK => FTAPurpose::SETTLEMENT,
        self::PAYOUT   => FTAPurpose::SETTLEMENT,
    ];

    public function setPurposeAndTypeForPayout(Entity $payout, string $purpose)
    {
        // If $purpose is one of the defaults, set and return
        if (isset(self::$defaultPurposeTypeMap[$purpose]) === true)
        {
            $payout->setPurpose($purpose);
            $payout->setPurposeType(self::$defaultPurposeTypeMap[$purpose]);

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

    public function getAll(Merchant\Entity $merchant): array
    {
        $default = self::$defaultPurposeTypeMap;

        $custom = $this->getSettingsAccessor($merchant)->all()->toArray();

        $all = array_merge($default, $custom);

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
        if ((isset(self::$defaultPurposeTypeMap[$purpose])) or
            ($this->getSettingsAccessor($merchant)->exists($purpose) === true))
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
