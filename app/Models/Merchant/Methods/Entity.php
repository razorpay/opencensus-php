<?php

namespace RZP\Models\Merchant\Methods;

use RZP\Models\Base;
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
    const EMI               = 'emi';
    const DEBIT_CARD        = 'debit_card';
    const CREDIT_CARD       = 'credit_card';
    const UPI               = 'upi';

    const METHODS           = 'methods';

    protected $primaryKey = self::MERCHANT_ID;

    protected $table = \RZP\Constants\Table::METHODS;

    protected $entity = 'methods';

    protected static $sign = '';

    protected $fillable = array(
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
        self::EMI,
        self::UPI,
        self::NETBANKING,
        self::DEBIT_CARD,
        self::CREDIT_CARD,
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
        self::EMI,
        self::UPI,
        self::NETBANKING,
        self::DEBIT_CARD,
        self::CREDIT_CARD,
    );

    protected $public = array(
        self::ENTITY,
        self::METHODS);

    protected $defaults = array(
        self::CARD          => false,
        self::AMEX          => false,
        self::PAYTM         => false,
        self::MOBIKWIK      => false,
        self::PAYZAPP       => false,
        self::PAYUMONEY     => false,
        self::AIRTELMONEY   => false,
        self::OLAMONEY      => false,
        self::FREECHARGE    => false,
        self::BANKS         => [],
        self::EMI           => false,
        self::UPI           => false,
        self::NETBANKING    => true,
        self::CREDIT_CARD   => true,
        self::DEBIT_CARD    => true,
    );

    protected $wallets = array(
        self::MOBIKWIK,
        self::PAYTM,
        self::PAYZAPP,
        self::PAYUMONEY,
        self::OLAMONEY,
        self::AIRTELMONEY,
        self::FREECHARGE,
    );

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
        return $this->getCardAttribute();
    }

    public function isDebitCardEnabled()
    {
        return $this->getDebitCardAttribute();
    }

    public function isCreditCardEnabled()
    {
        return $this->getCreditCardAttribute();
    }

    public function isNetbankingEnabled()
    {
        return $this->getNetbankingAttribute();
    }

    public function isUpiEnabled()
    {
        return $this->getUpiAttribute();
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
        return $this->getAmexAttribute();
    }

    public function isPaytmEnabled()
    {
        return $this->getPaytmAttribute();
    }

    public function isPayzappEnabled()
    {
        return $this->getPayzappAttribute();
    }

    public function isOlamoneyEnabled()
    {
        return $this->getOlamoneyAttribute();
    }

    public function isAirtelmoneyEnabled()
    {
        return $this->getAirtelmoneyAttribute();
    }

    public function isPayumoneyEnabled()
    {
        return $this->getPayumoneyAttribute();
    }

    public function isFreechargeEnabled()
    {
        return $this->getFreechargeAttribute();
    }

    public function isMobikwikEnabled()
    {
        return $this->getMobikwikAttribute();
    }

    public function isEmiEnabled()
    {
        return $this->getEmiAttribute();
    }

    public function isMethodEnabled($method)
    {
        $func = 'is'.ucfirst($method).'Enabled';

        return $this->$func();
    }

    public function getEnabledWallets()
    {
        $data = array();

        foreach ($this->wallets as $wallet)
        {
            $func = 'is'.ucfirst($wallet).'Enabled';

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

    public function getEMi()
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
                case self::FREECHARGE:
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
        $this->setAttribute(self::Airtelmoney, $value);
    }

    public function setFreecharge($value)
    {
        $this->setAttribute(self::FREECHARGE, $value);
    }

    public function setCard($card)
    {
        $this->setAttribute(self::CARD, $card);
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

    protected function getAmexAttribute()
    {
        return (bool) $this->attributes[self::AMEX];
    }

    protected function getPaytmAttribute()
    {
        return (bool) $this->attributes[self::PAYTM];
    }

    protected function getCardAttribute()
    {
        return (bool) $this->attributes[self::CARD];
    }

    protected function getCreditCardAttribute()
    {
        return (bool) $this->attributes[self::CREDIT_CARD];
    }

    protected function getDebitCardAttribute()
    {
        return (bool) $this->attributes[self::DEBIT_CARD];
    }

    protected function getNetbankingAttribute()
    {
        return (bool) $this->attributes[self::NETBANKING];
    }

    protected function getMobikwikAttribute()
    {
        return (bool) $this->attributes[self::MOBIKWIK];
    }

    protected function getOlamoneyAttribute()
    {
        return (bool) $this->attributes[self::OLAMONEY];
    }

    protected function getAirtelmoneyAttribute()
    {
        return (bool) $this->attributes[self::AIRTELMONEY];
    }

    protected function getPayzappAttribute()
    {
        return (bool) $this->attributes[self::PAYZAPP];
    }

    protected function getPayumoneyAttribute()
    {
        return (bool) $this->attributes[self::PAYUMONEY];
    }

    public function getUpiAttribute()
    {
        return (bool) $this->attributes[self::UPI];
    }

    protected function getFreechargeAttribute()
    {
        return (bool) $this->attributes[self::FREECHARGE];
    }

    protected function getBanksAttribute()
    {
        return json_decode($this->attributes[self::BANKS], true);
    }

    protected function getEmiAttribute()
    {
        return (bool) $this->attributes[self::EMI];
    }

    protected function setBanksAttribute(array $banks)
    {
        $this->attributes[self::BANKS] = json_encode($banks);
    }

    public function toArrayWithBankNames()
    {
        $banks = $this->getBanks();

        $names = \RZP\Models\Payment\Processor\Netbanking::getNames($banks);

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
        return array(
            self::CARD,
            self::NETBANKING,
            self::AMEX,
            self::PAYTM,
            self::MOBIKWIK,
            self::PAYZAPP,
            self::PAYUMONEY,
            self::OLAMONEY,
            self::AIRTELMONEY,
            self::EMI,
            self::UPI,
            self::FREECHARGE,
        );
    }
}
