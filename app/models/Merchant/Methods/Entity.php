<?php

namespace Models\Merchant\Methods;

use EE\Exception;
use Models\Base;

class Entity extends Base\PublicEntity
{
    const MERCHANT_ID       = 'merchant_id';
    const CARD              = 'card';
    const BANKS             = 'banks';
    const PAYTM             = 'paytm';
    const MOBIKWIK          = 'mobikwik';
    const PAYZAPP           = 'payzapp';

    protected $primaryKey = self::MERCHANT_ID;

    protected $table = \Constants\Table::METHODS;

    protected $entity = 'methods';

    protected static $sign = '';

    protected $fillable = array(
        self::MERCHANT_ID,
        self::CARD,
        self::BANKS,
        self::PAYTM,
        self::MOBIKWIK);

    protected $visible = array(
        self::MERCHANT_ID,
        self::CARD,
        self::BANKS,
        self::PAYTM,
        self::MOBIKWIK);

    protected $public = array(
        self::ENTITY,
        'methods');

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

    public function isPaytmEnabled()
    {
        return $this->getPaytmAttribute();
    }

    public function isPayzappEnabled()
    {
        return true;
    }

    public function isMobikwikEnabled()
    {
        return $this->getMobikwikAttribute();
    }

    public function getBanks()
    {
        return $this->getAttribute(self::BANKS);
    }

    public function setBanks(array $banks)
    {
        $this->setAttribute(self::BANKS, $banks);
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
//        return true;
        return $this->getAttribute(self::MOBIKWIK);
    }

    public function setPaytm($paytm)
    {
        $this->setAttribute(self::PAYTM, $paytm);
    }

    public function getPaytmAttribute()
    {
        return (bool) $this->attributes[self::PAYTM];
    }

    public function setCard($card)
    {
        $this->setAttribute(self::CARD, $card);
    }

    public function getCardAttribute()
    {
        return (bool) $this->attributes[self::CARD];
    }

    public function getBanksAttribute()
    {
        return json_decode($this->attributes[self::BANKS], true);
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
        return array('paytm' => $this->getPaytmAttribute());
    }

    public function getMobikwikAttribute()
    {
        return (bool) $this->attributes[self::MOBIKWIK];
    }
}
