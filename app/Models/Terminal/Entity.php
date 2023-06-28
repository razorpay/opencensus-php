<?php

namespace RZP\Models\Terminal;

use App;
use Crypt;
use RZP\Http\Route;
use RZP\Models\Base;
use RZP\Base\BuilderEx;
use RZP\Models\Payment;
use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Models\Admin\Org;
use RZP\Models\Payment\Method;
use RZP\Constants\Entity as E;
use RZP\Models\Payment\Gateway;
use RZP\Models\Terminal\TpvType;
use RZP\Models\Currency\Currency;
use RZP\Constants\Mode as RzpMode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Terminal\BankingType;
use RZP\Exception\BadRequestException;
use RZP\Models\Base\QueryCache\Cacheable;
use RZP\Models\Payment\Processor\PayLater;
use RZP\Models\Payment\Processor\Netbanking;
use Illuminate\Database\Eloquent\SoftDeletes;
use RZP\Models\Emi\Subvention as EmiSubvention;
use RZP\Models\Payment\Processor\App as AppMethod;
use RZP\Models\Terminal\Status;
use RZP\Trace\TraceCode;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;
    use Cacheable;

    const ID                            = 'id';
    const MERCHANT_ID                   = 'merchant_id';
    const ORG_ID                        = 'org_id';
    const PLAN_ID                       = 'plan_id';
    const PLAN_NAME                     = 'plan_name';
    const PROCURER                      = 'procurer';
    const USED_COUNT                    = 'used_count';
    const USED                          = 'used';
    const CATEGORY                      = 'category';
    const GATEWAY                       = 'gateway';
    const IDENTIFIERS                   = 'identifiers';
    const SECRETS                       = 'secrets';
    const GATEWAY_MERCHANT_ID           = 'gateway_merchant_id';
    const GATEWAY_MERCHANT_ID2          = 'gateway_merchant_id2';
    const GATEWAY_TERMINAL_ID           = 'gateway_terminal_id';
    const GATEWAY_VPA_WHITELISTED       = 'gateway_vpa_whitelisted';
    const GATEWAY_TERMINAL_PASSWORD     = 'gateway_terminal_password';
    const GATEWAY_TERMINAL_PASSWORD2    = 'gateway_terminal_password2';
    const GATEWAY_ACCESS_CODE           = 'gateway_access_code';
    const GATEWAY_SECURE_SECRET         = 'gateway_secure_secret';
    const GATEWAY_SECURE_SECRET2        = 'gateway_secure_secret2';
    const GATEWAY_RECON_PASSWORD        = 'gateway_recon_password';
    const GATEWAY_ACQUIRER              = 'gateway_acquirer';
    const GATEWAY_CLIENT_CERTIFICATE    = 'gateway_client_certificate';

    const MC_MPAN                       = 'mc_mpan';
    const VISA_MPAN                     = 'visa_mpan';
    const RUPAY_MPAN                    = 'rupay_mpan';
    const VPA                           = 'vpa';

    const ACCOUNT_TYPE                  = 'account_type';

    const CARD                          = 'card';
    const NETBANKING                    = 'netbanking';
    const EMI                           = 'emi';
    const UPI                           = 'upi';
    const OMNICHANNEL                   = 'omnichannel';
    const BANK_TRANSFER                 = 'bank_transfer';
    const AEPS                          = 'aeps';
    const EMANDATE                      = 'emandate';
    const NACH                          = 'nach';
    const CARDLESS_EMI                  = 'cardless_emi';
    const PAYLATER                      = 'paylater';
    const EMI_DURATION                  = 'emi_duration';
    const EMI_SUBVENTION                = 'emi_subvention';
    const RECURRING                     = 'recurring';
    const CAPABILITY                    = 'capability';
    const INTERNATIONAL                 = 'international';
    const TPV                           = 'tpv';
    const CURRENCY                      = 'currency';
    const SHARED                        = 'shared';
    const ENABLED                       = 'enabled';
    const NETWORK_CATEGORY              = 'network_category';
    const TYPE                          = 'type';
    const MODE                          = 'mode';
    const DIRECT                        = 'direct';
    const STATUS                        = 'status';
    const NOTES                         = 'notes';
    const SYNC_STATUS                   = 'sync_status';
    const MPAN                          = 'mpan';
    const CRED                          = 'cred';
    const APP                           = 'app';
    const OFFLINE                       = 'offline';
    const FPX                           = 'fpx';

    // Used for allowing gateway level changes for corporate netbanking payments.
    const CORPORATE                     = 'corporate';
    const BANKING_TYPES                 = 'banking_types';
    const ENABLED_BANKS                 = 'enabled_banks';
    const ENABLED_APPS                  = 'enabled_apps';
    const ENABLED_WALLETS               = 'enabled_wallets';

    // used for direct settlements.
    const ACCOUNT_NUMBER                = 'account_number';
    const IFSC_CODE                     = 'ifsc_code';

    //used for virtual VPA
    const VIRTUAL_UPI_ROOT               = 'virtual_upi_root';
    const VIRTUAL_UPI_MERCHANT_PREFIX    = 'virtual_upi_merchant_prefix';
    const VIRTUAL_UPI_HANDLE             = 'virtual_upi_handle';

    //
    // Currenly being used to handle 'unexpected' BharatQR payments.
    //
    // BharatQR payments generally require QR code. This QR code can be created
    // via Razorpay, or by the merchant himself. For the latter case, when we are
    // notified regarding payments made to this kind of QR code, our default
    // behaviour is to treat them as unexpected, and attempt to refund them.
    //
    // This flag in terminal serves to inform us that some merchants are permitted
    // to receive such payments (made to merchant-generated QR codes), and so
    // those payments should be treated as 'expected' ones.
    //
    const EXPECTED                      = 'expected';

    const DELETED                       = 'deleted';
    const DELETED_AT                    = 'deleted_at';

    const MAX_TERMINALS_COUNT           = 200;
    const DEFAULT_CURRENCY              = 'INR';

    /**
     * Used for column name in merchant terminal pivot table
     */
    const TERMINAL_ID                   = 'terminal_id';

    const SUB_MERCHANTS                 = 'sub_merchants';

    //const PRIORITY                      = 'priority';

    // additional attributes
    const ACTION                        = 'action';

    const TERMINAL_IDS                  = 'terminal_ids';

    const BANK                          = 'bank';

    const CATEGORY_LENGTH               = 4;

    const UPI_FEATURES_TYPE             = 'type';

    protected static $sign              = 'term';

    protected $fillable = [
        self::ORG_ID,
        self::GATEWAY,
        self::PROCURER,
        self::CARD,
        self::CATEGORY,
        self::NETWORK_CATEGORY,
        self::NETBANKING,
        self::UPI,
        self::OMNICHANNEL,
        self::BANK_TRANSFER,
        self::AEPS,
        self::EMANDATE,
        self::NACH,
        self::EMI,
        self::EMI_DURATION,
        self::EMI_SUBVENTION,
        self::INTERNATIONAL,
        self::TPV,
        self::TYPE,
        self::CAPABILITY,
        self::MODE,
        self::STATUS,
        self::NOTES,
        self::CORPORATE,
        self::EXPECTED,
        self::CURRENCY,
        self::GATEWAY_MERCHANT_ID,
        self::GATEWAY_MERCHANT_ID2,
        self::GATEWAY_TERMINAL_ID,
        self::GATEWAY_VPA_WHITELISTED,
        self::GATEWAY_ACCESS_CODE,
        self::GATEWAY_SECURE_SECRET,
        self::GATEWAY_SECURE_SECRET2,
        self::GATEWAY_TERMINAL_PASSWORD,
        self::GATEWAY_TERMINAL_PASSWORD2,
        self::GATEWAY_RECON_PASSWORD,
        self::GATEWAY_ACQUIRER,
        self::GATEWAY_CLIENT_CERTIFICATE,
        self::MC_MPAN,
        self::VISA_MPAN,
        self::RUPAY_MPAN,
        self::VPA,
        self::ENABLED,
        self::ENABLED_BANKS,
        self::ENABLED_APPS,
        self::ENABLED_WALLETS,
        self::ACCOUNT_NUMBER,
        self::IFSC_CODE,
        self::CARDLESS_EMI,
        self::PAYLATER,
        self::VIRTUAL_UPI_ROOT,
        self::VIRTUAL_UPI_MERCHANT_PREFIX,
        self::VIRTUAL_UPI_HANDLE,
        self::SYNC_STATUS,
        self::ACCOUNT_TYPE,
        self::CRED,
        self::PLAN_ID,
        self::APP,
        self::OFFLINE,
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::STATUS,
        self::ENABLED,
        self::MPAN,
        self::NOTES,
        self::CREATED_AT
    ];

    protected $visible = [
        self::ID,
        self::ENTITY,
        self::MERCHANT_ID,
        self::ORG_ID,
        self::PLAN_ID,
        self::PROCURER,
        self::GATEWAY,
        self::CARD,
        self::CATEGORY,
        self::CURRENCY,
        self::NETWORK_CATEGORY,
        self::NETBANKING,
        self::UPI,
        self::OMNICHANNEL,
        self::BANK_TRANSFER,
        self::AEPS,
        self::EMANDATE,
        self::NACH,
        self::EMI,
        self::EMI_DURATION,
        self::EMI_SUBVENTION,
        self::INTERNATIONAL,
        self::SHARED,
        self::TPV,
        self::GATEWAY_MERCHANT_ID,
        self::GATEWAY_MERCHANT_ID2,
        self::GATEWAY_TERMINAL_ID,
        self::GATEWAY_VPA_WHITELISTED,
        self::GATEWAY_ACQUIRER,
        self::GATEWAY_ACCESS_CODE,
        self::MC_MPAN,
        self::VISA_MPAN,
        self::RUPAY_MPAN,
        self::VPA,
        self::USED_COUNT,
        self::TYPE,
        self::MODE,
        self::STATUS,
        self::NOTES,
        self::SYNC_STATUS,
        self::CORPORATE,
        self::CAPABILITY,
        self::EXPECTED,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
        self::ENABLED,
        self::SUB_MERCHANTS,
        self::ENABLED_BANKS,
        self::ENABLED_APPS,
        self::ENABLED_WALLETS,
        self::ACCOUNT_NUMBER,
        self::IFSC_CODE,
        self::VIRTUAL_UPI_ROOT,
        self::VIRTUAL_UPI_MERCHANT_PREFIX,
        self::VIRTUAL_UPI_HANDLE,
        self::CARDLESS_EMI,
        self::CRED,
        self::APP,
        self::PAYLATER,
        self::MPAN,
        self::ACCOUNT_TYPE,
        self::CREATED_AT,
        self::OFFLINE,
        self::FPX,
    ];

    protected $hidden = [
        self::GATEWAY_TERMINAL_PASSWORD,
        self::GATEWAY_TERMINAL_PASSWORD2,
        self::GATEWAY_SECURE_SECRET,
        self::GATEWAY_SECURE_SECRET2,
        self::GATEWAY_RECON_PASSWORD,
        self::GATEWAY_CLIENT_CERTIFICATE,
    ];

    protected $generateIdOnCreate = true;

    protected $entity = 'terminal';

    protected static $generators = [
        'method',
        self::ENABLED_BANKS,
        self::ENABLED_APPS,
        self::ENABLED_WALLETS,
    ];

    protected static $modifiers = [
        'inputRemoveBlanks',
        self::INTERNATIONAL,
        self::EMI_SUBVENTION,
        self::TYPE,
        self::GATEWAY,
        self::MODE,
        self::ORG_ID,
    ];

    protected $defaults = [
        self::PROCURER                   => 'razorpay',
        self::CATEGORY                   => null,
        self::NETWORK_CATEGORY           => null,
        self::GATEWAY_MERCHANT_ID        => null,
        self::GATEWAY_TERMINAL_ID        => null,
        self::GATEWAY_TERMINAL_PASSWORD  => null,
        self::GATEWAY_TERMINAL_PASSWORD2 => null,
        self::GATEWAY_ACCESS_CODE        => null,
        self::GATEWAY_SECURE_SECRET      => null,
        self::GATEWAY_SECURE_SECRET2     => null,
        self::GATEWAY_RECON_PASSWORD     => null,
        self::EMI                        => false,
        self::TPV                        => 0,
        self::BANK_TRANSFER              => 0,
        self::TYPE                       => [
            Type::NON_RECURRING => '1'
        ],
        self::CAPABILITY                 => Capability::ALL,
        self::MODE                       => Mode::DUAL,
        self::CORPORATE                  => 0,
        self::EXPECTED                   => 0,
        self::CURRENCY                   => self::DEFAULT_CURRENCY,
        self::EMI_DURATION               => null,
        self::GATEWAY_ACQUIRER           => null,
        self::INTERNATIONAL              => 0,
        self::ENABLED                    => true,
        self::USED                       => false,
        self::EMI_SUBVENTION             => null,
        self::CARDLESS_EMI               => 0,
        self::CRED                       => 0,
        self::APP                        => 0,
        self::PAYLATER                   => 0,
        self::STATUS                     => Status::ACTIVATED,
        self::NOTES                      => null,
        self::OMNICHANNEL                => 0,
        self::VPA                        => null,
        self::MC_MPAN                    => null,
        self::VISA_MPAN                  => null,
        self::RUPAY_MPAN                 => null,
        self::SYNC_STATUS                => SyncStatus::NOT_SYNCED,
        self::ACCOUNT_TYPE               => null,
        self::PLAN_ID                    => null,
        self::OFFLINE                    => 0,
    ];

    protected $casts = [
        self::CARD                      => 'boolean',
        self::EMI                       => 'boolean',
        self::NETBANKING                => 'boolean',
        self::INTERNATIONAL             => 'boolean',
        self::UPI                       => 'boolean',
        self::OMNICHANNEL               => 'boolean',
        self::BANK_TRANSFER             => 'boolean',
        self::AEPS                      => 'boolean',
        self::EMANDATE                  => 'boolean',
        self::NACH                      => 'boolean',
        self::ENABLED                   => 'boolean',
        self::TPV                       => 'int',
        self::TYPE                      => 'int',
        self::MODE                      => 'int',
        self::CATEGORY                  => 'string',
        self::CORPORATE                 => 'int',
        self::EXPECTED                  => 'boolean',
        self::USED                      => 'boolean',
        self::ENABLED_BANKS             => 'array',
        self::ENABLED_APPS              => 'array',
        self::ENABLED_WALLETS           => 'array',
        self::CARDLESS_EMI              => 'boolean',
        self::CRED                      => 'boolean',
        self::APP                       => 'boolean',
        self::PAYLATER                  => 'boolean',
        self::DIRECT                    => 'boolean',
        self::OFFLINE                   => 'boolean',
    ];

    const featureToGatewayMap = [
        'axis_org' => 'paysecure',
    ];

    const gatewaysOnlyOnTs = [
        Gateway::WALLET_PAYPAL,
    ];

    protected $appends = [
        self::SHARED,
        self::BANKING_TYPES,
        self::FPX,
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::MPAN,
    ];


    /**
     * {@inheritDoc}
     */
    protected $dispatchesEvents = [
        // Event 'retrieved' fires on fetch from terminals table.
        'retrieved'   => EventRetrieved::class,
        // Event 'saved' fires on insert or update from terminals table.
        'saved'       => EventSaved::class,
    ];

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($terminal)
        {
            $terminal->merchants()->detach();
        });
    }

    // ---------------------- GETTERS ----------------------

    public function getOrgId() : string
    {
        return $this->getAttribute(self::ORG_ID);
    }

    public function getPlanId()
    {
        return $this->getAttribute(self::PLAN_ID);
    }

    public function getGatewayMerchantId()
    {
        return $this->getAttribute(self::GATEWAY_MERCHANT_ID);
    }

    public function getGatewayAccessCode()
    {
        return $this->getAttribute(self::GATEWAY_ACCESS_CODE);
    }

    public function getGatewayMerchantId2()
    {
        return $this->getAttribute(self::GATEWAY_MERCHANT_ID2);
    }

    public function getVpaWhitelisted()
    {
        return $this->getAttribute(self::GATEWAY_VPA_WHITELISTED);
    }

    public function getGatewayReconPassword()
    {
        if(!array_key_exists(self::GATEWAY_RECON_PASSWORD, $this->attributes))
            return null;

        $reconPassword = $this->attributes[self::GATEWAY_RECON_PASSWORD];

        if ($reconPassword === null)
        {
            return $reconPassword;
        }

        return Crypt::decrypt($reconPassword, true, $this);
    }

    public function getGatewayTerminalId()
    {
        return $this->getAttribute(self::GATEWAY_TERMINAL_ID);
    }

    public function getGateway()
    {
        return $this->getAttribute(self::GATEWAY);
    }

    public function getProcurer()
    {
        return $this->getAttribute(self::PROCURER);
    }

    public function getGatewayAcquirer()
    {
        return $this->getAttribute(self::GATEWAY_ACQUIRER);
    }

    public function getUsedCount()
    {
        return $this->getAttribute(self::USED_COUNT);
    }

    public function isUsed()
    {
        return $this->getAttribute(self::USED);
    }

    public function getMerchantId(): string
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }

    public function getCategory()
    {
        return $this->getAttribute(self::CATEGORY);
    }

    public function getCapability()
    {
        return $this->getAttribute(self::CAPABILITY);
    }

    public function getType()
    {
        return $this->getAttribute(self::TYPE);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getNotes()
    {
        return $this->getAttribute(self::NOTES);
    }

    public function getSyncStatus()
    {
        return $this->getAttribute(self::SYNC_STATUS);
    }

    public function getEmiDuration()
    {
        return $this->getAttribute(self::EMI_DURATION);
    }

    public function getEmiSubvention()
    {
        return $this->getAttribute(self::EMI_SUBVENTION);
    }

    public function getAccountType()
    {
        return $this->getAttribute(self::ACCOUNT_TYPE);
    }

    /**
     * Currency Accessor
     * @param $value
     */
    protected function getCurrencyAttribute($currency)
    {
        if (empty($currency) === true)
        {
            return [];
        }

        if (is_array($currencies = json_decode($currency)) === true)
        {
            $currency = $currencies;
        }

        return ((array) $currency);
    }

    protected function getFpxAttribute()
    {
        if ($this->getGateway() == "fpx")
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     * Currency Mutator
     * @param $value
     */
    protected function setCurrencyAttribute($currency)
    {
        if (empty($currency) === false)
        {
            $currency = (array) $currency;

            $currency = json_encode($currency);

            $this->attributes[self::CURRENCY] = $currency;
        }
    }



    public function getCurrency()
    {
        return $this->getAttribute(self::CURRENCY);
    }

    public function supportsCurrency($currency): bool
    {
        return in_array($currency, $this->getCurrency(), true);
    }

    public function getNetworkCategory()
    {
        return $this->getAttribute(self::NETWORK_CATEGORY);
    }

    public function getMode()
    {
        return $this->getAttribute(self::MODE);
    }

    public function getBankingTypes()
    {
        return $this->getAttribute(self::BANKING_TYPES);
    }

    public function getCorporate()
    {
        return $this->getAttribute(self::CORPORATE);
    }

    public function getTpv()
    {
        return $this->getAttribute(self::TPV);
    }

    public function getEnabledBanks()
    {
        return $this->getAttribute(self::ENABLED_BANKS);
    }

    public function getEnabledApps()
    {
        return $this->getAttribute(self::ENABLED_APPS);
    }

    public function getEnabledWallets()
    {
        return $this->getAttribute(self::ENABLED_WALLETS);
    }

    // ---------------------- END GETTERS ----------------------

    public function isEnabled()
    {
        return $this->getAttribute(self::ENABLED);
    }

    public function isCardEnabled()
    {
        return $this->getAttribute(self::CARD);
    }

    public function isNetbankingEnabled()
    {
        return $this->getAttribute(self::NETBANKING);
    }

    public function isEmiEnabled()
    {
        return (bool) $this->getAttribute(self::EMI);
    }

    public function isUpiEnabled()
    {
        return $this->getAttribute(self::UPI);
    }

    public function isFpxEnabled()
    {
        return $this->getAttribute(self::FPX);
    }

    public function isTokenizationSupported()
    {
        if (in_array($this->getAttribute(self::GATEWAY), Gateway::$tokenizationGateways) === true)
        {
            return true;
        }

        return false;
    }

    public function isOmnichannelEnabled()
    {
        return $this->getAttribute(self::OMNICHANNEL);
    }

    public function isBankTransferEnabled()
    {
        return $this->getAttribute(self::BANK_TRANSFER);
    }

    public function isAepsEnabled()
    {
        return $this->getAttribute(self::AEPS);
    }

    public function isEmandateEnabled()
    {
        return $this->getAttribute(self::EMANDATE);
    }

    public function isNachEnabled()
    {
        return $this->getAttribute(self::NACH);
    }

    public function isCardlessEmiEnabled()
    {
        return $this->getAttribute(self::CARDLESS_EMI);
    }

    public function isCredEnabled()
    {
        return $this->getAttribute(self::CRED);
    }

    public function isOfflineEnabled()
    {
        return $this->getAttribute(self::OFFLINE);
    }

    public function isAppEnabled()
    {
        return $this->getAttribute(self::APP);
    }

    public function isPayLaterEnabled()
    {
        return $this->getAttribute(self::PAYLATER);
    }

    public function isShared(): bool
    {
        $merchantId = $this->getAttribute(self::MERCHANT_ID);

        return ($merchantId === Merchant\Account::SHARED_ACCOUNT);
    }

    /**
     * NOTE: This function must be used with caution.
     * Any function using this must also use the standard
     * repo function `addMerchantWhereCondition` and should
     * also SET the terminal entity's `direct` attribute.
     *
     * @return bool
     */
    public function isDirectForMerchant(): bool
    {
        return ($this->getAttribute(self::DIRECT) === true);
    }

    /**
     * Fallback is applicable only if the terminal is assigned
     * directly to the merchant (or via sub merchant).
     * Hitachi is an exception where we are okay with
     * shared terminals also being used for fallback.
     *
     * @param Merchant\Entity $merchant
     *
     * @return bool
     */
    public function isFallbackApplicable(Merchant\Entity $merchant): bool
    {
        if ($this->getGateway() === Payment\Gateway::HITACHI)
        {
            return true;
        }

        return $this->isDirectForMerchant();
    }

    /**
     * Values for CORPORATE can be 0, 1, 2
     * 0: Retail only
     * 1: Corporate only
     * 2: Both
     *
     * @return bool
     */
    public function isBankingTypeBoth()
    {
        return ($this->getAttribute(self::CORPORATE) === BankingType::BOTH);
    }

    public function isExpected()
    {
        return $this->getAttribute(self::EXPECTED);
    }

    // ---------------------- SETTERS ----------------------

    public function setNetworkCategory($category)
    {
        $this->setAttribute(self::NETWORK_CATEGORY, $category);
    }

    public function setEmiDuration($duration)
    {
        $this->setAttribute(self::EMI_DURATION, $duration);
    }

    public function setEnabled($status)
    {
        $this->setAttribute(self::ENABLED, $status);
    }

    public function setCreatedAt($timestamp)
    {
        $this->setAttribute(self::CREATED_AT, $timestamp);

    }

    public function setUpdatedAt($timestamp)
    {
        $this->setAttribute(self::UPDATED_AT, $timestamp);
    }

    public function setDeletedAt($timestamp)
    {
        $this->setAttribute(self::DELETED_AT, $timestamp);
    }

    public function syncEntity()
    {
        $this->syncOriginal();

        $this->exists = true;
    }

    public function setMerchantId($merchantId)
    {
        $this->setAttribute(self::MERCHANT_ID, $merchantId);
    }

    public function setMode($mode)
    {
        $this->setAttribute(self::MODE, $mode);
    }

    public function setEnabledBanks(array $banksToEnable)
    {
        $this->setAttribute(self::ENABLED_BANKS, $banksToEnable);
    }

    public function setEnabledApps(array $appsToEnable)
    {
        $this->setAttribute(self::ENABLED_APPS, $appsToEnable);
    }

    public function setEnabledWallets(array $walletsToEnable)
    {
        $this->setAttribute(self::ENABLED_WALLETS, $walletsToEnable);
    }

    public function setCapability($capability)
    {
        $this->setAttribute(self::CAPABILITY, $capability);
    }

    public function setStatus(string $status)
    {
        $this->setAttribute(self::STATUS, $status);
    }

    public function setSyncStatus(string $status)
    {
        $this->setSyncStatusAttribute($status);
    }

    // ---------------------- END SETTERS ----------------------

    // -----------------------PUBLIC SETTERS -------------------

    protected function setPublicSubMerchantsAttribute(array & $array)
    {
        $subMerchants = $this->merchants()->get();

        $subMerchants->transform(
            function ($item, $key)
            {
                return [
                    Merchant\Entity::ID            => $item[Merchant\Entity::ID],
                    Merchant\Entity::NAME          => $item[Merchant\Entity::NAME],
                    Merchant\Entity::WEBSITE       => $item[Merchant\Entity::WEBSITE],
                    Merchant\Entity::BILLING_LABEL => $item[Merchant\Entity::BILLING_LABEL]
                ];
            });

        $array[self::SUB_MERCHANTS] = $subMerchants;
    }

    protected function setPublicMpanAttribute(array & $array)
    {
        $app = App::getFacadeRoot();

        // mpans are sensitive and thus stored in db in tokenized form. We don't want detokenized(original) mpans to be shown in all routes (not even in admin routes),
        // but aggregator partner need to see original mpans, so we are detokenizing mpans for these particular routes
        $shouldTokenize = false;

        $routeName = $app['api.route']->getCurrentRouteName();

        if (in_array($routeName, Route::$detokenizeMpansRoutes, true) === true)
        {
            $variant = app('razorx')->getTreatment(UniqueIdEntity::generateUniqueId(), Merchant\RazorxTreatment::DETOKENIZE_MPANS,
                $this->mode ?? "live");

            // If experiment enabled then don't de-tokenize
            if(strtolower($variant) != 'on')
            {
                $shouldTokenize = true;
            }
        }

        $cardVaultApp = $app['mpan.cardVault'];

        $mcMpan =  $this->getMCMpan();
        $rupayMpan =  $this->getRupayMpan();
        $visaMpan =  $this->getVisaMpan();

        if ($shouldTokenize and (empty($mcMpan) === false) and (strlen($mcMpan) !== 16))
        {
            $mcMpan = $cardVaultApp->detokenize($mcMpan);
        }

        if ($shouldTokenize and (empty($rupayMpan) === false) and (strlen($rupayMpan) !== 16))
        {
            $rupayMpan = $cardVaultApp->detokenize($rupayMpan);
        }

        if ($shouldTokenize and (empty($visaMpan) === false) and (strlen($visaMpan) !== 16))
        {
            $visaMpan = $cardVaultApp->detokenize($visaMpan);
        }

        $array[self::MPAN] = [
            self::MC_MPAN    => $mcMpan,
            self::RUPAY_MPAN => $rupayMpan,
            self::VISA_MPAN  => $visaMpan,
        ];

        return $array;
    }

    //----------------------END PUBLIC SETTERS----------------

    // ---------------------- ACCESSORS ----------------------

    protected function getGatewayTerminalPasswordAttribute()
    {
        $pwd = $this->attributes[self::GATEWAY_TERMINAL_PASSWORD];

        if ($pwd === null)
            return $pwd;

        return Crypt::decrypt($pwd, true, $this);
    }

    protected function getGatewayTerminalPassword2Attribute()
    {
        $pwd = $this->attributes[self::GATEWAY_TERMINAL_PASSWORD2];

        if ($pwd === null)
            return $pwd;

        return Crypt::decrypt($pwd, true, $this);
    }

    protected function getGatewaySecureSecretAttribute()
    {
        $secret = $this->attributes[self::GATEWAY_SECURE_SECRET];

        if ($secret === null)
        {
            return $secret;
        }

        return Crypt::decrypt($secret, true, $this);
    }

    protected function getGatewaySecureSecret2Attribute()
    {
        $secret = $this->attributes[self::GATEWAY_SECURE_SECRET2];

        if ($secret === null)
        {
            return $secret;
        }

        return Crypt::decrypt($secret, true, $this);
    }

    protected function getUsedCountAttribute()
    {
        return (int) $this->attributes[self::USED_COUNT];
    }

    protected function getCategoryAttribute()
    {
        return $this->attributes[self::CATEGORY];
    }

    protected function getEmiDurationAttribute()
    {
        $emiDuration = $this->attributes[self::EMI_DURATION] ?? null;

        if ($emiDuration !== null)
        {
            $emiDuration = (int) $emiDuration;
        }

        return $emiDuration;
    }

    protected function getSharedAttribute()
    {
        return $this->isShared();
    }

    protected function getBankingTypesAttribute()
    {
        $corporate = $this->getAttribute(self::CORPORATE);

        if ($corporate === null)
        {
            return;
        }

        return BankingType::getBankingTypes($corporate);
    }

    protected function getSyncStatusAttribute()
    {
        $attribute = $this->attributes[self::SYNC_STATUS];

        return SyncStatus::getSyncStatusStringForValue($attribute);
    }

    // ---------------------- END ACCESSORS ----------------------

    // ---------------------- MODIFIERS ----------------------

    protected function setGatewayTerminalPasswordAttribute($password)
    {
        if ((empty($password) === true) or (trim($password) === ''))
        {
            $this->attributes[self::GATEWAY_TERMINAL_PASSWORD] = null;

            return;
        }

        $this->attributes[self::GATEWAY_TERMINAL_PASSWORD] = Crypt::encrypt($password, true, $this);
    }

    protected function setGatewayTerminalPassword2Attribute($password)
    {
        if ((empty($password) === true) or (trim($password) === ''))
        {
            $this->attributes[self::GATEWAY_TERMINAL_PASSWORD2] = null;

            return;
        }

        $this->attributes[self::GATEWAY_TERMINAL_PASSWORD2] = Crypt::encrypt($password, true, $this);
    }

    protected function setGatewaySecureSecretAttribute($secret)
    {
        if ((empty($secret) === true) or (trim($secret) === ''))
        {
            $this->attributes[self::GATEWAY_SECURE_SECRET] = null;

            return;
        }

        $this->attributes[self::GATEWAY_SECURE_SECRET] = Crypt::encrypt($secret, true, $this);
    }

    protected function setGatewaySecureSecret2Attribute($secret)
    {
        if ((empty($secret) === true) or (trim($secret) === ''))
        {
            $this->attributes[self::GATEWAY_SECURE_SECRET2] = null;

            return;
        }

        $this->attributes[self::GATEWAY_SECURE_SECRET2] = Crypt::encrypt($secret, true, $this);
    }

    protected function setGatewayReconPasswordAttribute($reconPassword)
    {
        if ((empty($reconPassword) === true) or (trim($reconPassword) === ''))
        {
            $this->attributes[self::GATEWAY_RECON_PASSWORD]  = null;

            return;
        }

        $this->attributes[self::GATEWAY_RECON_PASSWORD] = Crypt::encrypt($reconPassword, true, $this);
    }

    protected function setEnabledAttribute($status)
    {
        $this->attributes[self::ENABLED] = $status;
    }

    public function setDirectForMerchant($isDirect)
    {
        $this->attributes[self::DIRECT] = $isDirect;
    }

    protected function setTypeAttribute($type)
    {
        $hex = 0;

        if (isset($this->attributes[self::TYPE]) === true)
        {
            $hex = $this->attributes[self::TYPE];
        }

        $this->attributes[self::TYPE] = Type::getHexValue($type, $hex);
    }

    public function setType($type)
    {
        $this->attributes[self::TYPE] = Type::getHexValue($type, 0);
    }

    protected function setSyncStatusAttribute(string $syncStatusString)
    {
        $this->attributes[self::SYNC_STATUS] = SyncStatus::getValueForSyncStatusString($syncStatusString);
    }

    protected function getTypeAttribute()
    {
        $type = $this->attributes[self::TYPE];

        return Type::getEnabledTypes($type);
    }

    public function getMCMpan()
    {
        return $this->getAttribute(self::MC_MPAN);
    }

    public function getVisaMpan()
    {
        return $this->getAttribute(self::VISA_MPAN);
    }

    public function getRupayMpan()
    {
        return $this->getAttribute(self::RUPAY_MPAN);
    }

    public function getAccountNumber()
    {
        return $this->getAttribute(self::ACCOUNT_NUMBER);
    }

    public function getIfscCode()
    {
        return $this->getAttribute(self::IFSC_CODE);
    }

    public function getVpa()
    {
        return $this->getAttribute(self::VPA);
    }

    public function getVirtualUpiRoot()
    {
        return $this->getAttribute(self::VIRTUAL_UPI_ROOT);
    }

    public function getVirtualUpiMerchantPrefix()
    {
        return $this->getAttribute(self::VIRTUAL_UPI_MERCHANT_PREFIX);
    }

    public function getVirtualUpiHandle()
    {
        return $this->getAttribute(self::VIRTUAL_UPI_HANDLE);
    }

    // returns vpa for terminal by first checking vpa attribute and if not present then returns gatewayMerchantId2 value
    // for some gateways like upi_mindgate vpa is stored in gatewayMerchantId2, and not in vpa
    public function getVpaForTerminal()
    {
        if (($this->isUpiEnabled() === false) and ($this->isOmnichannelEnabled() === false))
        {
            return null;
        }

        return $this->getVpa() ?: $this->getGatewayMerchantId2();
    }

    protected function modifyInternational(& $input)
    {
        if (isset($input[self::INTERNATIONAL]) === false)
        {
            $gateway = $input[self::GATEWAY];

            if ((in_array($gateway, Payment\Gateway::$internationalCardGateways, true) === true) or
                (in_array($gateway, Payment\Gateway::$internationalGateways, true) === true))
            {
                $input[self::INTERNATIONAL] = 1;
            }
        }
    }

    protected function modifyType(& $input)
    {
        if ((empty($input[self::GATEWAY]) === false) and
            ($input[self::GATEWAY] === Payment\Gateway::PAYTM))
        {
            $input[self::TYPE][Type::DIRECT_SETTLEMENT_WITH_REFUND] = '1';
        }
    }

    protected function modifyMode(& $input)
    {
        if ((empty($input[self::GATEWAY]) === false) and
            ($input[self::GATEWAY] === Payment\Gateway::PAYTM))
        {
            $input[self::MODE] = Mode::PURCHASE;
        }
    }

    protected function modifyEmiSubvention(& $input)
    {
        $isEmi = $input[self::EMI] ?? false;

        if ($isEmi == true)
        {
            $input[self::EMI_SUBVENTION] = $input[self::EMI_SUBVENTION] ?? EmiSubvention::CUSTOMER;
        }
    }

    protected function modifyGateway(& $input)
    {
        $input[self::GATEWAY] = strtolower($input[self::GATEWAY]);
    }

    protected function modifyOrgId(& $input)
    {
        $app = App::getFacadeRoot();

        if ( isset($input[self::ORG_ID]) === true )
        {
            // remove the 'org_' from org_id in input if present,
            // since resulting terminal's org_id should not have 'org_'
            // if 'org_' not present in the org_id in input, input will not be changed, but,
            // we need to append it to the local variable orgId which we are using to fetch the org here

            $input[self::ORG_ID] = Org\Entity::verifyIdAndSilentlyStripSign($input[self::ORG_ID]);

            $orgId = 'org_' . $input[self::ORG_ID];  // resulting orgId will be org_{id}

            try
            {
                $org = (new Org\Repository)->findOrFailPublic($input[self::ORG_ID]);

                $this->org()->associate($org);  // if org is set in input, associate the terminal with this org
            }
            catch (\Exception $e)
            {
                $app['trace']->info(TraceCode::TERMINAL_ORG_HEADERS_EXCEPTION, [
                    'stripped org id' => $input[self::ORG_ID],
                ]);

                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_INVALID_ORG_ID,
                    null,
                    [
                        'org_id' => $orgId,
                    ]
                );
            }
        }
    }
    // ---------------------- END MODIFIERS ----------------------

    // ---------------------- SCOPES ----------------------

    public function scopeEnabled($query)
    {
        return $query->where(Entity::ENABLED, '=', '1');
    }

    public function scopeShared($query)
    {
        return $query->where(Entity::MERCHANT_ID, '=', Merchant\Account::SHARED_ACCOUNT);
    }

    public function build(array $input = array())
    {
        $terminal = parent::build($input);

        // This is done here because we do this similarly after build is done in edit flow.
        $terminal->getValidator()->validateType();

        return $terminal;
    }

    /**
     * This is called from parent class Entity's build()
     * This is needed so that org_id and merchant_id gets set first as we need to do  $entity->getMerchantId() and $entity->getOrgId() for getting org key while setting and encrypting sensitive fields.
     * If this is not done, we will get error when doing $entity->getMerchantId() and $entity->getOrgId() in Encryption/Facade.php
     * Relevant test = testEncryptionDecryptionAxisOrg, testTerminalEncryptionAxisOrg
     */
    public function generate($input)
    {
        $this->setForemostAttributes($input);

        parent::generate($input);
    }

    public function buildFromTerminalServiceResponse(array $input = array())
    {
        $this->setForemostAttributes($input);

        $this->modify($input);

        $this->fill($input);

        $this->generateDefaultAttributes($input);

        return $this;
    }

    public function buildFromAttributes(array $input = array())
    {
        foreach ($this->getVisible() as $key)
        {
            if ((array_key_exists($key, $input) === true) and (empty($input[$key]) !== true) and ($key !== self::TYPE))
            {
                $this->attributes[$key] = $input[$key];
            }
        }
        return $this;
    }

    public function exportAttributes(): array
    {
        $attributes =[];
        foreach ($this->getVisible() as $key)
        {
            if ((empty($this->attributes[$key]) !== true) and ($key !== 'type'))
            {
                $attributes[$key] = $this->attributes[$key];
            }
        }
        return $attributes;
    }

    // See test testGetEntityFromTerminalServiceResponseShouldUseOrgKeyForEncryption
    public function setForemostAttributes(array $input = array())
    {
        if (isset($input[Entity::MERCHANT_ID]) === true)
        {
            $this->setAttribute(Entity::MERCHANT_ID, $input[Entity::MERCHANT_ID]);
        }

        if (isset($input[Entity::ORG_ID]) === true)
        {
            $this->setAttribute(Entity::ORG_ID, $input[Entity::ORG_ID]);
        }
    }

    /**
     * Used to query by type, which is a bitwise column.
     *
     * The objective is to check if a specific bit is set. We find the bit in position,
     * create a comparator that has only that bit set and nothing else, and perform a
     * logical AND with type. If the result is the same comparator, then the bit is set.
     * If not set, the result would have given 0.
     *
     * Example: A terminal that support recurring, both 3DS and N3DS, has type set
     * to 0110, i.e. 6. To check if it support N3DS, we find bit position of N3DS (3),
     * shift 1 so that it gives a comparator with only the 3rd bit set (0100),
     * and AND it with type. The result is 0100.
     *
     * @param BuilderEx $query
     * @param array      $types
     *
     * @return BuilderEx
     */
    public function scopeType($query, array $types)
    {
        $bitComparator = 0;

        foreach ($types as $type)
        {
            if (in_array($type, Type::getValidTypes(), true) === false)
            {
                return $query;
            }

            $position = Type::getBitPosition($type);

            $bitComparator |= (1 << ($position - 1));
        }

        $typeColumn = $this->dbColumn(Entity::TYPE);

        return $query->whereRaw($typeColumn . ' & ' . $bitComparator . ' = ' . $bitComparator);
    }

    // ---------------------- END SCOPES ----------------------

    /**
     * This function won't work in cases where a single gateway
     * supports multiple methods. Example: Netbanking HDFC,
     * Netbanking ICICI. They both support emandate and netbanking.
     *
     * It'll end up setting both netbanking and emandate as 1.
     *
     * @param $input
     */
    public function generateMethod($input)
    {
        $gateway = $input[self::GATEWAY];
        $methods = [
            self::CARD,
            self::NETBANKING,
            self::UPI,
            self::AEPS,
        ];

        foreach ($methods as $method)
        {
            if (Payment\Gateway::isMethodSupported($method, $gateway))
            {
                $this->setAttribute($method, 1);
            }
            else
            {
                $this->setAttribute($method, 0);
            }
        }
    }

    protected function generateEnabledBanks(array $input)
    {
        $netbanking  = intval($input[self::NETBANKING] ?? 0);
        $paylater    = intval($input[self::PAYLATER] ?? 0);
        $cardlessEmi = intval($input[self::CARDLESS_EMI] ?? 0);

        $gatewayAquirer = $input[self::GATEWAY_ACQUIRER] ?? '';

        $gateway = $input[self::GATEWAY];

        if ((($netbanking !== 1) and ($paylater !== 1) and ($cardlessEmi !== 1)) or ((in_array($gateway, Gateway::$methodMap[Method::NETBANKING], true) === false) and
                (PayLater::isMultilenderProvider($gatewayAquirer) === false) and (Payment\Processor\CardlessEmi::isMultilenderProvider($gatewayAquirer)) === false))
        {
            return;
        }

        if ($netbanking === 1)
        {
            $corporate = $input[self::CORPORATE] ?? BankingType::RETAIL_ONLY;

            $tpv = $input[self::TPV] ?? TpvType::NON_TPV_ONLY;

            $supportedBanks = Netbanking::getSupportedBanksForGateway($gateway, $corporate, $tpv);

            $disabledBanks = Netbanking::getDefaultDisabledBanksForGateway($gateway, $corporate, $tpv);

            $enabledBanks = array_diff($supportedBanks, $disabledBanks);

            $enabledBanks = array_values($enabledBanks);
        }
        elseif ($paylater === 1)
        {
            $supportedBanks = Payment\Processor\Paylater::getSupportedBanksForMultilenderProvider($gatewayAquirer);

            $disabledBanks = Payment\Processor\Paylater::getDefaultDisabledBanksForMultilenderProvider($gatewayAquirer);

            $enabledBanks = array_diff($supportedBanks, $disabledBanks);

            $enabledBanks = array_values($enabledBanks);
        }
        elseif ($cardlessEmi === 1)
        {
            $supportedBanks = Payment\Processor\CardlessEmi::getSupportedBanksForMultilenderProvider($gatewayAquirer);

            $disabledBanks = Payment\Processor\CardlessEmi::getDefaultDisabledBanksForMultilenderProvider($gatewayAquirer);

            $enabledBanks = array_diff($supportedBanks, $disabledBanks);

            $enabledBanks = array_values($enabledBanks);
        }

        $this->setAttribute(self::ENABLED_BANKS, $enabledBanks);
    }

    protected function generateEnabledApps(array $input)
    {
        $app = intval($input[self::APP] ?? 0);

        $gateway = $input[self::GATEWAY];

        $enabledApps = null;

        if (($app !== 1) or (in_array($gateway, Gateway::$methodMap[Method::APP], true) === false))
        {
            return;
        }

        if ($app === 1)
        {
            $enabledApps = AppMethod::getSupportedAppsForGateway($gateway);
        }

        $this->setEnabledApps($enabledApps);
    }

    protected function generateEnabledWallets(array $input)
    {
        $gateway = $input[self::GATEWAY];

        $walletGateways = Payment\Gateway::$methodMap[Payment\Method::WALLET];

        if ((in_array($gateway, $walletGateways, true) === false))
        {
            return;
        }

        $enabledWallets = Payment\Gateway::getSupportedWalletsForGateway($gateway);

        $this->setEnabledWallets($enabledWallets);
    }

    public function edit(array $input = [], $operation = 'edit')
    {
        $this->getValidator()->editTerminalValidator($this, $input);

        $this->fill($input);
    }

    public function incrementUsedCount()
    {
        $usedCount = $this->getUsedCount() + 1;

        $this->setAttribute(self::USED_COUNT, $usedCount);
    }

    public function setUsed()
    {
        $this->setAttribute(self::USED, true);
    }

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function merchants()
    {
        return $this->belongsToMany('RZP\Models\Merchant\Entity', Table::MERCHANT_TERMINAL);
    }

    public function org()
    {
        return $this->belongsTo(
            'RZP\Models\Admin\Org\Entity');
    }

    public function toArrayWithPassword(bool $proxy = true)
    {
        $terminal = $this->toArray();


        $apiTerminalPassword = $this->getGatewayTerminalPasswordAttribute();
        $apiTerminalPassword2 = $this->getGatewayTerminalPassword2Attribute();
        $apiTerminalSecret = $this->getGatewaySecureSecretAttribute();
        $apiTerminalSecret2= $this->getGatewaySecureSecret2Attribute();
        $apiReconPassword = $this->getGatewayReconPassword();

        $terminal[self::GATEWAY_TERMINAL_PASSWORD]  = $this->getGatewayTerminalPasswordAttribute();
        $terminal[self::GATEWAY_TERMINAL_PASSWORD2] = $this->getGatewayTerminalPassword2Attribute();
        $terminal[self::GATEWAY_SECURE_SECRET]      = $this->getGatewaySecureSecretAttribute();
        $terminal[self::GATEWAY_SECURE_SECRET2]      = $this->getGatewaySecureSecret2Attribute();
        $terminal[self::GATEWAY_RECON_PASSWORD]     = $this->getGatewayReconPassword();

        if(app()->runningUnitTests() === true)
        {
            $proxy = false;
        }

        // get credential from terminal service
        if ($proxy === true)
        {
            $app = App::getFacadeRoot();

            try
            {
                $terminalId = $terminal['id'];

                $app['trace']->info(TraceCode::TERMINALS_SERVICE_PROXY_CREDENTIAL_FETCH_REQUEST, ["Id" => $terminalId]);

                $path = "v2/terminals/credentials/" . $terminal[Entity::ID];

                $response = $app['terminals_service']->proxyTerminalService("", "GET", $path, ['timeout' => 3]);

                // compare secrets
                $tsTerminalPassword = $response["terminal"]["secrets"][Entity::GATEWAY_TERMINAL_PASSWORD];
                $tsTerminalPassword2 = $response["terminal"]["secrets"][Entity::GATEWAY_TERMINAL_PASSWORD2];
                $tsTerminalSecret = $response["terminal"]["secrets"][Entity::GATEWAY_SECURE_SECRET];
                $tsTerminalSecret2 = $response["terminal"]["secrets"][Entity::GATEWAY_SECURE_SECRET2];
                $tsReconPassword = $response["terminal"]["secrets"][Entity::GATEWAY_RECON_PASSWORD];

                if ((empty($apiTerminalPassword) === false) and ($apiTerminalPassword !== $tsTerminalPassword)) {
                    $app['trace']->info(TraceCode::TERMINALS_SERVICE_PROXY_CREDENTIAL_MISMAATCH, ["key" => "gateway_terminal_password", "id" => $terminalId]);
                }

                if ((empty($apiTerminalPassword2) === false) and ($apiTerminalPassword2 !== $tsTerminalPassword2)) {
                    $app['trace']->info(TraceCode::TERMINALS_SERVICE_PROXY_CREDENTIAL_MISMAATCH, ["key" => "gateway_terminal_password2", "id" => $terminalId]);
                }

                if ((empty($apiTerminalSecret) === false) and ($apiTerminalSecret !== $tsTerminalSecret)) {
                    $app['trace']->info(TraceCode::TERMINALS_SERVICE_PROXY_CREDENTIAL_MISMAATCH, ["key" => "gateway_secure_secret", "id" => $terminalId]);
                }

                if ((empty($apiTerminalSecret2) === false) and ($apiTerminalSecret2 !== $tsTerminalSecret2)) {
                    $app['trace']->info(TraceCode::TERMINALS_SERVICE_PROXY_CREDENTIAL_MISMAATCH, ["key" => "gateway_secure_secret2", "id" => $terminalId]);
                }

                if ((empty($apiReconPassword) === false) and ($apiReconPassword !== $tsReconPassword)) {
                    $app['trace']->info(TraceCode::TERMINALS_SERVICE_PROXY_CREDENTIAL_MISMAATCH, ["key" => "gateway_recon_password", "id" => $terminalId]);
                }

                $terminal[self::GATEWAY_TERMINAL_PASSWORD] = $tsTerminalPassword;
                $terminal[self::GATEWAY_TERMINAL_PASSWORD2] = $tsTerminalPassword2;
                $terminal[self::GATEWAY_SECURE_SECRET] = $tsTerminalSecret;
                $terminal[self::GATEWAY_SECURE_SECRET2] = $tsTerminalSecret2;
                $terminal[self::GATEWAY_RECON_PASSWORD] = $tsReconPassword;

            }
            catch (\Throwable $ex)
            {
                $app['trace']->traceException(
                    $ex,
                    Trace::ERROR,
                    TraceCode::TERMINALS_SERVICE_PROXY_CREDENTIAL_FETCH_FAILED,
                    [
                        'Id' => $terminalId
                    ]);

                $metricData = [
                    "function" => __FUNCTION__
                ];

                $app['trace']->count(Metric::TERMINAL_CREDENTIAL_FETCH_FAILURE, $metricData);
            }
        }

        return $terminal;
    }

    public function isGateway($gateway)
    {
        return ($this->getAttribute(self::GATEWAY) === $gateway);
    }

    public function isGatewayAcquirer($acquirer)
    {
        return ($this->getAttribute(self::GATEWAY_ACQUIRER) === $acquirer);
    }

    public function isDeleted()
    {
        return ($this->getAttribute(self::DELETED_AT) !== null);
    }

    public function matchEncryptedAttribute($attribute, $value)
    {
        $actualValue = $this->getAttribute($attribute);

        return ($value === $actualValue);
    }

    public function isTpv()
    {
        return $this->getAttribute(self::TPV);
    }

    public function isNotTpv()
    {
        return ($this->isTpv() === false);
    }

    public function isTpvAllowed() : bool
    {
        $tpv = $this->getAttribute(self::TPV);

        return TpvType::isTpvAllowed($tpv);
    }

    public function isNonTpvAllowed() : bool
    {
        $tpv = $this->getAttribute(self::TPV);

        return TpvType::isNonTpvAllowed($tpv);
    }

    public function isValidEmiTerminal($gateway, $emiDuration)
    {
        $ignoreEmiDurationGateways = [
            Gateway::BAJAJ,
            Gateway::HDFC_DEBIT_EMI,
        ];

        if (($this->isEmiEnabled()) and
            ($this->getGateway() === $gateway) and
            (($this->getEmiDuration() === $emiDuration) or (in_array($gateway, $ignoreEmiDurationGateways) === true)))
        {
            return true;
        }

        return false;
    }

    public function isTypeApplicable(string $type): bool
    {
        $enabledTypes = $this->getType();

        return in_array($type, $enabledTypes, true);
    }

    public function isNonRecurring()
    {
        return ($this->isTypeApplicable(Type::NON_RECURRING) === true);
    }

    public function isRecurring()
    {
        return (($this->is3DSRecurring() === true) or
                ($this->isNon3DSRecurring() === true));
    }

    public function is3DSRecurring()
    {
        return ($this->isTypeApplicable(Type::RECURRING_3DS) === true);
    }

    public function isNon3DSRecurring()
    {
        return ($this->isTypeApplicable(Type::RECURRING_NON_3DS) === true);
    }

    public function isAuthTypeEnabled($authType, $networkCode)
    {
        switch ($authType)
        {
            case Payment\AuthType::PIN:
                $isEnabled = $this->isPin();
                break;

            case Payment\AuthType::OTP:
                $gateway = $this->getGateway();

                $isEnabled = (($this->isIvr() === true) or
                              (Payment\Gateway::supportsHeadlessBrowser($gateway, $networkCode) === true));

                break;

            default:
                $isEnabled = (($this->isPin() === false) and ($this->isIvr() === false));
                break;
        }

        return $isEnabled;
    }

    public function isDebitRecurring()
    {
        return ($this->isTypeApplicable(Type::DEBIT_RECURRING) === true);
    }

    public function isCollectTerminal()
    {
        return ($this->isTypeApplicable(Type::COLLECT) === true);
    }

    public function isNo2fa()
    {
        return ($this->isTypeApplicable(Type::NO_2FA) === true);
    }

    public function isIvr()
    {
        return ($this->isTypeApplicable(Type::IVR) === true);
    }

    public function isPay()
    {
        return ($this->isTypeApplicable(Type::PAY) === true);
    }

    public function isOtmPay()
    {
        return ($this->isTypeApplicable(Type::OTM_PAY) === true);
    }

    public function isOtmCollect()
    {
        return ($this->isTypeApplicable(Type::OTM_COLLECT) === true);
    }

    public function isMandateHub()
    {
        return ($this->isTypeApplicable(Type::MANDATE_HUB) === true);
    }

    public function isOptimizer()
    {
        return ($this->isTypeApplicable(Type::OPTIMIZER) === true);
    }

    public function isDisableOptimiserRefunds()
    {
        return ($this->isTypeApplicable(TYPE::DISABLE_OPTIMISER_REFUNDS) === true);
    }

    public function isEnableAutoDebit()
    {
        return ($this->isTypeApplicable(TYPE::ENABLE_AUTO_DEBIT) === true);
    }

    public function isOnline()
    {
        return ($this->isTypeApplicable(Type::ONLINE) === true);
    }

    public function isOffline()
    {
        return ($this->isTypeApplicable(Type::OFFLINE) === true);
    }

    public function isPin()
    {
        return ($this->isTypeApplicable(Type::PIN) === true);
    }

    public function isBharatQr()
    {
        return ($this->isTypeApplicable(Type::BHARAT_QR) === true);
    }

    public function isUpiTransfer()
    {
        return ($this->isTypeApplicable(Type::UPI_TRANSFER) === true);
    }

    public function isInAPP() {
        return ($this->isTypeApplicable(Type::IN_APP) === true);
    }

    public function isIOS() {
        return ($this->isTypeApplicable(Type::IOS) === true);
    }

    public function isAndroid() {
        return ($this->isTypeApplicable(Type::ANDROID) === true);
    }

    public function isMoto()
    {
        return ($this->isTypeApplicable(Type::MOTO) === true);
    }

    public function isDirectSettlement()
    {
        return (($this->isDirectSettlementWithRefund() === true) or
                ($this->isDirectSettlementWithoutRefund() === true));
    }

    public function isDirectSettlementWithRefund(): bool
    {
        return ($this->isTypeApplicable(Type::DIRECT_SETTLEMENT_WITH_REFUND) === true);
    }

    public function isDirectSettlementWithoutRefund()
    {
        return ($this->isTypeApplicable(Type::DIRECT_SETTLEMENT_WITHOUT_REFUND) === true);
    }

    public function isPos()
    {
        return ($this->isTypeApplicable(Type::POS) === true);
    }

    public function isInternational()
    {
        return $this->getAttribute(self::INTERNATIONAL);
    }

    public function isAuthCapture()
    {
        return ($this->getAttribute(self::MODE) === Mode::AUTH_CAPTURE);
    }

    public function isPurchase()
    {
        return ($this->getAttribute(self::MODE) === Mode::PURCHASE);
    }

    public function isDomestic()
    {
        return ($this->isCardEnabled() === true);
    }

    public function isSyncStatusSuccess()
    {
        return $this->getSyncStatus() === SyncStatus::SYNC_SUCCESS;
    }

    public function isValidVirtualVpaForTerminal(string $virtualVpa)
     {
        $prefix = $this->getAttribute(self::VIRTUAL_UPI_ROOT) . $this->getAttribute(self::VIRTUAL_UPI_MERCHANT_PREFIX);

        $handle = $this->getAttribute(self::VIRTUAL_UPI_HANDLE);

        return ((strcasecmp(substr($virtualVpa, 0, strlen($prefix)), $prefix) === 0) &&
                (strcasecmp(substr($virtualVpa, -strlen($handle), strlen($virtualVpa)), $handle) === 0));
    }

    public function isQrV2Terminal()
    {
        return (($this->isOnline() === true) or
                ($this->isOffline() === true));
    }

    /**
     * This is being overridden because, we don't always want to add sub merchants
     * to the serialized data as it involves a db call. Only when serializing
     * an individual terminal entity, we want to do it
     *
     * @param  boolean $subMerchantFlag Flag ti indicate if sub_merchants should be included
     *
     * @return array
     */
    public function toArrayPublic($subMerchantFlag = false)
    {
        if ($subMerchantFlag === true)
        {
            $this->publicSetters[] = Entity::SUB_MERCHANTS;
        }

        return parent::toArrayPublic();
    }

    public function toArrayAdmin($subMerchantFlag = false)
    {
        if ($subMerchantFlag === true)
        {
            $this->publicSetters[] = Entity::SUB_MERCHANTS;
        }

        return parent::toArrayAdmin();
    }

    public static function getCacheTag($id)
    {
        return implode('_', [E::TERMINAL, $id]);
    }

    public function isTerminalOnlyOnTerminalsService(): bool
    {
        $isActivatedPayPalTerminal = ($this->getStatus() == Status::ACTIVATED && $this->getGateway() == Gateway::WALLET_PAYPAL);
        if ($isActivatedPayPalTerminal)
        {
            return false;
        }
        return in_array($this->getGateway(), self::gatewaysOnlyOnTs) || in_array($this->getGateway(), Gateway::TOKENISATION_GATEWAYS);
    }

    public function isSodexo() {
        return ($this->isTypeApplicable(Type::SODEXO) === true);
    }
}
