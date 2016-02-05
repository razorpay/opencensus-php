<?php

namespace Models\Merchant\Methods;

use EE\Exception;
use Models\Base;

class Entity extends Base\PublicEntity
{
    const MERCHANT_ID       = 'merchant_id';
    const CARD              = 'card';
    const AMEX              = 'amex';
    const BANKS             = 'banks';
    const PAYTM             = 'paytm';
    const MOBIKWIK          = 'mobikwik';
    const PAYZAPP           = 'payzapp';
    const EMI               = 'emi';

    const METHODS           = 'methods';

    protected $primaryKey = self::MERCHANT_ID;

    protected $table = \Constants\Table::METHODS;

    protected $entity = 'methods';

    protected static $sign = '';

    protected $fillable = array(
        self::MERCHANT_ID,
        self::CARD,
        self::AMEX,
        self::BANKS,
        self::PAYTM,
        self::PAYZAPP,
        self::MOBIKWIK,
        self::EMI,
    );

    protected $visible = array(
        self::MERCHANT_ID,
        self::CARD,
        self::AMEX,
        self::BANKS,
        self::PAYTM,
        self::PAYZAPP,
        self::MOBIKWIK,
        self::EMI,
    );

    protected $public = array(
        self::ENTITY,
        self::METHODS);

    protected $defaults = array(
        self::CARD      => false,
        self::AMEX      => false,
        self::PAYTM     => false,
        self::MOBIKWIK  => false,
        self::PAYZAPP   => false,
        self::BANKS     => [],
        self::EMI       => false,
    );

    protected $wallets = array(
        self::MOBIKWIK,
        self::PAYTM,
        self::PAYZAPP,
    );

    public function setMethods(array $input = array())
    {
        $this->edit($input, 'setMethods');
    }

    public function merchant()
    {
        return $this->belongsTo('Models\Merchant\Entity');
    }

    public function isCardEnabled()
    {
        return $this->getCardAttribute();
    }

    public function isWalletEnabled($wallet)
    {
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

    public function isMobikwikEnabled()
    {
        return $this->getMobikwikAttribute();
    }

    public function isEmiEnabled()
    {
        return $this->getEmiAttribute();
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

    public function setMobikwik($mobikwik)
    {
        $this->setAttribute(self::MOBIKWIK, $mobikwik);
    }

    public function getMobikwik()
    {
        return $this->getAttribute(self::MOBIKWIK);
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

    public function setPayzapp($value)
    {
        $this->setAttribute(self::PAYZAPP, $value);
    }

    public function setCard($card)
    {
        $this->setAttribute(self::CARD, $card);
    }

    public function setEmi($emi)
    {
        assert($this->isCardEnabled(), "Cannot enable emi without Card method");
        
        $this->setAttribute(self::EMI, $emi);
    }

    public function getAmexAttribute()
    {
        return (bool) $this->attributes[self::AMEX];
    }

    public function getPaytmAttribute()
    {
        return (bool) $this->attributes[self::PAYTM];
    }

    public function getCardAttribute()
    {
        return (bool) $this->attributes[self::CARD];
    }

    public function getMobikwikAttribute()
    {
        return (bool) $this->attributes[self::MOBIKWIK];
    }

    public function getPayzappAttribute()
    {
        return (bool) $this->attributes[self::PAYZAPP];
    }

    public function getBanksAttribute()
    {
        return json_decode($this->attributes[self::BANKS], true);
    }

    public function getEmiAttribute()
    {
        return (bool) $this->attributes[self::EMI];
    }

    public function setBanksAttribute(array $banks)
    {
        $this->attributes[self::BANKS] = json_encode($banks);
    }

    public function toArrayWithBankNames()
    {
        $banks = $this->getBanks();

        $names = \Models\Payment\Processor\Netbanking::getNames($banks);

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
}
