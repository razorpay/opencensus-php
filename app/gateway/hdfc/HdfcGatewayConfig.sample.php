<?php

namespace Gateway\HdfcGateway;

final class HdfcGatewayConfig
{
    public static function getCreds(){
    	//modify and add actual ID, password
        $id = 'HDFC_ID';
        $password = 'HDFC_PASSWORD';
        return array($id, $password);
    } 
}