<?php

namespace RZP\Models\Merchant\Balance;

use App;
use Carbon\Carbon;
use Razorpay\Spine\DataTypes\Dictionary;

use RZP\Models\Merchant;
use RZP\Models\Settings;
use RZP\Trace\TraceCode;
use RZP\Models\Payout\Mode;
use RZP\Constants\Timezone;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Merchant\Balance;
use RZP\Exception\BadRequestException;
use RZP\Models\Admin\Service as AdminService;
use RZP\Models\Settings\Service as SettingsService;

class FreePayout
{
    /**
     * The application instance.
     *
     * @var Application
     */
    protected $app;

    const SLAB1 = 'slab1';

    const SLAB2 = 'slab2';

    const FREE_PAYOUT                           = 'free_payout';

    // Settings module key
    const FREE_PAYOUTS_COUNT                            = 'free_payouts_count';

    const FREE_PAYOUTS_SUPPORTED_MODES                  = 'free_payouts_supported_modes';

    // Default free shared account payouts allowed per merchant in a month.
    const DEFAULT_FREE_SHARED_ACCOUNT_PAYOUTS_COUNT_SLAB1     = 300;

    // Default free direct account payouts allowed per merchant in a month.
    const DEFAULT_FREE_DIRECT_ACCOUNT_PAYOUTS_COUNT_RBL_SLAB1   = 500;

    // Default free direct account payouts allowed per merchant in a month for ICICI
    const DEFAULT_FREE_DIRECT_ACCOUNT_PAYOUTS_COUNT_ICICI_SLAB1 = 500;

    // Setting default count to 0 for connected banking users
    const DEFAULT_FREE_DIRECT_ACCOUNT_PAYOUTS_COUNT_AXIS_SLAB1 = 0;

    // Setting default count to 0 for connected banking users
    const DEFAULT_FREE_DIRECT_ACCOUNT_PAYOUTS_COUNT_YESBANK_SLAB1 = 0;

    const DEFAULT_FREE_SHARED_ACCOUNT_PAYOUTS_COUNT_SLAB2       = 0;

    const DEFAULT_FREE_DIRECT_ACCOUNT_PAYOUTS_COUNT_RBL_SLAB2   = 250;

    const DEFAULT_FREE_DIRECT_ACCOUNT_PAYOUTS_COUNT_ICICI_SLAB2 = 250;

    // Setting default count to 0 for connected banking users
    const DEFAULT_FREE_DIRECT_ACCOUNT_PAYOUTS_COUNT_AXIS_SLAB2 = 0;

    // Setting default count to 0 for connected banking users
    const DEFAULT_FREE_DIRECT_ACCOUNT_PAYOUTS_COUNT_YESBANK_SLAB2 = 0;

    // Count of the number of free shared account payouts allowed per merchant in a month.
    const FREE_SHARED_ACCOUNT_PAYOUTS_COUNT             = 'free_shared_account_payouts_count';

    // Count of the number of free direct account payouts allowed per merchant in a month.
    const FREE_DIRECT_ACCOUNT_PAYOUTS_COUNT             = 'free_direct_account_payouts_count';

    // Default free payouts supported modes.
    const DEFAULT_FREE_PAYOUTS_SUPPORTED_MODES          = [Mode::IMPS, Mode::NEFT, Mode::RTGS, Mode::UPI, Mode::IFT];

    const FREE_PAYOUT_SLAB2_ROLLOUT_TIMESTAMP = [
        'day'   => 5,
        'month' => 9,
        'year'  => 2022
    ];

    public function __construct()
    {
        $this->app = App::getFacadeRoot();
    }

    public function getFreePayoutsKeyAndDefaultCount(Balance\Entity $balance)
    {
        $accountType = $balance->getAccountType();

        $configKey = null;

        $defaultCount = null;

        try
        {
            $slab = $this->getFreePayoutsSlab($balance);
        }
        catch (\Throwable $throwable)
        {
            $slab = self::SLAB1;
        }

        $slab = strtoupper(trim($slab));

        switch ($accountType)
        {
            case AccountType::SHARED:
                $configKey = constant(ConfigKey::class . '::' .
                    strtoupper(self::FREE_SHARED_ACCOUNT_PAYOUTS_COUNT) . '_' . $slab);

                $defaultCount = constant(self::class . '::DEFAULT_FREE_SHARED_ACCOUNT_PAYOUTS_COUNT' . '_' . $slab);

                break;

            case AccountType::DIRECT:
                $channel = $balance->getChannel();

                $channel = strtoupper(trim($channel));

                $configKey = constant(ConfigKey::class . '::' .
                    strtoupper(self::FREE_DIRECT_ACCOUNT_PAYOUTS_COUNT) . '_' . $channel . '_' . $slab);

                $defaultCount = constant(
                    self::class . '::DEFAULT_FREE_DIRECT_ACCOUNT_PAYOUTS_COUNT_' . $channel  . '_' . $slab);

                break;

            default:
                throw new BadRequestException(
                    'Invalid account type: ' . $accountType,
                    Entity::ACCOUNT_TYPE,
                    [
                        Entity::ID               => $balance->getPublicId(),
                        Entity::ACCOUNT_TYPE     => $accountType
                    ]);
        }

        $this->app['trace']->info(TraceCode::FREE_PAYOUT_DEFAULT_VALUE_AND_CONFIG_KEY,
            [
                'balance_id'       => $balance->getId(),
                'merchant_id'      => $balance->getMerchantId(),
                'free_payout_slab' => $slab,
                'default_value'    => $defaultCount,
                'redis_key'        => $configKey,
            ]);

        return [$configKey, $defaultCount];
    }

    protected function getFreePayoutsSlab(Balance\Entity $balance)
    {
        $this->app['trace']->info(TraceCode::FREE_PAYOUT_SLAB_CHECK_REQUEST,
            [
                'balance_id'  => $balance->getId(),
                'merchant_id' => $balance->getMerchantId(),
            ]);

        $slab = self::SLAB1;

        $merchantId = $balance->getMerchantId();

        $merchantCreatedAt = (new Merchant\Repository)->getCreatedAtForTheMerchant($merchantId);

        $freePayoutSlab2RolloutTimestamp = Carbon::create(
            self::FREE_PAYOUT_SLAB2_ROLLOUT_TIMESTAMP['year'],
            self::FREE_PAYOUT_SLAB2_ROLLOUT_TIMESTAMP['month'],
            self::FREE_PAYOUT_SLAB2_ROLLOUT_TIMESTAMP['day'],
            00, 0, 0, Timezone::IST)->getTimestamp();

        if (($merchantCreatedAt !== null) and
            ($merchantCreatedAt > $freePayoutSlab2RolloutTimestamp))
        {
            $slab = self::SLAB2;
        }

        $this->app['trace']->info(TraceCode::FREE_PAYOUT_SLAB_ASSIGNED,
            [
                'balance_id'       => $balance->getId(),
                'merchant_id'      => $balance->getMerchantId(),
                'free_payout_slab' => $slab
            ]);

        return $slab;
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

    public function getFreePayoutsCountRecord(Balance\Entity $balance)
    {
        return (new SettingsService())->getSettings($balance->getId(), self::FREE_PAYOUT, self::FREE_PAYOUTS_COUNT);
    }

    public function getFreePayoutsSupportedModesRecord(Balance\Entity $balance)
    {
        return (new SettingsService())->getSettings($balance->getId(), self::FREE_PAYOUT, self::FREE_PAYOUTS_SUPPORTED_MODES);
    }

}
