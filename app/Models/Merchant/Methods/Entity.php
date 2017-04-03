<?php

namespace RZP\Models\Merchant\Methods;

use RZP\Models\Base;
use RZP\Models\Feature;
use RZP\Exception;

class Entity extends Base\PublicEntity
{
    const MERCHANT_ID       = 'merchant_id';
    const CARD              = 'card';
    const NETBANKING        = 'netbanking';
    const AMEX              = 'amex';
    const BANKS             = 'banks';
    const MOBIKWIK          = 'mobikwik';
    const OLAMONEY          = 'olamoney';
    const PAYTM             = 'paytm';
    const PAYZAPP           = 'payzapp';
    const PAYUMONEY         = 'payumoney';
    const AIRTELMONEY       = 'airtelmoney';
    const FREECHARGE        = 'freecharge';
    const JIOMONEY          = 'jiomoney';
    const OPENWALLET        = 'openwallet';
    const EMI               = 'emi';
    const DEBIT_CARD        = 'debit_card';
    const CREDIT_CARD       = 'credit_card';
    const UPI               = 'upi';
    const BANK_TRANSFER     = 'bank_transfer';

    const METHODS           = 'methods';

    protected $primaryKey = self::MERCHANT_ID;

    protected $entity = 'methods';

    protected $revisionEnabled = true;

    protected $revisionCreationsEnabled = true;

    protected $fillable = array(
        self::MERCHANT_ID,
        self::AMEX,
        self::BANKS,
        self::PAYTM,
        self::PAYZAPP,
        self::PAYUMONEY,
        self::AIRTELMONEY,
        self::FREECHARGE,
        self::MOBIKWIK,
        self::OLAMONEY,
        self::JIOMONEY,
        self::OPENWALLET,
        self::EMI,
        self::UPI,
        self::NETBANKING,
        self::DEBIT_CARD,
        self::CREDIT_CARD,
        self::BANK_TRANSFER,
    );

    protected $visible = array(
        self::MERCHANT_ID,
        self::CARD,
        self::AMEX,
        self::BANKS,
        self::PAYTM,
        self::PAYZAPP,
        self::PAYUMONEY,
        self::AIRTELMONEY,
        self::FREECHARGE,
        self::MOBIKWIK,
        self::OLAMONEY,
        self::JIOMONEY,
        self::OPENWALLET,
        self::EMI,
        self::UPI,
        self::NETBANKING,
        self::DEBIT_CARD,
        self::CREDIT_CARD,
        self::BANK_TRANSFER,
    );

    protected $public = array(
        self::ENTITY,
        self::METHODS);

    protected $defaults = array(
        self::AMEX          => false,
        self::PAYTM         => false,
        self::MOBIKWIK      => false,
        self::PAYZAPP       => false,
        self::PAYUMONEY     => false,
        self::AIRTELMONEY   => false,
        self::OLAMONEY      => false,
        self::FREECHARGE    => false,
        self::JIOMONEY      => false,
        self::OPENWALLET    => false,
        self::BANKS         => [],
        self::EMI           => false,
        self::UPI           => true,
        self::NETBANKING    => true,
        self::CREDIT_CARD   => true,
        self::DEBIT_CARD    => true,
        self::BANK_TRANSFER => false,
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
        self::OPENWALLET,
    );

    protected static $methods = array(
        self::CARD,
        self::EMI,
        self::AMEX,
        self::UPI,
        self::BANK_TRANSFER,
        self::NETBANKING,
        self::PAYTM,
        self::MOBIKWIK,
        self::PAYZAPP,
        self::PAYUMONEY,
        self::OLAMONEY,
        self::AIRTELMONEY,
        self::FREECHARGE,
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
        self::OPENWALLET    => 'bool',
        self::EMI           => 'bool',
        self::UPI           => 'bool',
        self::BANK_TRANSFER => 'bool',
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
        return $this->getAttribute(self::MOBIKWIK);
    }

    public function isOpenwalletEnabled()
    {
        return $this->getAttribute(self::OPENWALLET);
    }

    public function isJiomoneyEnabled()
    {
        return $this->getAttribute(self::JIOMONEY);
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

    public function getBanks()
    {
        return $this->getAttribute(self::BANKS);
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

    public function setBanks(array $banks)
    {
        $this->setAttribute(self::BANKS, $banks);
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

    public function setEmi($emi)
    {
        assertTrue($this->isCardEnabled(), "Cannot enable emi without Card method");

        $this->setAttribute(self::EMI, $emi);
    }

    protected function getBanksAttribute()
    {
        return json_decode($this->attributes[self::BANKS], true);
    }

    protected function setBanksAttribute(array $banks)
    {
        $this->attributes[self::BANKS] = json_encode($banks);
    }

    public function toArrayWithBankNames()
    {
        $banks = $this->getBanks();

        $names = \RZP\Models\Payment\Processor\Netbanking::getNames($banks);

        // Unsetting AIRP for now
        unset($names['AIRP']);

        return $names;
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

    public static function getAllMethodNames()
    {
        return self::$methods;
    }
}
