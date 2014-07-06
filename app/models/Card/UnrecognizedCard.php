<?php

namespace Models\DAL;

use Constants\Field;

class UnrecognizedCard extends DAL
{
    protected $table = \Constants\Table::UNRECOGNIZED_CARD;

    protected $fillable = array(
        'iin');
}
