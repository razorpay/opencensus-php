<?php

namespace Models\Manager;

use Utility;

class Merchant extends EntityManager
{
    protected static $createRules = array(
        'id'    =>  'required|numeric'
    );

}
