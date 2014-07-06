<?php

namespace Models\Token;

class Token extends UniqueIdDal
{
    const ID = Common::ID;

    const MERCHANT_ID = Common::MERCHANT_ID;

    const EXPIRED = 'expired';

    const CARD_ID = 'card_id';

    protected $table = \Constants\Table::TOKEN;

    protected $sign = 'tok';

    protected $entity = 'token';

    protected $appends = array('object');

    protected $fillable = array(
        self::ID,
        self::CARD_ID,
        self::MERCHANT_ID,
        self::EXPIRED);

    public function getToken()
    {
        return $this->getAttribute(self::ID);
    }

    public function getCardId()
    {
        return $this->getAttribute(self::CARD);
    }

    public function setCard($card_id)
    {
        $this->setAttribute('card', $card_id);
    }

    public function setMerchantId($merchant_id)
    {
        $this->setAttribute(self::MERCHANT_ID, $merchant_id);
    }

    public function getMerchantId()
    {
        $this->getAttribute(self::MERCHANT_ID);
    }

    public function getObjectAttribute()
    {
        return 'token';
    }

    const WITH_CARD             = 0x1024;

    public function toArrayEx($flag = 0x0)
    {
        $array = parent::toArray($flag);

        if ($flag & self::WITH_CARD)
        {
            $card_do_flag = 0x0;

            $card_do_flag |= Card::NO_CHECK_FIELDS;

            $card = $this->card_do->toArray($card_do_flag);

            $array['card'] = $card;
        }

        return $array;
    }

    public static function findByTokenAndMerchantId($token, $merchant_id)
    {
        $token;

        try
        {
           $token = self::where('token', $token)
                        ->where(self::MERCHANT_ID, $merchant_id)
                        ->first();

        }
        catch(Exception $e)
        {
            $token = false;
        }

        return $token;
    }

    public function expired()
    {
        return (bool)$this->getAttribute(self::EXPIRED);
    }

    public function transactions()
    {
        return $this->hasOne(
            __NAMESPACE__.'\Transaction');
    }

    public function card()
    {
        return $this->belongsTo(
            __NAMESPACE__.'\Card');
    }

}
