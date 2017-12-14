<?php

namespace RZP\Models\Merchant\Methods;

use RZP\Models\Base;
use RZP\Models\Payment\Processor\Netbanking as NetbankingProcessor;

class Entity extends Base\PublicEntity
{
    const MERCHANT_ID       = 'merchant_id';
    const CARD              = 'card';
    const NETBANKING        = 'netbanking';
    const AMEX              = 'amex';
    const DISABLED_BANKS    = 'disabled_banks';
    const BANKS             = 'banks';
    const MOBIKWIK          = 'mobikwik';
    const OLAMONEY          = 'olamoney';
    const PAYTM             = 'paytm';
    const PAYZAPP           = 'payzapp';
    const PAYUMONEY         = 'payumoney';
    const AIRTELMONEY       = 'airtelmoney';
    const FREECHARGE        = 'freecharge';
    const JIOMONEY          = 'jiomoney';
    const SBIBUDDY          = 'sbibuddy';
    const OPENWALLET        = 'openwallet';
    const MPESA             = 'mpesa';
    const EMI               = 'emi';
    const DEBIT_CARD        = 'debit_card';
    const CREDIT_CARD       = 'credit_card';
    const UPI               = 'upi';
    const BANK_TRANSFER     = 'bank_transfer';
    const AEPS              = 'aeps';

    const METHODS           = 'methods';

    protected $primaryKey = self::MERCHANT_ID;

    protected $entity = 'methods';

    protected $revisionEnabled = true;

    protected $revisionCreationsEnabled = true;

    protected $fillable = [
        self::MERCHANT_ID,
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
        self::MPESA,
        self::EMI,
        self::UPI,
        self::AEPS,
        self::NETBANKING,
        self::DEBIT_CARD,
        self::CREDIT_CARD,
        self::BANK_TRANSFER,
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
        self::MPESA,
        self::EMI,
        self::UPI,
        self::AEPS,
        self::NETBANKING,
        self::DEBIT_CARD,
        self::CREDIT_CARD,
        self::BANK_TRANSFER,
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
        self::MPESA,
        self::EMI,
        self::UPI,
        self::AEPS,
        self::NETBANKING,
        self::DEBIT_CARD,
        self::CREDIT_CARD,
        self::ENTITY,
        self::BANK_TRANSFER,
    ];

    protected $defaults = array(
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
        self::MPESA          => false,
        self::DISABLED_BANKS => [],
        self::BANKS          => '[]',
        self::EMI            => false,
        self::UPI            => true,
        self::AEPS           => false,
        self::NETBANKING     => true,
        self::CREDIT_CARD    => true,
        self::DEBIT_CARD     => true,
        self::BANK_TRANSFER  => true,
    );

    protected $wallets = array(
        self::MOBIKWIK,
        self::PAYTM,
        self::PAYZAPP,
        self::PAYUMONEY,
        self::OLAMONEY,
        self::AIRTELMONEY,
        self::FREECHARGE,
        self::JIOMONEY,
        self::SBIBUDDY,
        self::OPENWALLET,
        self::MPESA,
    );

    protected static $methods = array(
        self::CARD,
        self::EMI,
        self::AMEX,
        self::UPI,
        self::BANK_TRANSFER,
        self::AEPS,
        self::NETBANKING,
        self::PAYTM,
        self::MOBIKWIK,
        self::PAYZAPP,
        self::PAYUMONEY,
        self::OLAMONEY,
        self::AIRTELMONEY,
        self::FREECHARGE,
        self::MPESA,
    );

    // Casts the attributes to native types
    protected $casts = [
        self::AMEX          => 'bool',
        self::PAYTM         => 'bool',
        self::CREDIT_CARD   => 'bool',
        self::DEBIT_CARD    => 'bool',
        self::NETBANKING    => 'bool',
        self::MOBIKWIK      => 'bool',
        self::OLAMONEY      => 'bool',
        self::PAYZAPP       => 'bool',
        self::PAYUMONEY     => 'bool',
        self::AIRTELMONEY   => 'bool',
        self::FREECHARGE    => 'bool',
        self::JIOMONEY      => 'bool',
        self::SBIBUDDY      => 'bool',
        self::OPENWALLET    => 'bool',
        self::MPESA         => 'bool',
        self::EMI           => 'bool',
        self::UPI           => 'bool',
        self::BANK_TRANSFER => 'bool',
        self::AEPS          => 'bool',
    ];

    public function setMethods(array $input = array())
    {
        $this->edit($input, 'setMethods');
    }

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function isCardEnabled()
    {
        return (($this->isDebitCardEnabled()) or
                ($this->isCreditCardEnabled()));
    }

    public function isDebitCardEnabled()
    {
        return $this->getAttribute(self::DEBIT_CARD);
    }

    public function isCreditCardEnabled()
    {
        return $this->getAttribute(self::CREDIT_CARD);
    }

    public function isNetbankingEnabled()
    {
        return $this->getAttribute(self::NETBANKING);
    }

    public function isUpiEnabled()
    {
        return $this->getAttribute(self::UPI);
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

    public function isAmexEnabled()
    {
        return $this->getAttribute(self::AMEX);
    }

    public function isPaytmEnabled()
    {
        return $this->getAttribute(self::PAYTM);
    }

    public function isPayzappEnabled()
    {
        return $this->getAttribute(self::PAYZAPP);
    }

    public function isOlamoneyEnabled()
    {
        return $this->getAttribute(self::OLAMONEY);
    }

    public function isAirtelmoneyEnabled()
    {
        return $this->getAttribute(self::AIRTELMONEY);
    }

    public function isMpesaEnabled()
    {
        return $this->getAttribute(self::MPESA);
    }

    public function isPayumoneyEnabled()
    {
        return $this->getAttribute(self::PAYUMONEY);
    }

    public function isFreechargeEnabled()
    {
        return $this->getAttribute(self::FREECHARGE);
    }

    public function isMobikwikEnabled()
    {
        //
        // Mobikwik MID was unexpectedly disabled, this change is
        // needed for merchants using razorpay.js and S2S.
        //
        // https://github.com/razorpay/incidents/issues/157
        //

        return false;
        // return $this->getAttribute(self::MOBIKWIK);
    }

    public function isOpenwalletEnabled()
    {
        return $this->getAttribute(self::OPENWALLET);
    }

    public function isJiomoneyEnabled()
    {
        return $this->getAttribute(self::JIOMONEY);
    }

    public function isSbibuddyEnabled()
    {
        return $this->getAttribute(self::SBIBUDDY);
    }

    public function isEmiEnabled()
    {
        return $this->getAttribute(self::EMI);
    }

    public function isTransferEnabled()
    {
        return $this->merchant->isLinkedAccount();
    }

    public function isMethodEnabled($method)
    {
        $func = 'is' . studly_case($method) . 'Enabled';

        return $this->$func();
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
        return $this->getAttribute(self::AMEX);
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

    public function getFreecharge()
    {
        return $this->getAttribute(self::FREECHARGE);
    }

    public function getSbibuddy()
    {
        return $this->getAttribute(self::SBIBUDDY);
    }

    public function getOpenwallet()
    {
        return $this->getAttribute(self::OPENWALLET);
    }

    public function getEmi()
    {
        return $this->getAttribute(self::EMI);
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

    public function getWallets()
    {
        $walletsStatus = array();

        foreach ($this->wallets as $wallet)
        {
            $walletsStatus[$wallet] = $this->getAttribute($wallet);
        }

        return $walletsStatus;
    }

    public function setDisabledBanks(array $banks)
    {
        $this->setAttribute(self::DISABLED_BANKS, $banks);
    }

    public function setAmex($amex)
    {
        $this->setAttribute(self::AMEX, $amex);
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

    public function setFreecharge($value)
    {
        $this->setAttribute(self::FREECHARGE, $value);
    }

    public function setSbibuddy($value)
    {
        $this->setAttribute(self::SBIBUDDY, $value);
    }

    public function setOpenwallet($value)
    {
        $this->setAttribute(self::OPENWALLET, $value);
    }

    public function setCreditCard($card)
    {
        $this->setAttribute(self::CREDIT_CARD, $card);
    }

    public function setDebitCard($card)
    {
        $this->setAttribute(self::DEBIT_CARD, $card);
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

    public function setEmi($emi)
    {
        assertTrue($this->isCardEnabled(), "Cannot enable emi without Card method");

        $this->setAttribute(self::EMI, $emi);
    }

    protected function getDisabledBanksAttribute()
    {
        if (empty($this->attributes[self::DISABLED_BANKS]) === true)
        {
            return [];
        }
        return json_decode($this->attributes[self::DISABLED_BANKS], true);
    }

    protected function setDisabledBanksAttribute(array $banks)
    {
        $this->attributes[self::DISABLED_BANKS] = json_encode(array_values($banks));
    }

    public function getWalletAttribute()
    {
        $wallets = array();

        foreach ($this->wallets as $wallet)
        {
            $wallets[$wallet] = $this->isWalletEnabled($wallet);
        }

        return $wallets;
    }

    public function getSupportedBanks()
    {
        $banks = NetbankingProcessor::getSupportedBanks($this->merchant);

        $supportedBanks = array_diff($banks, $this->getDisabledBanks());

        return $supportedBanks;
    }

    public static function getAllMethodNames()
    {
        return self::$methods;
    }
}
