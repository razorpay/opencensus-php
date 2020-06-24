<?php

namespace RZP\Services\Mock\NbPlus;

use App;
use RZP\Exception;

use RZP\Services\NbPlus\Service as NbPlusService;

class Service extends NbPlusService
{
    protected function getDriver($method)
    {
        Payment\Method::validateMethod($method);

        switch ($method)
        {
            case 'netbanking':
                $class = new Netbanking();
                break;
            default:
                throw new Exception\LogicException('Should not have reached here');
        }

        return $class;
    }
}
