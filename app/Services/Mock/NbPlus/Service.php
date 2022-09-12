<?php

namespace RZP\Services\Mock\NbPlus;

use App;
use RZP\Exception;
use RZP\Models\Payment;

use RZP\Services\NbPlus\Paylater;
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
            case Payment\Method::WALLET;
                $class = new Wallet();
                break;
            case Payment\Method::PAYLATER:
                $class = new Paylater();
                break;
            case Payment\Method::EMANDATE;
                $class = new Emandate();
                break;
            case Payment\Method::CARDLESS_EMI:
                $class = new CardlessEmi();
                break;
            case Payment\Method::APP;
                $class = new AppMethod();
                break;
            case Payment\Method::WALLET:
                $class = new Wallet();
                break;
            default:
                throw new Exception\LogicException('Should not have reached here');
        }

        return $class;
    }
}
