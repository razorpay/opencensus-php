<?php

namespace Models\Card;

class Unrecognized extends \Eloquent
{
    protected $table = \Constants\Table::UNRECOGNIZED_CARD;

    protected $fillable = array(
        'iin');
}
