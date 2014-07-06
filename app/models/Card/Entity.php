<?php

namespace Models\Card;

class Entity extends UniqueIdDal
{
    const ID = Common::ID;

    const NAME = 'name';

    const EXPIRY_MONTH = 'expiry_month';

    const EXPIRY_YEAR = 'expiry_year';

    const LAST4 = 'last4';

    const NETWORK = 'network';

    const TYPE = 'type';

    const BANK = 'bank';

    const COUNTRY = 'country';

    const ADDRESS_LINE1 = 'address_line1';

    const ADDRESS_LINE2 = 'address_line2';

    const ADDRESS_CITY = 'address_city';

    const ADDRESS_STATE = 'address_state';

    const ADDRESS_ZIP = 'address_zip';

    const ADDRESS_COUNTRY = 'address_country';


    protected $table = \Constants\Table::CARD;

    protected $sign = 'card';

    protected $entity = 'card';

    protected $fillable = array(
        self::ID,
        self::NAME,
        self::EXPIRY_MONTH,
        self::EXPIRY_YEAR,
        self::LAST4,
        self::NETWORK,
        self::COUNTRY,
        self::TYPE,
        self::BANK,
        self::ADDRESS_LINE1,
        self::ADDRESS_LINE2,
        self::ADDRESS_STATE,
        self::ADDRESS_CITY,
        self::ADDRESS_ZIP,
        self::ADDRESS_COUNTRY,

        'cvv_check',
        'address_line1_check',
        'address_zip_check');

    protected $guarded = array(self::ID);

    protected $visible = array(
        self::ID,
        self::NAME,
        self::EXPIRY_MONTH,
        self::EXPIRY_YEAR,
        self::LAST4,
        self::NETWORK);

    public function getNetwork()
    {
        return $this->getAttribute(self::NETWORK);
    }
}
