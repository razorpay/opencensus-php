<?php

namespace RZP\Http\Controllers;

use Request;
use RZP\Models\Admin\Permission\Name;

class CmmaProxyController extends BaseProxyController
{
    const CRON_UPDATE_PROCESS_ASSIGNED_TO = 'CronUpdateProcessAssignedTo';
    const GET_PROCESS_INSTANCE            = 'GetProcessInstanceById';
    const FETCH_PROCESS_INSTANCES         = 'FetchProcessInstances';
    const CREATE_PROCESS_INSTANCE         = 'CreateProcessInstance';
    const HANDLE_CALLBACK                 = 'HandleCallback';
    const GET_USER_TASK_QUERY             = 'GetUserTaskQuery';
    const GET_USER_TASK_BY_ID             = 'GetUserTaskById';

    const ROUTES_URL_MAP    = [
        self::GET_PROCESS_INSTANCE => "/twirp\/rzp.cmma.process.v1.ProcessManagementServiceAdminCalls\/GetProcessInstanceById/",
        self::CREATE_PROCESS_INSTANCE => "/twirp\/rzp.cmma.process.v1.ProcessManagementService\/CreateProcessInstance/",
        self::HANDLE_CALLBACK => "/twirp\/rzp.cmma.process.v1.ProcessManagementService\/HandleCallback/",
        self::GET_USER_TASK_QUERY => '/twirp\/rzp.cmma.userTask.v1.UserTaskService\/GetUserTaskQuery/',
        self::GET_USER_TASK_BY_ID => '/twirp\/rzp.cmma.userTask.v1.UserTaskService\/GetUserTaskById/',
        self::CRON_UPDATE_PROCESS_ASSIGNED_TO => "/twirp\/rzp.cmma.process.v1.ProcessManagementServiceAdminCalls\/UpdateProcessAssignedTo/",
        self::FETCH_PROCESS_INSTANCES => "/twirp\/rzp.cmma.process.v1.ProcessManagementServiceAdminCalls\/FetchProcessInstances/",
    ];

    const ADMIN_ROUTES   = [
        self::GET_PROCESS_INSTANCE,
        self::FETCH_PROCESS_INSTANCES,
        self::HANDLE_CALLBACK,
        self::GET_USER_TASK_QUERY,
        self::GET_USER_TASK_BY_ID
    ];

    const CRON_ROUTES   = [
        self::CRON_UPDATE_PROCESS_ASSIGNED_TO,
        self::CREATE_PROCESS_INSTANCE // In api it is called via a cron script
    ];

    const ADMIN_ROUTES_VS_PERMISSION   = [
        self::GET_PROCESS_INSTANCE   => Name::CMMA_PROCESS_VIEW,
        self::FETCH_PROCESS_INSTANCES => Name::CMMA_PROCESS_VIEW,
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

    protected function getHeadersForAdminRequest($body)
    {
        return [
            'X-Admin-id'       => optional($this->ba->getAdmin())->getPublicId() ?? '',
            'X-Task-Id'        => $this->app['request']->getTaskId(),
            'Content-Type'     => 'application/json',
            'Accept'           => 'application/json',
            'Authorization'    => $this->getAuthorizationHeader(),
            'X-Request-ID'     => Request::getTaskId(),
            'X-Client-ID'      => $this->serviceConfig['client_id'] ?? ''
        ];
    }
}
