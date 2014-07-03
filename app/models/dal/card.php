<?php

namespace Models\DAL;

use Constants\Field;

class Card extends UniqueIdDal
{
    protected $table = \Constants\Table::CARD;

    protected $sign = 'card';

    protected $entity = 'card';

    protected $fillable = array(
        Field\Card::NAME,

        Field\Card::EXPIRY_MONTH,
        Field\Card::EXPIRY_YEAR,

        Field\Card::LAST4,
        Field\Card::NETWORK,
        Field\Card::COUNTRY,
        Field\Card::TYPE,
        Field\Card::BANK,

        Field\Card::ADDRESS_LINE1,
        Field\Card::ADDRESS_LINE2,
        Field\Card::ADDRESS_STATE,
        Field\Card::ADDRESS_CITY,
        Field\Card::ADDRESS_ZIP,
        Field\Card::ADDRESS_COUNTRY,

        'cvv_check',
        'address_line1_check',
        'address_zip_check');

    protected $guarded = array(Field\Card::ID);

    protected $visible = array(
        Field\Card::ID,
        Field\Card::NAME,
        Field\Card::EXPIRY_MONTH,
        Field\Card::EXPIRY_YEAR,
        Field\Card::LAST4,
        Field\Card::NETWORK);
}
