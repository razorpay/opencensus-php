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
    const EMI               = 'emi';
    const DEBIT_CARD        = 'debit_card';
    const CREDIT_CARD       = 'credit_card';

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
        self::MOBIKWIK,
        self::OLAMONEY,
        self::EMI,
        self::NETBANKING,
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
        self::MOBIKWIK,
        self::OLAMONEY,
        self::EMI,
        self::NETBANKING,
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
        self::BANKS         => [],
        self::EMI           => false,
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
    );

    // Casts the attributes to native types
    protected $casts = [
        self::AMEX        => 'bool',
        self::PAYTM       => 'bool',
        self::CARD        => 'bool',
        self::CREDIT_CARD => 'bool',
        self::DEBIT_CARD  => 'bool',
        self::NETBANKING  => 'bool',
        self::MOBIKWIK    => 'bool',
        self::OLAMONEY    => 'bool',
        self::PAYZAPP     => 'bool',
        self::PAYUMONEY   => 'bool',
        self::AIRTELMONEY => 'bool',
        self::EMI         => 'bool',
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
        return $this->getAttribute(self::CARD);
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

    public function isMobikwikEnabled()
    {
        return $this->getAttribute(self::MOBIKWIK);
    }

    public function isEmiEnabled()
    {
        return $this->getAttribute(self::EMI);
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
        $this->setAttribute(self::Airtelmoney, $value);
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
        assert($this->isCardEnabled(), "Cannot enable emi without Card method");

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
            self::EMI,
            self::AMEX,
            self::NETBANKING,
            self::PAYTM,
            self::MOBIKWIK,
            self::PAYZAPP,
            self::PAYUMONEY,
            self::OLAMONEY,
            self::AIRTELMONEY,
        );
    }
}
