<?php

namespace RZP\Models\Merchant\Balance;

use Razorpay\Spine\DataTypes\Dictionary;

use RZP\Models\Settings;
use RZP\Models\Payout\Mode;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Merchant\Balance;
use RZP\Exception\BadRequestException;
use RZP\Models\Admin\Service as AdminService;

class FreePayout
{
    // Settings module key
    const FREE_PAYOUTS_COUNT                            = 'free_payouts_count';

    const FREE_PAYOUTS_SUPPORTED_MODES                  = 'free_payouts_supported_modes';

    // Default free shared account payouts allowed per merchant in a month.
    const DEFAULT_FREE_SHARED_ACCOUNT_PAYOUTS_COUNT     = 300;

    // Default free direct account payouts allowed per merchant in a month.
    const DEFAULT_FREE_DIRECT_ACCOUNT_PAYOUTS_COUNT_RBL = 500;

    // Default free direct account payouts allowed per merchant in a month for ICICI
    const DEFAULT_FREE_DIRECT_ACCOUNT_PAYOUTS_COUNT_ICICI = 500;

    // Count of the number of free shared account payouts allowed per merchant in a month.
    const FREE_SHARED_ACCOUNT_PAYOUTS_COUNT             = 'free_shared_account_payouts_count';

    // Count of the number of free direct account payouts allowed per merchant in a month.
    const FREE_DIRECT_ACCOUNT_PAYOUTS_COUNT             = 'free_direct_account_payouts_count';

    // Default free payouts supported modes.
    const DEFAULT_FREE_PAYOUTS_SUPPORTED_MODES          = [Mode::IMPS, Mode::NEFT, Mode::RTGS, Mode::UPI, Mode::IFT];

    public function getFreePayoutsKeyAndDefaultCount(Balance\Entity $balance)
    {
        $accountType = $balance->getAccountType();

        $configKey = null;

        $defaultCount = null;

        if ($accountType === AccountType::SHARED)
        {
            $configKey = ConfigKey::FREE_SHARED_ACCOUNT_PAYOUTS_COUNT;

            $defaultCount = self::DEFAULT_FREE_SHARED_ACCOUNT_PAYOUTS_COUNT;
        }
        else if ($accountType === AccountType::DIRECT)
        {
            $channel = $balance->getChannel();

            $channel = strtoupper(trim($channel));

            $configKey = constant(
                ConfigKey::class . '::' .
                strtoupper(self::FREE_DIRECT_ACCOUNT_PAYOUTS_COUNT) . '_' .
                $channel);

            $defaultCount = constant(
                self::class . '::DEFAULT_FREE_DIRECT_ACCOUNT_PAYOUTS_COUNT_' .
                $channel);;
        }
        else
        {
            throw new BadRequestException(
                'Invalid account type: ' . $accountType,
                Entity::ACCOUNT_TYPE,
                [
                    Entity::ID               => $balance->getPublicId(),
                    Entity::ACCOUNT_TYPE     => $accountType
                ]);
        }

        return [$configKey, $defaultCount];
    }

    /*
    Priority of fetching the count is as follows -
    1. Fetch the count from settings table
    2. If the settings table entry doesn't exist, pick the global count from redis.
    3. If that too doesn't exist, pick the default fallback count from the code.
    */
    public function getFreePayoutsCount(Balance\Entity $balance) : int
    {
        // This is the entry from the settings table.
        $freePayoutsCount = $this->getSettingsAccessor($balance)->get(self::FREE_PAYOUTS_COUNT);

        if (($freePayoutsCount instanceof Dictionary) and
            (empty($freePayoutsCount->key()) === true))
        {
            // Here we fetch the config key for the balance type to fetch from redis as well as the default fallback
            // count from the code.
            list ($configKey, $defaultCount) = $this->getFreePayoutsKeyAndDefaultCount($balance);

            // This is the redis key value.
            $globalCount = (int) (new AdminService)->getConfigKey(
                [
                    'key' => $configKey
                ]
            );

            // If redis key gave empty result, i.e., the key is unset there or call failed, we return the fallback
            // value, else we return the redis value.
            if (empty($globalCount) === true)
            {
                return $defaultCount;
            }

            return $globalCount;
        }

        return $freePayoutsCount;
    }

    public function addNewAttribute($value, Balance\Entity $balance, $key)
    {
        if (is_array($value))
        {
            $value = implode(',', $value);
        }

        $attribute = [
            $key => $value
        ];

        $this->getSettingsAccessor($balance)
             ->upsert($attribute)
             ->save();
    }

    /*
    Priority of fetching the modes is as follows -
    1. Fetch the mode list from settings table
    2. If the settings table entry doesn't exist, pick the global list of supported modes from redis.
    3. If that too doesn't exist, pick the default fallback list from the code.
    */
    public function getFreePayoutsSupportedModes(Balance\Entity $balance)
    {
        // This is the entry from the settings table.
        $freePayoutsSupportedModes = $this->getSettingsAccessor($balance)->get(self::FREE_PAYOUTS_SUPPORTED_MODES);

        if (($freePayoutsSupportedModes instanceof Dictionary) and
            (empty($freePayoutsSupportedModes->key()) === true))
        {
            // This is the redis key value.
            $freePayoutsSupportedModes = (new AdminService)->getConfigKey(
                ['key' => ConfigKey::FREE_PAYOUTS_SUPPORTED_MODES]);

            // If redis key gave empty result, i.e., the key is unset there or call failed, we return the fallback
            // value, else we return the redis value.
            if (empty($freePayoutsSupportedModes) === true)
            {
                return self::DEFAULT_FREE_PAYOUTS_SUPPORTED_MODES;
            }

            return $freePayoutsSupportedModes;
        }

        return explode(',', $freePayoutsSupportedModes);
    }

    protected function getSettingsAccessor(Balance\Entity $balance): Settings\Accessor
    {
        return Settings\Accessor::for($balance, Settings\Module::FREE_PAYOUT);
    }
}
