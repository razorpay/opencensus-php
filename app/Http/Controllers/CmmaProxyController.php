<?php

namespace RZP\Http\Controllers;

use RZP\Models\Admin\Permission\Name;

class CmmaProxyController extends BaseProxyController
{
    //TODO : change the url @shubham
    const GET_PROCESS           = 'GetUserList';

    const ROUTES_URL_MAP    = [
        //TODO : change the url @shubham
        self::GET_PROCESS => "/twirp\/rzp.example.user.v1.UserAPI\/List/"
    ];

    const ADMIN_ROUTES   = [
        self::GET_PROCESS
    ];

    const ADMIN_ROUTES_VS_PERMISSION   = [
        self::GET_PROCESS   => Name::CMMA_PROCESS_VIEW,
    ];

    public function __construct()
    {
        parent::__construct("cmma");

        $this->registerRoutesMap(self::ROUTES_URL_MAP);
        $this->registerAdminRoutes(self::ADMIN_ROUTES,self::ADMIN_ROUTES_VS_PERMISSION);

        $this->setDefaultTimeout(30);

    }

    protected function getAuthorizationHeader()
    {
        return'Basic '. base64_encode($this->serviceConfig['user'] . ':' . $this->serviceConfig['password']);
    }
}