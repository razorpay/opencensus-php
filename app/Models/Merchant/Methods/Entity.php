<?php

namespace RZP\Models\Merchant\Methods;

use RZP\Models\Base;
use RZP\Models\Bank\Bank;
use RZP\Models\Card\Network;
use RZP\Models\Feature;
use RZP\Models\Base\QueryCache\Cacheable;
use RZP\Models\Card\SubType;
use RZP\Models\Card\Type;
use RZP\Models\Emi\DebitProvider;
use RZP\Models\Payment\Gateway;
use RZP\Models\Payment\Processor\Wallet;
use RZP\Models\Payment\Processor\Fpx as FpxProcessor;
use RZP\Models\Payment\Processor\IntlBankTransfer;
use RZP\Models\Payment\Processor\Netbanking as NetbankingProcessor;
use RZP\Models\Payment\Processor\App as AppMethod;
use RZP\Models\Emi\CreditEmiProvider;
use RZP\Models\Emi\PaylaterProvider;
use RZP\Models\Emi\CardlessEmiProvider;


class Entity extends Base\PublicEntity
{
    use Cacheable;

    const MERCHANT_ID       = 'merchant_id';
    const CARD              = 'card';
    const NETBANKING        = 'netbanking';
    const AMEX              = 'amex';
    const DISABLED_BANKS    = 'disabled_banks';
    const ENABLED_BANKS     = 'enabled_banks';
    const BANKS             = 'banks';
    const MOBIKWIK          = 'mobikwik';
    const OLAMONEY          = 'olamoney';
    const PAYTM             = 'paytm';
    const PAYZAPP           = 'payzapp';
    const PAYUMONEY         = 'payumoney';
    const AIRTELMONEY       = 'airtelmoney';
    const AMAZONPAY         = 'amazonpay';
    const FREECHARGE        = 'freecharge';
    const JIOMONEY          = 'jiomoney';
    const SBIBUDDY          = 'sbibuddy';
    const OPENWALLET        = 'openwallet';
    const RAZORPAYWALLET    = 'razorpaywallet';
    const MPESA             = 'mpesa';
    const EMI               = 'emi';
    const OFFLINE           = 'offline';
    const DEBIT_CARD        = 'debit_card';
    const CREDIT_CARD       = 'credit_card';
    const PREPAID_CARD      = 'prepaid_card';
    const CARD_SUBTYPE      = 'card_subtype';
    const UPI               = 'upi';
    const UPI_TYPE          = 'upi_type';
    const BANK_TRANSFER     = 'bank_transfer';
    const AEPS              = 'aeps';
    const EMANDATE          = 'emandate';
    const NACH              = 'nach';
    const CARDLESS_EMI      = 'cardless_emi';
    const PAYLATER          = 'paylater';
    const CARD_NETWORKS     = 'card_networks';
    const PHONEPE           = 'phonepe';
    const PHONEPE_SWITCH    = 'phonepeswitch';
    const PAYPAL            = 'paypal';
    const GOOGLE_PAY_CARDS  = 'google_pay_cards';
    const GPAY              = 'gpay';
    const APPS              = 'apps';
    const HDFC_DEBIT_EMI    = 'hdfc_debit_emi';
    const COD               = 'cod';
    const FPX               = 'fpx';
    const IN_APP            = 'in_app';
    const BAJAJPAY          = 'bajajpay';
    const GRABPAY           = 'grabpay';
    const TOUCHNGO          = 'touchngo';
    const BOOST             = 'boost';
    const MCASH             = 'mcash';
    const SODEXO            = 'sodexo';

    const DEBIT_EMI_PROVIDERS = 'debit_emi_providers';
    const CREDIT_EMI_PROVIDERS  = 'credit_emi_providers';
    const CARDLESS_EMI_PROVIDERS = 'cardless_emi_providers';
    const CREDIT_EMI = 'credit_emi';
    const PAYLATER_PROVIDERS = 'paylater_providers';
    const EMI_TYPES           = 'emi_types';

    const METHODS           = 'methods';

    const ADDITIONAL_WALLETS = 'additional_wallets';
    const ADDON_METHODS      = 'addon_methods';

    //new wallets
    const ITZCASH = 'itzcash';
    const OXIGEN  = 'oxigen';
    const AMEXEASYCLICK = 'amexeasyclick';
    const PAYCASH = 'paycash';
    const CITIBANKREWARDS = 'citibankrewards';

    const INTL_BANK_TRANSFER = 'intl_bank_transfer';

    protected $primaryKey = self::MERCHANT_ID;

    // Table name has been renamed to 'merchant_banks'
    protected $entity = 'methods';

    protected $revisionEnabled = true;

    protected $revisionCreationsEnabled = true;

    protected $fillable = [
        self::AMEX,
        self::DISABLED_BANKS,
        self::PAYTM,
        self::PAYZAPP,
        self::PAYUMONEY,
        self::AIRTELMONEY,
        self::FREECHARGE,
        self::MOBIKWIK,
        self::OLAMONEY,
        self::JIOMONEY,
        self::SBIBUDDY,
        self::OPENWALLET,
        self::RAZORPAYWALLET,
        self::MPESA,
        self::EMI,
        self::UPI,
        self::UPI_TYPE,
        self::AEPS,
        self::EMANDATE,
        self::NACH,
        self::NETBANKING,
        self::DEBIT_CARD,
        self::CREDIT_CARD,
        self::PREPAID_CARD,
        self::CARD_SUBTYPE,
        self::BANK_TRANSFER,
        self::AMAZONPAY,
        self::CARDLESS_EMI,
        self::PAYLATER,
        self::CARD_NETWORKS,
        self::PHONEPE,
        self::PHONEPE_SWITCH,
        self::PAYPAL,
        self::APPS,
        self::DEBIT_EMI_PROVIDERS,
        self::ADDITIONAL_WALLETS,
        self::COD,
        self::OFFLINE,
        self::FPX,
        self::ADDON_METHODS,
        self::BAJAJPAY,
        self::BOOST,
        self::MCASH,
        self::GRABPAY,
        self::TOUCHNGO,
    ];

    protected $visible = [
        self::MERCHANT_ID,
        self::CARD,
        self::AMEX,
        self::DISABLED_BANKS,
        self::PAYTM,
        self::PAYZAPP,
        self::PAYUMONEY,
        self::AIRTELMONEY,
        self::FREECHARGE,
        self::MOBIKWIK,
        self::OLAMONEY,
        self::JIOMONEY,
        self::SBIBUDDY,
        self::OPENWALLET,
        self::RAZORPAYWALLET,
        self::MPESA,
        self::EMI,
        self::UPI,
        self::UPI_TYPE,
        self::AEPS,
        self::EMANDATE,
        self::NACH,
        self::NETBANKING,
        self::DEBIT_CARD,
        self::CREDIT_CARD,
        self::PREPAID_CARD,
        self::CARD_SUBTYPE,
        self::BANK_TRANSFER,
        self::AMAZONPAY,
        self::CARDLESS_EMI,
        self::PAYLATER,
        self::CARD_NETWORKS,
        self::PHONEPE,
        self::PHONEPE_SWITCH,
        self::PAYPAL,
        self::APPS,
        self::DEBIT_EMI_PROVIDERS,
        self::CREDIT_EMI_PROVIDERS,
        self::CARDLESS_EMI_PROVIDERS,
        self::PAYLATER_PROVIDERS,
        self::ITZCASH,
        self::OXIGEN,
        self::AMEXEASYCLICK,
        self::PAYCASH,
        self::CITIBANKREWARDS,
        self::COD,
        self::OFFLINE,
        self::FPX,
        self::IN_APP,
        self::BAJAJPAY,
        self::BOOST,
        self::MCASH,
        self::GRABPAY,
        self::TOUCHNGO,
        self::INTL_BANK_TRANSFER,
        self::SODEXO,
    ];

    protected $public = [
        self::MERCHANT_ID,
        self::CARD,
        self::AMEX,
        self::DISABLED_BANKS,
        self::PAYTM,
        self::PAYZAPP,
        self::PAYUMONEY,
        self::AIRTELMONEY,
        self::FREECHARGE,
        self::MOBIKWIK,
        self::OLAMONEY,
        self::JIOMONEY,
        self::SBIBUDDY,
        self::OPENWALLET,
        self::RAZORPAYWALLET,
        self::MPESA,
        self::EMI,
        self::UPI,
        self::UPI_TYPE,
        self::AEPS,
        self::EMANDATE,
        self::NACH,
        self::NETBANKING,
        self::DEBIT_CARD,
        self::CREDIT_CARD,
        self::PREPAID_CARD,
        self::CARD_SUBTYPE,
        self::ENTITY,
        self::BANK_TRANSFER,
        self::AMAZONPAY,
        self::CARDLESS_EMI,
        self::CARD_NETWORKS,
        self::PHONEPE,
        self::PHONEPE_SWITCH,
        self::PAYLATER,
        self::PAYPAL,
        self::APPS,
        self::DEBIT_EMI_PROVIDERS,
        self::CREDIT_EMI_PROVIDERS,
        self::CARDLESS_EMI_PROVIDERS,
        self::PAYLATER_PROVIDERS,
        self::ITZCASH,
        self::OXIGEN,
        self::AMEXEASYCLICK,
        self::PAYCASH,
        self::CITIBANKREWARDS,
        self::COD,
        self::OFFLINE,
        self::FPX,
        self::IN_APP,
        self::BAJAJPAY,
        self::BOOST,
        self::MCASH,
        self::GRABPAY,
        self::TOUCHNGO,
        self::INTL_BANK_TRANSFER,
        self::SODEXO,
    ];

    protected $appends = [
        self::ITZCASH,
        self::OXIGEN,
        self::AMEXEASYCLICK,
        self::PAYCASH,
        self::CITIBANKREWARDS,
        self::IN_APP,
        self::CREDIT_EMI_PROVIDERS ,
        self::CARDLESS_EMI_PROVIDERS ,
        self::PAYLATER_PROVIDERS ,
        self::BAJAJPAY,
        self::INTL_BANK_TRANSFER,
        self::PAYZAPP,
        self::BOOST,
        self::MCASH,
        self::GRABPAY,
        self::TOUCHNGO,
        self::SODEXO,
    ];


    //
    // If adding any default methods here, also add
    // in $defaultPaymentMethodsForSubmerchantByPartner with default as false.
    //
    protected $defaults = array(
        self::CARD_NETWORKS  => Network::DEFAULT_CARD_NETWORKS,
        self::AMEX           => false,
        self::PAYTM          => false,
        self::MOBIKWIK       => true,
        self::PAYZAPP        => false,
        self::PAYUMONEY      => false,
        self::AIRTELMONEY    => false,
        self::OLAMONEY       => false,
        self::FREECHARGE     => false,
        self::JIOMONEY       => false,
        self::SBIBUDDY       => false,
        self::OPENWALLET     => false,
        self::RAZORPAYWALLET => false,
        self::MPESA          => false,
        self::DISABLED_BANKS => NetbankingProcessor::DEFAULT_DISABLED_BANKS,
        self::BANKS          => '[]',
        self::EMI            => EmiType::DEFAULT_TYPES,
        self::UPI            => true,
        self::UPI_TYPE       => 3,
        self::AEPS           => false,
        self::EMANDATE       => false,
        self::NACH           => false,
        self::NETBANKING     => true,
        self::CREDIT_CARD    => true,
        self::DEBIT_CARD     => true,
        self::PREPAID_CARD   => true,
        self::CARD_SUBTYPE   => SubType::DEFAULT_CARD_SUBTYPE,
        self::BANK_TRANSFER  => true,
        self::AMAZONPAY      => false,
        self::CARDLESS_EMI   => false,
        self::PAYLATER       => false,
        self::PHONEPE        => false,
        self::PHONEPE_SWITCH => false,
        self::PAYPAL         => false,
        self::APPS           => AppMethod::DEFAULT_APPS,
        self::DEBIT_EMI_PROVIDERS => DebitProvider::DEFAULT_DEBIT_EMI_PROVIDERS,
        self::ADDITIONAL_WALLETS => [],
        self::COD            => false,
        self::OFFLINE        => false,
        self::FPX            => false,
        self::ADDON_METHODS  => [],
    );

    public static $defaultPaymentMethodsForSubmerchantByPartner = array(
        self::AMEX           => false,
        self::PAYTM          => false,
        self::MOBIKWIK       => false,
        self::PAYZAPP        => false,
        self::PAYUMONEY      => false,
        self::AIRTELMONEY    => false,
        self::OLAMONEY       => false,
        self::FREECHARGE     => false,
        self::JIOMONEY       => false,
        self::SBIBUDDY       => false,
        self::OPENWALLET     => false,
        self::RAZORPAYWALLET => false,
        self::MPESA          => false,
        self::DISABLED_BANKS => NetbankingProcessor::DEFAULT_DISABLED_BANKS,
        self::EMI            => EmiType::DEFAULT_TYPES,
        self::UPI            => false,
        self::AEPS           => false,
        self::EMANDATE       => false,
        self::NACH           => false,
        self::NETBANKING     => false,
        self::CREDIT_CARD    => false,
        self::DEBIT_CARD     => false,
        self::PREPAID_CARD   => false,
        self::CARD_SUBTYPE   => SubType::DEFAULT_CARD_SUBTYPE,
        self::BANK_TRANSFER  => false,
        self::AMAZONPAY      => false,
        self::CARDLESS_EMI   => false,
        self::PAYLATER       => false,
        self::PHONEPE        => false,
        self::PHONEPE_SWITCH => false,
        self::PAYPAL         => false,
        self::ADDITIONAL_WALLETS => [],
        self::COD            => false,
        self::OFFLINE        => false,
        self::FPX            => false,
        self::ADDON_METHODS  => [],
    );

    protected $wallets = array(
        self::MOBIKWIK,
        self::PAYTM,
        self::PAYZAPP,
        self::PAYUMONEY,
        self::OLAMONEY,
        self::AIRTELMONEY,
        self::AMAZONPAY,
        self::FREECHARGE,
        self::JIOMONEY,
        self::SBIBUDDY,
        self::OPENWALLET,
        self::RAZORPAYWALLET,
        self::MPESA,
        self::PHONEPE,
        self::PAYPAL,
        self::ITZCASH,
        self::OXIGEN,
        self::AMEXEASYCLICK,
        self::PAYCASH,
        self::CITIBANKREWARDS,
        self::BAJAJPAY,
        self::BOOST,
        self::GRABPAY,
        self::TOUCHNGO,
        self::MCASH
    );


    protected static $additional_wallet_names = [
        self::ITZCASH,
        self::OXIGEN,
        self::AMEXEASYCLICK,
        self::PAYCASH,
        self::CITIBANKREWARDS,
        self::BAJAJPAY,
        self::PAYZAPP,
        self::TOUCHNGO,
        self::GRABPAY,
        self::MCASH,
        self::BOOST
    ];

    protected static $aff_method_public_name_mapping = [
        self::CREDIT_EMI => self::CREDIT_EMI_PROVIDERS,
        self::PAYLATER => self::PAYLATER_PROVIDERS,
        self::CARDLESS_EMI => self::CARDLESS_EMI_PROVIDERS,
    ];

    protected static $addon_affordability_methods = [
        self::CREDIT_EMI,
        self::PAYLATER,
        self::CARDLESS_EMI,
    ];

    protected static $addon_methods_names = [
        self::UPI => [
            self::IN_APP
        ],
        self::INTL_BANK_TRANSFER => [
            IntlBankTransfer::ACH,
            IntlBankTransfer::SWIFT
        ],
        self::CREDIT_EMI => [
            CreditEmiProvider::HDFC,
            CreditEmiProvider::SBIN,
            CreditEmiProvider::UTIB,
            CreditEmiProvider::ICIC,
            CreditEmiProvider::AMEX,
            CreditEmiProvider::BARB,
            CreditEmiProvider::CITI,
            CreditEmiProvider::HSBC,
            CreditEmiProvider::INDB,
            CreditEmiProvider::KKBK,
            CreditEmiProvider::RATN,
            CreditEmiProvider::SCBL,
            CreditEmiProvider::YESB,
            CreditEmiProvider::ONECARD,
            CreditEmiProvider::BAJAJ,
            CreditEmiProvider::FDRL
        ],
        self::CARDLESS_EMI => [
            CardlessEmiProvider::WALNUT369,
            CardlessEmiProvider::ZESTMONEY,
            CardlessEmiProvider::EARLYSALARY,
            CardlessEmiProvider::HDFC,
            CardlessEmiProvider::ICIC,
            CardlessEmiProvider::BARB,
            CardlessEmiProvider::KKBK,
            CardlessEmiProvider::FDRL,
            CardlessEmiProvider::IDFB,
            CardlessEmiProvider::HCIN,
            CardlessEmiProvider::KRBE,
            CardlessEmiProvider::CSHE,
            CardlessEmiProvider::TVSC,
        ],
        self::PAYLATER => [
            Paylaterprovider::GETSIMPL,
            Paylaterprovider::LAZYPAY,
            Paylaterprovider::ICIC,
            Paylaterprovider::HDFC
        ],

        self::CARD => [
            self::SODEXO
        ]
    ];


    protected static $methods = [
        self::CARD,
        self::EMI,
        self::AMEX,
        self::UPI,
        self::BANK_TRANSFER,
        self::AEPS,
        self::EMANDATE,
        self::NACH,
        self::NETBANKING,
        self::PAYTM,
        self::MOBIKWIK,
        self::PAYZAPP,
        self::PAYUMONEY,
        self::OLAMONEY,
        self::AIRTELMONEY,
        self::AMAZONPAY,
        self::FREECHARGE,
        self::MPESA,
        self::CARDLESS_EMI,
        self::PAYLATER,
        self::PHONEPE,
        self::PHONEPE_SWITCH,
        self::PAYPAL,
        self::APPS,
        self::COD,
        self::OFFLINE,
        self::FPX,
    ];

    // Casts the attributes to native types
    protected $casts = [
        self::AMEX          => 'bool',
        self::PAYTM         => 'bool',
        self::CREDIT_CARD   => 'bool',
        self::DEBIT_CARD    => 'bool',
        self::PREPAID_CARD  => 'bool',
        self::NETBANKING    => 'bool',
        self::MOBIKWIK      => 'bool',
        self::OLAMONEY      => 'bool',
        self::PAYZAPP       => 'bool',
        self::PAYUMONEY     => 'bool',
        self::AIRTELMONEY   => 'bool',
        self::AMAZONPAY     => 'bool',
        self::FREECHARGE    => 'bool',
        self::JIOMONEY      => 'bool',
        self::SBIBUDDY      => 'bool',
        self::OPENWALLET    => 'bool',
        self::RAZORPAYWALLET=> 'bool',
        self::MPESA         => 'bool',
        self::EMI           => 'int',
        self::UPI           => 'bool',
        self::BANK_TRANSFER => 'bool',
        self::AEPS          => 'bool',
        self::EMANDATE      => 'bool',
        self::NACH          => 'bool',
        self::CARDLESS_EMI  => 'bool',
        self::PAYLATER      => 'bool',
        self::PHONEPE       => 'bool',
        self::PHONEPE_SWITCH=> 'bool',
        self::PAYPAL        => 'bool',
        self::COD           => 'bool',
        self::OFFLINE       => 'bool',
        self::FPX           => 'bool',
        self::BAJAJPAY      => 'bool',
    ];

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function isCardEnabled()
    {
        return (($this->isDebitCardEnabled()) or
                ($this->isCreditCardEnabled()) or
                ($this->isPrepaidCardEnabled()));
    }

    public function isPaypalEnabled()
    {
        return $this->getAttribute(self::PAYPAL);
    }

    public function isDebitCardEnabled()
    {
        return $this->getAttribute(self::DEBIT_CARD);
    }

    public function isPrepaidCardEnabled()
    {
        return $this->getAttribute(self::PREPAID_CARD);
    }

    public function isCreditCardEnabled()
    {
        return $this->getAttribute(self::CREDIT_CARD);
    }

    public function isNetbankingEnabled()
    {
        return $this->getAttribute(self::NETBANKING);
    }

    public function isFpxEnabled()
    {
        return $this->getAttribute(self::FPX);
    }

    public function isUpiEnabled()
    {
        return $this->getAttribute(self::UPI);
    }

    public function getUpiTypeFromRepo():int
    {
        $upi = $this->attributes[self::UPI_TYPE];

        return $upi;
    }

    public function isUpiIntentEnabled():bool
    {
        $upiTypes = $this->getUpiTypes();

        return $upiTypes[UpiType::INTENT] === 1;
    }

    public function isUpiCollectEnabled():bool
    {
        $upiTypes = $this->getUpiTypes();

        return $upiTypes[UpiType::COLLECT] === 1;
    }

    public function isBankTransferEnabled()
    {
        return $this->getAttribute(self::BANK_TRANSFER);
    }

    public function isAepsEnabled()
    {
        return $this->getAttribute(self::AEPS);
    }

    public function isWalletEnabled($wallet = null)
    {
        if ($wallet === null)
        {
            return $this->isAnyWalletEnabled();
        }

        return $this->{'is'.ucfirst($wallet).'Enabled'}();
    }

    public function isAnyWalletEnabled()
    {
        foreach ($this->wallets as $wallet)
        {
            if ($this->isWalletEnabled($wallet) === true)
            {
                return true;
            }
        }

        return false;
    }

    public function isCardNetworkEnabled(string $network): bool
    {
        if (Network::isValidNetworkCode($network) === false)
        {
            return false;
        }

        return ((bool) $this->getCardNetworks()[$network]);
    }

    public function isAppEnabled(string $app = ""): bool
    {
        if (AppMethod::isValidApp($app) === false)
        {
            return false;
        }

        return ((bool) $this->getApps()[$app]);
    }

    public function isIntlBankTransferEnabled(string $mode = ""): bool
    {
        $addonMethods = $this->getAddonMethods();

        if(($addonMethods !== null) and (isset($addonMethods[self::INTL_BANK_TRANSFER])) and (IntlBankTransfer::isValidIntlBankTransferMode($mode)))
        {
            return $addonMethods[self::INTL_BANK_TRANSFER][$mode] === 1;
        }
        return false;
    }

    public function isSubTypeEnabled(string $subtype): bool
    {
        if (SubType::isValidSubType($subtype) === false)
        {
            return false;
        }

        return ((bool) $this->getCardSubtypes()[$subtype]);
    }

    public function isAmexEnabled()
    {
        return ((bool) $this->getCardNetworks()[Network::AMEX]);
    }


    public function isPaytmEnabled()
    {
        return $this->getAttribute(self::PAYTM);
    }

    public function isPayzappEnabled()
    {
        return $this->getPayzapp();
    }

    public function isOlamoneyEnabled()
    {
        return $this->getAttribute(self::OLAMONEY);
    }

    public function isPhonepeswitchEnabled()
    {
        return $this->getAttribute(self::PHONEPE_SWITCH);
    }

    public function isPhonepeEnabled()
    {
        return $this->getAttribute(self::PHONEPE);
    }

    public function isAirtelmoneyEnabled()
    {
        return $this->getAttribute(self::AIRTELMONEY);
    }

    public function isAmazonpayEnabled()
    {
        return $this->getAttribute(self::AMAZONPAY);
    }

    public function isMpesaEnabled()
    {
        return false;

        return $this->getAttribute(self::MPESA);
    }

    public function isPayumoneyEnabled()
    {
        return false;
    }

    public function isFreechargeEnabled()
    {
        return $this->getAttribute(self::FREECHARGE);
    }

    public function isBajajPayEnabled(): bool
    {
        return $this->getBajajPay();
    }

    public function isBoostEnabled(): bool
    {
        return $this->getBoost();
    }

    public function isMcashEnabled(): bool
    {
        return $this->getMCash();
    }

    public function isTouchngoEnabled(): bool
    {
        return $this->getTouchNGo();
    }

    public function isGrabpayEnabled(): bool
    {
        return $this->getGrabPay();
    }

    public function isMobikwikEnabled()
    {
        return $this->getAttribute(self::MOBIKWIK);
    }

    public function isOpenwalletEnabled()
    {
        return $this->getAttribute(self::OPENWALLET);
    }

    public function isRazorpaywalletEnabled()
    {
        return $this->getAttribute(self::RAZORPAYWALLET);
    }

    public function isJiomoneyEnabled()
    {
        return $this->getAttribute(self::JIOMONEY);
    }

    public function isSbibuddyEnabled()
    {
        /*
         Disabling sbibuddy permanently, this gateway has been shutdown since 2019
         https://jira.corp.razorpay.com/browse/NBPLUS-688
        */
        //return $this->getAttribute(self::SBIBUDDY);
        return false;
    }

    public function isItzcashEnabled()
    {
//        return $this->getItzcash();
        return false;
    }

    public function isOxigenEnabled()
    {
//        return $this->getOxigen();
        return false;
    }

    public function isAmexeasyclickEnabled()
    {
//        return $this->getAmexeasyclick();
        return false;
    }

    public function isPaycashEnabled()
    {
//        return $this->getPaycash();
        return false;
    }

    public function isCitibankrewardsEnabled()
    {
//        return $this->getCitibankrewards();
        return false;
    }

    public function isEmiEnabled()
    {
        return ($this->isCreditEmiEnabled() || $this->isDebitEmiEnabled());
    }

    public function isCreditEmiEnabled()
    {
        $enabledEmi = $this->getAttribute(self::EMI);

        return in_array(EmiType::CREDIT, $enabledEmi, true);
    }

    public function isDebitEmiEnabled()
    {
        $enabledEmi = $this->getAttribute(self::EMI);

        return in_array(EmiType::DEBIT, $enabledEmi, true);
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

    public function isCodEnabled()
    {
        return $this->getAttribute(self::COD);
    }

    public function isOfflineEnabled()
    {
        return $this->getAttribute(self::OFFLINE);
    }

    public function isCredEnabled()
    {
        return ((bool) $this->getApps()[AppMethod::CRED]);
    }

    public function isPayLaterEnabled()
    {
        return $this->getAttribute(self::PAYLATER);
    }

    public function isTransferEnabled()
    {
        return $this->merchant->isLinkedAccount();
    }

    public function isInAppEnabled() {
        $addonMethods = $this->getAddonMethods();

        if($addonMethods !== null && isset($addonMethods[self::UPI]) && isset($addonMethods[self::UPI][self::IN_APP]))
        {
            return $addonMethods[self::UPI][self::IN_APP] === 1;
        }
        return null;
    }

    public function isSodexoEnabled(): bool 
    {
        $addonMethods = $this->getAddonMethods();

        return !empty($addonMethods[self::CARD][self::SODEXO]);
    }


    public function isMethodEnabled($method)
    {
        $func = 'is' . studly_case($method) . 'Enabled';

        return $this->$func();
    }

    public function getCardSubtypes(): array
    {
        return $this->getAttribute(self::CARD_SUBTYPE);
    }

    public static function isTpvMethod(string $method): bool
    {
        return in_array($method, [self::UPI, self::NETBANKING], true);
    }

    // ----------------------- Getters --------------------------------------------
    protected function setCardSubTypeAttribute($subtypes)
    {
        $cardSubtypes = $this->getCardSubtypes();

        $subtypes = array_merge($cardSubtypes, $subtypes);

        $value = SubType::getHexValue($subtypes);

        $this->attributes[self::CARD_SUBTYPE] = $value;
    }

    protected function getCardSubTypeAttribute()
    {
        return $this->getEnabledSubTypes();
    }

    public function getEnabledSubTypes(): array
    {
        $subtypes = $this->attributes[self::CARD_SUBTYPE] ?? 0;

        return SubType::getEnabledCardSubTypes($subtypes);
    }

    public function getMerchantId()
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }

    public function getEnabledWallets()
    {
        $data = array();

        foreach ($this->wallets as $wallet)
        {
            $func = 'is' . ucfirst($wallet) . 'Enabled';

            if ($this->$func())
            {
                $data[$wallet] = true;
            }
        }

        return $data;
    }

    public function getAmex()
    {
        return ((bool) $this->getCardNetworks()[Network::AMEX]);
    }

    public function getCardNetworks(): array
    {
        return $this->getAttribute(self::CARD_NETWORKS);
    }

    public function getDebitEmiProviders(): array
    {
        return $this->getAttribute(self::DEBIT_EMI_PROVIDERS);
    }

    public function getCreditEmiProviders(): array
    {
        return $this->getAttribute(self::CREDIT_EMI_PROVIDERS);
    }

    public function getCardlessEmiProviders(): array
    {
        return $this->getAttribute(self::CARDLESS_EMI_PROVIDERS);
    }

    public function getPaylaterProviders(): array
    {
        return $this->getAttribute(self::PAYLATER_PROVIDERS);
    }

    public function getApps(): array
    {
        return $this->getAttribute(self::APPS);
    }

    public function getUpiType():array
    {
        return $this->getAttribute(self::UPI_TYPE);
    }

    public function getEnabledBanks()
    {
        return NetbankingProcessor::getEnabledBanks($this->getDisabledBanks());
    }

    public function getDisabledBanks()
    {
        return $this->getAttribute(self::DISABLED_BANKS);
    }

    public function getPaytm()
    {
        return $this->getAttribute(self::PAYTM);
    }

    public function getMobikwik()
    {
        return $this->getAttribute(self::MOBIKWIK);
    }

    public function getPayumoney()
    {
        return $this->getAttribute(self::PAYUMONEY);
    }
    public function getOlamoney()
    {
        return $this->getAttribute(self::OLAMONEY);
    }

    public function getAirtelmoney()
    {
        return $this->getAttribute(self::AIRTELMONEY);
    }

    public function getAmazonpay()
    {
        return $this->getAttribute(self::AMAZONPAY);
    }

    public function getFreecharge()
    {
        return $this->getAttribute(self::FREECHARGE);
    }

    public function getSbibuddy()
    {
        return $this->getAttribute(self::SBIBUDDY);
    }

    public function getItzcash()
    {
        $additional_wallets = $this->getAttribute(self::ADDITIONAL_WALLETS);
        return in_array(self::ITZCASH,$additional_wallets);
    }

    public function getOxigen()
    {
        $additional_wallets = $this->getAttribute(self::ADDITIONAL_WALLETS);
        return in_array(self::OXIGEN,$additional_wallets);
    }

    public function getAmexeasyclick()
    {
        $additional_wallets = $this->getAttribute(self::ADDITIONAL_WALLETS);
        return in_array(self::AMEXEASYCLICK,$additional_wallets);
    }

    public function getPaycash()
    {
        $additional_wallets = $this->getAttribute(self::ADDITIONAL_WALLETS);
        return in_array(self::PAYCASH,$additional_wallets);
    }

    public function getCitibankrewards()
    {
        $additional_wallets = $this->getAttribute(self::ADDITIONAL_WALLETS);
        return in_array(self::CITIBANKREWARDS,$additional_wallets);
    }

    public function getBajajPay(): bool
    {
        return in_array(self::BAJAJPAY, $this->getAttribute(self::ADDITIONAL_WALLETS));
    }

    public function getGrabPay(): bool
    {
        return in_array(self::GRABPAY, $this->getAttribute(self::ADDITIONAL_WALLETS));
    }

    public function getBoost(): bool
    {
        return in_array(self::BOOST, $this->getAttribute(self::ADDITIONAL_WALLETS));
    }

    public function getMCash(): bool
    {
        return in_array(self::MCASH, $this->getAttribute(self::ADDITIONAL_WALLETS));
    }

    public function getTouchNGo(): bool
    {
        return in_array(self::TOUCHNGO, $this->getAttribute(self::ADDITIONAL_WALLETS));
    }

    public function getPayzapp(): bool
    {
        return in_array(self::PAYZAPP, $this->getAttribute(self::ADDITIONAL_WALLETS));
    }

    public function getOpenwallet()
    {
        return $this->getAttribute(self::OPENWALLET);
    }

    public function getRazorpaywallet()
    {
        return $this->getAttribute(self::RAZORPAYWALLET);
    }

    public function getWallets()
    {
        $walletsStatus = array();

        foreach ($this->wallets as $wallet)
        {
            $walletsStatus[$wallet] = $this->getAttribute($wallet);
        }

        return $walletsStatus;
    }

    public function getSupportedBanks()
    {
        $banks = NetbankingProcessor::getSupportedBanks($this->merchant);

        $supportedBanks = array_diff($banks, $this->getDisabledBanks());

        return $supportedBanks;
    }

    public function getFPXSupportedBanks()
    {
        return FpxProcessor::getSupportedBanks();
    }

    public static function getAllMethodNames()
    {
        return self::$methods;
    }

    public static function getAllAdditionalWalletNames()
    {
        return self::$additional_wallet_names;
    }

    public static function getAllAddonMethodsNames()
    {
        return self::$addon_methods_names;
    }

    public static function getAddonMethodsList($method)
    {
        return self::$addon_methods_names[$method];
    }

    public function getIntlBankTransferEnabledModes()
    {
        $addon_methods = $this->getAttribute(self::ADDON_METHODS);
        if(isset($addon_methods[self::INTL_BANK_TRANSFER]) === true)
        {
            return $addon_methods[self::INTL_BANK_TRANSFER];
        }
        return [];
    }

    public function getIntlBankTransferEnabledForMerchant()
    {
        $intl_bank_transfer_modes = $this->getIntlBankTransferEnabledModes();
        $intlBankTransfer = [];

        foreach ($this->getAddonMethodsList(self::INTL_BANK_TRANSFER) as $mode)
        {
            $intlBankTransfer[strtolower(Gateway::MODE_TO_VA_CURRENCY_ACCOUNT_MAPPING_FOR_INTL_BANK_TRANSFER[$mode])] = isset($intl_bank_transfer_modes[$mode]) ? (int)$intl_bank_transfer_modes[$mode] : 0 ;
        }

        if($this->merchant->isFeatureEnabled(Feature\Constants::ENABLE_B2B_EXPORT))
        {
            $intlBankTransfer['va_usd'] = 1;
        }

        return $intlBankTransfer;
    }

    public function getInApp()
    {
        $addon_methods = $this->getAttribute(self::ADDON_METHODS);
        if(isset($addon_methods[self::UPI]) === true && isset($addon_methods[self::UPI][self::IN_APP]) === true)
        {
            return $addon_methods[self::UPI][self::IN_APP];
        }
        return 0;
    }

    public function getInAppAttribute()
    {
        return $this->getInApp();
    }

    public function getIntlBankTransferAttribute()
    {
        return $this->getIntlBankTransferEnabledModes();
    }

    public function getSodexo()
    {
        $addonMethods = $this->getAttribute(self::ADDON_METHODS);
        return $addonMethods[self::CARD][self::SODEXO] ?? false;
    }

    public function getSodexoAttribute()
    {
        return $this->getSodexo();
    }

    // ----------------------- Getters End -----------------------------------------

    // ----------------------- Setters --------------------------------------------

    public function setMethods(array $input = array())
    {
        $this->transformAdditionalWallets($input);
        $this->transformAddonMethods($input);
        $this->edit($input, 'setMethods');
    }

    /**
     * @param array $input
     *
     * Transforms all additional wallets passed in input to additional wallets array
     *
     * So if the input is [ "itzcash" : true ] , then "itzcash" will be added to "additional_wallets"     *
     *
     */
    protected function transformAdditionalWallets(array &$input)
    {
        $additional_wallet_names = Entity::getAllAdditionalWalletNames();
        // Get existing additional wallets
        $additional_wallets = $this->getAttribute(self::ADDITIONAL_WALLETS);
        foreach ($additional_wallet_names as $wallet_name) {
            if (isset($input[$wallet_name]) === true) {
                if (((bool)$input[$wallet_name] === true) && !in_array($wallet_name, $additional_wallets)) {
                    array_push($additional_wallets, $wallet_name);
                }
                if (((bool)$input[$wallet_name] === false) && in_array($wallet_name, $additional_wallets)) {
                    $index = array_search($wallet_name, $additional_wallets);
                    array_splice($additional_wallets,$index,1);
                }
                unset($input[$wallet_name]);
                // here we are not deleting "itzcash" field in input, and allowing it to pass through validation
            }
        }
        if (isset($input['additional_wallets']) === true) {
            // ignore "additional_wallets" attribute sent in request body
            unset($input['additional_wallets']);
        }
        $input['additional_wallets'] = $additional_wallets;
    }

    /**
     * @param array $input
     *
     * Transforms other payment methods in input to addon_methods array
     *
     * So if the input is [ "in_app" : true ] , then "in_app" will be added to "addon_methods"     *
     *
     */
    protected function transformAddonMethods(array &$input)
    {
        $all_addon_methods = Entity::getAllAddonMethodsNames();
        $addon_methods = $this->getAttribute(self::ADDON_METHODS);

        foreach ($all_addon_methods as $method => $sub_methods)
        {
            foreach ($sub_methods as $sub_method)
            {
                if (isset($input[$sub_method]) === true)
                {
                    if(isset($addon_methods[$method]) === false)
                    {
                        $addMethod = [];
                        $addMethod[$sub_method] = $input[$sub_method];
                        $addon_methods[$method] = $addMethod;
                    }
                    else
                    {
                        $addon_methods[$method][$sub_method] = $input[$sub_method];
                    }
                    unset($input[$sub_method]);
                }
                else if (isset($input[$method][$sub_method]) ===  true)
                {
                    if(isset($addon_methods[$method]) === false)
                    {
                        $addMethod = [];
                        $addMethod[$sub_method] = $input[$method][$sub_method];
                        $addon_methods[$method] = $addMethod;
                    }
                    else
                    {
                        $addon_methods[$method][$sub_method] = $input[$method][$sub_method];
                    }
                    unset($input[$method][$sub_method]);
                }
            }
        }

        $this->transformAffordabilityMethods($input, $addon_methods);

        $input["addon_methods"] = $addon_methods;
    }

    public function transformAffordabilityMethods(array &$input,array &$addon_methods)
    {
        foreach (self::$addon_affordability_methods as $affordabilityMethod)
        {
            $this->transformAffordabilityMethod($input, $affordabilityMethod, $addon_methods);
        }
    }

    public function setDisabledBanks(array $banks)
    {
        $this->setAttribute(self::DISABLED_BANKS, $banks);
    }

    public function setAmex($amex)
    {
        $this->setAttribute(self::AMEX, $amex);

        $this->setAmexCard($amex);
    }

    public function setAmexCard(int $value)
    {
        $this->setCardNetwork(Network::AMEX, $value);
    }

    public function setDinersCard(int $value)
    {
        $this->setCardNetwork(Network::DICL, $value);
    }

    public function setMasterCard(int $value)
    {
        $this->setCardNetwork(Network::MC, $value);
    }

    public function setMaestroCard(int $value)
    {
        $this->setCardNetwork(Network::MAES, $value);
    }

    public function setVisaCard(int $value)
    {
        $this->setCardNetwork(Network::VISA, $value);
    }

    public function setJcbCard(int $value)
    {
        $this->setCardNetwork(Network::JCB, $value);
    }

    public function setRupayCard(int $value)
    {
        $this->setCardNetwork(Network::RUPAY, $value);
    }

    public function setDiscCard(int $value)
    {
        $this->setCardNetwork(Network::DISC, $value);
    }

    public function setBajajCard(int $value)
    {
        $this->setCardNetwork(Network::BAJAJ, $value);
    }

    public function setUnpCard(int $value)
    {
        $this->setCardNetwork(Network::UNP, $value);
    }

    public function setCred($value)
    {
        $this->setAttribute(self::CRED, $value);
    }

    protected function setCardNetwork(string $network, int $value)
    {
        $cardNetworks = $this->getAttribute(self::CARD_NETWORKS);

        Network::checkNetworkValidity($network);

        $cardNetworks[$network] = $value;

        $this->setAttribute(self::CARD_NETWORKS, $cardNetworks);
    }

    public function setDebitEmiProvider(string $provider, int $value)
    {
        $providers = $this->getAttribute(self::DEBIT_EMI_PROVIDERS);

        DebitProvider::checkProviderValidity($provider);

        $providers[$provider] = $value;

        $this->setAttribute(self::DEBIT_EMI_PROVIDERS, $providers);
    }

    protected function setApps(string $app, int $value)
    {
        $apps = $this->getAttribute(self::APPS);

        AppMethod::checkApp($app);

        $apps[$app] = $value;

        $this->setAttribute(self::APPS, $app);
    }

    protected function setCardSubType(string $subtype, int $value)
    {
        $subTypes = $this->getAttribute(self::CARD_SUBTYPE);

        SubType::checkSubType($subtype);

        $subTypes[$subtype] = $value;

        $this->setAttribute(self::CARD_SUBTYPE, $subTypes);
    }

    public function setWallets($wallets)
    {
        foreach ($wallets as $wallet) {
            switch ($wallet) {
                case self::MOBIKWIK:
                case self::PAYTM:
                case self::PAYZAPP:
                case self::PAYUMONEY:
                    $this->setAttribute($wallet, true);
                    break;

                default:
                    break;
            }
        }
    }

    public function setPaytm($paytm)
    {
        $this->setAttribute(self::PAYTM, $paytm);
    }

    public function setMobikwik($mobikwik)
    {
        $this->setAttribute(self::MOBIKWIK, $mobikwik);
    }

    public function setPayzapp($value)
    {
        $this->setAttribute(self::PAYZAPP, $value);
    }

    public function setPayumoney($value)
    {
        $this->setAttribute(self::PAYUMONEY, $value);
    }

    public function setOlamoney($value)
    {
        $this->setAttribute(self::OLAMONEY, $value);
    }

    public function setAirtelmoney($value)
    {
        $this->setAttribute(self::AIRTELMONEY, $value);
    }

    public function setAmazonpay($value)
    {
        $this->setAttribute(self::AMAZONPAY, $value);
    }

    public function setFreecharge($value)
    {
        $this->setAttribute(self::FREECHARGE, $value);
    }

    public function setJiomoney($value)
    {
        $this->setAttribute(self::JIOMONEY, $value);
    }

    public function setPayLater($value)
    {
        $this->setAttribute(self::PAYLATER, $value);
    }

    public function setSbibuddy($value)
    {
        $this->setAttribute(self::SBIBUDDY, $value);
    }

    public function setOpenwallet($value)
    {
        $this->setAttribute(self::OPENWALLET, $value);
    }

    public function setRazorpaywallet($value)
    {
        $this->setAttribute(self::RAZORPAYWALLET, $value);
    }

    public function setCreditCard($card)
    {
        $this->setAttribute(self::CREDIT_CARD, $card);
    }

    public function setOffline(int $value)
    {
        $this->setAttribute(self::OFFLINE, $value);
    }

    public function setDebitCard($card)
    {
        $this->setAttribute(self::DEBIT_CARD, $card);
    }

    public function setPrepaidCard($card)
    {
        $this->setAttribute(self::PREPAID_CARD, $card);
    }

    public function setNetbanking($netbanking)
    {
        $this->setAttribute(self::NETBANKING, $netbanking);
    }

    public function setUpi(bool $upi)
    {
        $this->setAttribute(self::UPI, $upi);
    }

    public function setBankTransfer(bool $bankTransfer)
    {
        $this->setAttribute(self::BANK_TRANSFER, $bankTransfer);
    }

    public function setPhonepeSwitch(bool $phonepeSwitch)
    {
        $this->setAttribute(self::PHONEPE_SWITCH, $phonepeSwitch);
    }

    public function setEmi($emi)
    {
        assertTrue($this->isCardEnabled(), "Cannot enable emi without Card method");

        $this->setAttribute(self::EMI, $emi);
    }

    // ----------------------- Setters End ----------------------------------------

    // ----------------------- Accessors --------------------------------------------

    public function getEmiAttribute()
    {
        $emi = $this->attributes[self::EMI];

        return EmiType::getEnabledTypes($emi);
    }

    public function getEmiTypes()
    {
        $emi = $this->attributes[self::EMI];

        return EmiType::getEmiTypes($emi);
    }

    protected function getWalletAttribute()
    {
        $wallets = array();

        foreach ($this->wallets as $wallet)
        {
            $wallets[$wallet] = $this->isWalletEnabled($wallet);
        }

        return $wallets;
    }

    protected function getCardNetworksAttribute()
    {
        return $this->getEnabledCardNetworks();
    }

    protected function getDebitEmiProvidersAttribute()
    {
        return $this->getEnabledDebitEmiProviders();
    }

    protected function getCreditEmiProvidersAttribute()
    {
        return $this->getEnabledCreditEmiProviders();
    }

    protected function getCardlessEmiProvidersAttribute()
    {
        return $this->getEnabledCardlessEmiProviders();
    }

    protected function getPaylaterProvidersAttribute()
    {
        return $this->getEnabledPaylaterProviders();
    }

    protected function getAppsAttribute()
    {
        return $this->getEnabledApps();
    }

    protected function getUpiTypeAttribute()
    {
        return $this->getUpiTypes();
    }

    protected function getEnabledApps(): array
    {
        $apps = $this->attributes[self::APPS];

        return AppMethod::getEnabledApps($apps);
    }

    protected function getEnabledCardNetworks(): array
    {
        $networks = $this->attributes[self::CARD_NETWORKS];

        return Network::getEnabledCardNetworks($networks);
    }

    public function getEnabledDebitEmiProviders(): array
    {
        $networks = $this->attributes[self::DEBIT_EMI_PROVIDERS];

        return DebitProvider::getEnabledDebitEmiProviders($networks);
    }

    public function getConsolidatedEnabledDebitEmiProviders(): array
    {
        $networks = $this->attributes[self::DEBIT_EMI_PROVIDERS];

        $debitEmi = $this->isDebitEmiEnabled();

        return DebitProvider::getConsolidatedEnabledDebitEmiProviders($debitEmi, $networks);
    }

    public function getEnabledCreditEmiProviders(): array
    {
        $addon_methods = $this->getAddonMethods();

        $all_addon_methods = Entity::getAllAddonMethodsNames();

        return CreditEmiProvider::getEnabledProviders($all_addon_methods, $addon_methods);
    }

    public function getConsolidatedEnabledCreditEmiProviders(): array
    {
        $addon_methods = $this->getAddonMethods();

        $all_addon_methods = Entity::getAllAddonMethodsNames();

        $creditEmi = $this->isCreditEmiEnabled();

        return CreditEmiProvider::getConsolidatedEnabledCreditEmiProviders($all_addon_methods, $addon_methods, $creditEmi);
    }

    public function getEnabledCardlessEmiProviders(): array
    {
        $addon_methods = $this->getAddonMethods();

        $all_addon_methods = Entity::getAllAddonMethodsNames();

        return CardlessEmiProvider::getEnabledProviders($all_addon_methods, $addon_methods);
    }

    public function getEnabledPaylaterProviders(): array
    {
        $addon_methods = $this->getAddonMethods();

        $all_addon_methods = Entity::getAllAddonMethodsNames();

        return PaylaterProvider::getEnabledProviders($all_addon_methods, $addon_methods);
    }

    public function getUpiTypes()
    {
        $upi_type = $this->getUpiTypeFromRepo();

        $upi = $this->isUpiEnabled();

        return UpiType::getEnabledUpiTypes($upi, $upi_type);
    }

    public function getAddonMethods()
    {
        return $this->getAttribute(self::ADDON_METHODS);
    }


    protected function getDisabledBanksAttribute()
    {
        if (empty($this->attributes[self::DISABLED_BANKS]) === true)
        {
            return [];
        }
        return json_decode($this->attributes[self::DISABLED_BANKS], true);
    }

    protected function getAdditionalWalletsAttribute()
    {
        if (empty($this->attributes[self::ADDITIONAL_WALLETS]) === true)
        {
            return [];
        }
        return json_decode($this->attributes[self::ADDITIONAL_WALLETS], true);
    }

    protected function getItzcashAttribute()
    {
        return $this->getItzcash();
    }

    protected function getOxigenAttribute()
    {
        return $this->getOxigen();
    }

    protected function getAmexeasyclickAttribute()
    {
        return $this->getAmexeasyclick();
    }

    protected function getPaycashAttribute()
    {
        return $this->getPaycash();
    }

    protected function getCitibankrewardsAttribute()
    {
        return $this->getCitibankrewards();
    }

    protected function getBajajPayAttribute()
    {
        return $this->getBajajPay();
    }

    protected function getMCashAttribute()
    {
        return $this->getMCash();
    }

    protected function getGrabPayAttribute()
    {
        return $this->getGrabPay();
    }

    protected function getTouchNGoAttribute()
    {
        return $this->getTouchNGo();
    }

    protected function getBoostAttribute()
    {
        return $this->getBoost();
    }

    protected function getPayzappAttribute()
    {
        return $this->getPayzapp();
    }

    protected function getAddonMethodsAttribute()
    {
        if (empty($this->attributes[self::ADDON_METHODS]) === true)
        {
            return [];
        }
        return json_decode($this->attributes[self::ADDON_METHODS], true);
    }

    // ----------------------- Accessors End ----------------------------------------

    // ----------------------- Mutators --------------------------------------------

    protected function setCardNetworksAttribute($networks)
    {
        if (is_array($networks) === true)
        {
            $cardNetworks = $this->getCardNetworks();

            $networks = array_merge($cardNetworks, $networks);

            $value = Network::getHexValue($networks);

            $this->attributes[self::CARD_NETWORKS] = $value;
        }
        else
        {
            $this->attributes[self::CARD_NETWORKS] = $networks;
        }
    }

    protected function setDebitEmiProvidersAttribute($providers)
    {
        if (is_array($providers) === true)
        {
            $debitEmiProviders = $this->getDebitEmiProviders();

            $providers = array_merge($debitEmiProviders, $providers);

            $value = DebitProvider::getHexValue($providers);

            $this->attributes[self::DEBIT_EMI_PROVIDERS] = $value;
        }
        else
        {
            $this->attributes[self::DEBIT_EMI_PROVIDERS] = $providers;
        }
    }

    protected function transformAffordabilityMethod($input, $affordabilityProvider, array &$addon_methods)
    {
        $method = self::$aff_method_public_name_mapping[$affordabilityProvider];

        if(isset($input[$method]) === true)
        {
            foreach ($input[$method] as $provider => $enabled)
            {
                if($enabled === "1")
                {
                    $addon_methods[$affordabilityProvider][$provider] = 1;
                }
                else
                {
                    $addon_methods[$affordabilityProvider][$provider] = 0;
                }
            }
            unset($input[$method]);
        }
    }

    protected function setHdfcDebitEmiAttribute($value)
    {
        $this->setDebitEmiProvider(DebitProvider::HDFC, $value);
    }

    protected function setUpiAttribute($upi)
    {
        $this->attributes[self::UPI] = $upi;

        $this->attributes[self::UPI_TYPE] = UpiType::getUpiTypeFromUpi($upi);
    }

    protected function setUpiTypeAttribute($upiType)
    {
        if (is_array($upiType) === true) {

            $upiTypeAttribute = $this->attributes[self::UPI_TYPE];

            $value = UpiType::getUpdatedBinaryValue($upiTypeAttribute, $upiType);

            $this->attributes[self::UPI] = ($value > 0) ? 1 : 0;

            $this->attributes[self::UPI_TYPE] = $value;
        }
        else
        {
            $this->attributes[self::UPI_TYPE] = $upiType;

            $this->attributes[self::UPI] = ($upiType > 0) ? 1 : 0;
        }

    }

    protected function setAppsAttribute($apps)
    {
        if (is_array($apps) === true)
        {
            $existingApps = $this->getApps();

            $apps = array_merge($existingApps, $apps);

            $value = AppMethod::getHexValue($apps);

            $this->attributes[self::APPS] = $value;
        }
        else
        {
            $this->attributes[self::APPS] = $apps;
        }
    }

    protected function setEmiAttribute($type)
    {
        $hex = 0;

        if (isset($this->attributes[self::EMI]) === true)
        {
            $hex = $this->attributes[self::EMI];
        }

        $this->attributes[self::EMI] = EmiType::getHexValue($type, $hex);
    }

    protected function setDisabledBanksAttribute(array $banks)
    {
        $this->attributes[self::DISABLED_BANKS] = json_encode(array_values($banks));
    }

    protected function setAddonMethodsAttribute(array $addon_methods)
    {
        $this->attributes[self::ADDON_METHODS] = json_encode($addon_methods);
    }

    protected function setAdditionalWalletsAttribute(array $additional_wallets)
    {
        $this->attributes[self::ADDITIONAL_WALLETS] = json_encode(array_values($additional_wallets));
    }

    // TODO: Remove this once Amex column is removed
    protected function setAmexAttribute($value)
    {
        $this->attributes[self::AMEX] = $value;

        $this->setAmexCard($value);
    }

    // ----------------------- Mutators End --------------------------------------------

    /**
     * We are tagging the method entity cache key by
     * <entityName>_<merchantID>
     * @param string $entity
     * @param string $merchantId
     * @return string
     */
    public static function getCacheTags(string $entity, string $merchantId): string
    {
        $cacheTags = implode('_', [$entity, $merchantId]);

        return $cacheTags;
    }

    public function getCustomTextCacheKey()
    {
        return 'merchant_banks:subtext.'.$this->getMerchantId().'cache';
    }
}
