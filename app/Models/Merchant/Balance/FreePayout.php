<?php

namespace RZP\Models\Merchant\Balance;

use RZP\Models\Settings;
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

    // Default free shared account payouts allowed per merchant in a month.
    const DEFAULT_FREE_DIRECT_ACCOUNT_PAYOUTS_COUNT     = 500;

    // Count of the number of free shared account payouts allowed per merchant in a month.
    const FREE_SHARED_ACCOUNT_PAYOUTS_COUNT             = 'free_shared_account_payouts_count';

    // Count of the number of free direct account payouts allowed per merchant in a month.
    const FREE_DIRECT_ACCOUNT_PAYOUTS_COUNT             = 'free_direct_account_payouts_count';

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

            $trimmedUpperCaseChannel = strtoupper(trim($channel));

            $configKey = constant(
                ConfigKey::class . '::' .
                strtoupper(Self::FREE_DIRECT_ACCOUNT_PAYOUTS_COUNT_CONSTANT_KEY) . '_' .
                $trimmedUpperCaseChannel);

            $defaultCount = self::DEFAULT_FREE_DIRECT_ACCOUNT_PAYOUTS_COUNT;
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

    public function getFreePayoutsCount(Balance\Entity $balance): int
    {
        $freePayoutsCount = $this->getSettingsAccessor($balance)->get(self::FREE_PAYOUTS_COUNT);

        if ($freePayoutsCount === null)
        {
            list ($configKey, $defaultCount)  = $this->getFreePayoutsKeyAndDefaultCount($balance);

            $globalCount = (int) (new AdminService)->getConfigKey(
                [
                    'key' => $configKey
                ]
            );

            if (empty($globalCount) === null)
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

    protected function getSettingsAccessor(Balance\Entity $balance): Settings\Accessor
    {
        return Settings\Accessor::for($balance, Settings\Module::FREE_PAYOUT);
    }
}
