<?php

namespace Models\DAL;

class CardDetail extends DAL
{
    protected $table = 'iins';

    protected $primaryKey = 'iin';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = array('*');
}