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

    protected static $generators = array('expired', 'id');

    protected function generateToken()
    {
        $token = \Utility::generate_token(self::TOKEN_LEN);

        $this->setAttribute(self::ID, $token);
    }

    protected function generateExpired()
    {
        $this->setAttribute(self::EXPIRED, 0);
    }

    public function getCardId()
    {
        return $this->getAttribute(self::CARD);
    }

    public function expired()
    {
        return (bool)$this->getAttribute(self::EXPIRED);
    }

    public function transaction()
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
