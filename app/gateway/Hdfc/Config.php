<?php

namespace Gateway\Hdfc;

final class Config
{
    const TIMEOUT = 5;

    public static function getCreds(){
        //modify and add actual ID, password
        $id = $_ENV['HDFC_ID'];
        $password = $_ENV['HDFC_PASSWORD'];
        return array($id, $password);
    }
}