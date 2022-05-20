<?php

namespace RZP\Http\Controllers;

use RZP\Models\Admin\Permission\Name;

class CmmaProxyController extends BaseProxyController
{
    //TODO : change the url @shubham
    const GET_PROCESS           = 'GetUserList';
    const GET_USER_TASK_QUERY   = 'GetUserTaskQuery';
    const GET_USER_TASK_BY_ID   = 'GetUserTaskById';

    const ROUTES_URL_MAP    = [
        //TODO : change the url @shubham
        self::GET_PROCESS => "/twirp\/rzp.example.user.v1.UserAPI\/List/",
        self::GET_USER_TASK_QUERY => '/twirp\/rzp.cmma.userTask.v1.UserTaskService\/GetUserTaskQuery/',
        self::GET_USER_TASK_BY_ID => '/twirp\/rzp.cmma.userTask.v1.UserTaskService\/GetUserTaskById/',
    ];

    const ADMIN_ROUTES   = [
        self::GET_PROCESS,
        self::GET_USER_TASK_QUERY,
        self::GET_USER_TASK_BY_ID
    ];

    const ADMIN_ROUTES_VS_PERMISSION   = [
        self::GET_PROCESS   => Name::CMMA_PROCESS_VIEW,
        self::GET_USER_TASK_QUERY => Name::CMMA_USER_TASK_VIEW,
        self::GET_USER_TASK_BY_ID => Name::CMMA_USER_TASK_VIEW
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
