<?php

namespace Models\DAL;

use Constants\Field;

class Token extends UniqueIdDal
{

    protected $table = \Constants\Table::TOKEN;

    protected $sign = 'tok';

    protected $entity = 'token';

    protected $appends = array('object');

    protected $fillable = array(
        Field\Token::ID,
        Field\Token::CARD_ID,
        Field\Common::MERCHANT_ID,
        Field\Token::EXPIRED);

    public function getToken()
    {
        return $this->getAttribute(Field\Token::ID);
    }

    public function getCardId()
    {
        return $this->getAttribute(Field\Token::CARD);
    }

    public function setCard($card_id)
    {
        $this->setAttribute('card', $card_id);
    }

    public function setMerchantId($merchant_id)
    {
        $this->setAttribute(Field\Common::MERCHANT_ID, $merchant_id);
    }

    public function getMerchantId()
    {
        $this->getAttribute(Field\Common::MERCHANT_ID);
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
                        ->where(Field\Common::MERCHANT_ID, $merchant_id)
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
        return (bool)$this->getAttribute(Field\Token::EXPIRED);
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
