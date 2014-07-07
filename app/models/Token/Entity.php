<?php

namespace Models\Token;

use Models\Base;

class Entity extends Base\UniqueIdEntity
{
    const ID = 'id';

    const MERCHANT_ID = 'merchant_id';

    const EXPIRED = 'expired';

    const CARD_ID = 'card_id';

    const TOKEN_LEN = self::ID_LENGTH;

    protected $table = \Constants\Table::TOKEN;

    protected static $sign = 'tok';

    protected $entity = 'token';

    protected $fillable = array(
        self::ID,
        self::CARD_ID,
        self::MERCHANT_ID,
        self::EXPIRED);

    protected static $generators = array('token', 'expired', 'id');

    protected function generateToken()
    {
        $token = \Utility::generate_token(self::TOKEN_LEN);

        $this->setAttribute(self::ID, $token);
    }

    protected function generateExpired()
    {
        $this->setAttribute(self::EXPIRED, 0);
    }

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
        $this->setAttribute(self::CARD, $card_id);
    }

    public function setMerchantId($merchant_id)
    {
        $this->setAttribute(self::MERCHANT_ID, $merchant_id);
    }

    public function getMerchantId()
    {
        $this->getAttribute(self::MERCHANT_ID);
    }

    const WITH_CARD             = 0x1024;

    public function expired()
    {
        return (bool)$this->getAttribute(self::EXPIRED);
    }

    public function transactions()
    {
        return $this->hasOne(
            '\Models\Transaction\Entity');
    }

    public function card()
    {
        return $this->belongsTo(
            '\Models\Card\Entity');
    }

}
