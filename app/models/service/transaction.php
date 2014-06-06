<?php

namespace Models\Service;

use Models\Manager;

class Transaction extends Service
{
    public function fetch(array $input)
    {
        list($error,$options) = Manager\Transaction::createValidate($input, 'fetch')->getData();

        if (empty($error))
        {
            static::setApiCredentials();
            $response = static::$api->transaction->fetch($options);
            $data = Manager\Transaction::mapKeys($response);
        }
        else
        {
            $data = $error;
        }

        return $data;
    }
}