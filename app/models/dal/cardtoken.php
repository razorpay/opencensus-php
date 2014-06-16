<?php

namespace Models\DAL;

use \Constants\Field;

class CardToken extends DAL {

    protected $table = \Constants\Table::TOKEN;

    protected $appends = array('object');

    protected $fillable = array(
        'token',
        'card_id',
        Field\Common::MERCHANT_ID,
        Field\Token::EXPIRED
        );

    protected $guarded = array('id');

    public function getId()
    {
        return $this->getAttribute('id');
    }

    public function getToken()
    {
        return $this->getAttribute('token');
    }

    public function getCardId()
    {
        return $this->getAttribute('card_id');
    }

    public function setCardId($card_id)
    {
        $this->setAttribute('card_id', $card_id);
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
        return $this->hasMany('Transaction');
    }

    public function card()
    {
        return $this->belongsTo(__NAMESPACE__.'\Card', 'card_id', 'id');
    }

}
