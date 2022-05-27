<?php

namespace RZP\Http\Controllers;

use RZP\Models\Admin\Permission\Name;

class CmmaProxyController extends BaseProxyController
{
    //TODO : change the url @shubham
    const CRON_UPDATE_PROCESS_ASSIGNED_TO = 'CronUpdateProcessAssignedTo';
    const GET_PROCESS           = 'GetUserList';
    const HANDLE_CALLBACK       = 'HandleCallback';
    const GET_USER_TASK_QUERY   = 'GetUserTaskQuery';
    const GET_USER_TASK_BY_ID   = 'GetUserTaskById';

    const ROUTES_URL_MAP    = [
        //TODO : change the url @shubham
        self::GET_PROCESS => "/twirp\/rzp.example.user.v1.UserAPI\/List/",
        self::HANDLE_CALLBACK => "/twirp\/rzp.cmma.process.v1.ProcessManagementService\/HandleCallback/",
        self::GET_USER_TASK_QUERY => '/twirp\/rzp.cmma.userTask.v1.UserTaskService\/GetUserTaskQuery/',
        self::GET_USER_TASK_BY_ID => '/twirp\/rzp.cmma.userTask.v1.UserTaskService\/GetUserTaskById/',
        self::CRON_UPDATE_PROCESS_ASSIGNED_TO => "/twirp\/rzp.cmma.process.v1.ProcessManagementServiceAdminCalls\/UpdateProcessAssignedTo/",
    ];

    const ADMIN_ROUTES   = [
        self::GET_PROCESS,
        self::HANDLE_CALLBACK,
        self::GET_USER_TASK_QUERY,
        self::GET_USER_TASK_BY_ID
    ];

    const CRON_ROUTES   = [
        self::CRON_UPDATE_PROCESS_ASSIGNED_TO
    ];

    const ADMIN_ROUTES_VS_PERMISSION   = [
        self::GET_PROCESS   => Name::CMMA_PROCESS_VIEW,
        self::HANDLE_CALLBACK => Name::CMMA_PROCESS_EDIT,
        self::GET_USER_TASK_QUERY => Name::CMMA_USER_TASK_VIEW,
        self::GET_USER_TASK_BY_ID => Name::CMMA_USER_TASK_VIEW
    ];

    public function __construct()
    {
        parent::__construct("cmma");

        $this->registerRoutesMap(self::ROUTES_URL_MAP);
        $this->registerAdminRoutes(self::ADMIN_ROUTES, self::ADMIN_ROUTES_VS_PERMISSION);
        $this->registerCronRoutes(self::CRON_ROUTES);

        $this->setDefaultTimeout(30);

    }

    protected function getAuthorizationHeader()
    {
        return 'Basic ' . base64_encode($this->serviceConfig['user'] . ':' . $this->serviceConfig['password']);
    }

    protected function getCronAuthorizationHeader()
    {
        return 'Basic ' . base64_encode($this->serviceConfig['cron_user'] . ':' . $this->serviceConfig['cron_password']);
    }
}
