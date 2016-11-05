<?php

namespace App\Lead;

use App\Base;

class Entity extends Base\Entity
{
    protected $table = 'leads';

    protected $fillable = [
        'email',
        'registered',
        'registered_at'
    ];
}
