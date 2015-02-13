<?php

use Http\ApiResponse;
use EE\Exception\RecoverableException;
use Models\Payment;
use Models\Card;

class AdminController extends BaseController
{
    protected $payment;

    public function __construct()
    {
        $this->payment = new Payment\Service();
    }

    public function getEntityMultiple($type)
    {
        $class = ucfirst($type).'Controller';
        $controller = new $class;
        $func = 'get'.ucfirst($type).'s';

        $input = Input::all();

        return $controller->$func($input);
    }

    public function getEntityById($type, $id)
    {
        ;
    }
}