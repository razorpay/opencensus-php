<?php

namespace Gateway\HdfcGateway;

final class HdfcGatewayConfig
{
    public static function getCreds(){
        $id = getenv('HDFC_ID');
        $password = getenv('HDFC_PASSWORD');
        return array($id, $password);
    } 
}