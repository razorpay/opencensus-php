<?php

namespace RZP\Http\Controllers;

use Request;
use RZP\Models\Admin\Permission\Name;

class CmmaProxyController extends BaseProxyController
{
    const CRON_UPDATE_PROCESS_ASSIGNED_TO = 'CronUpdateProcessAssignedTo';
    const UPDATE_PROCESS_ASSIGNED_TO      = 'UpdateProcessAssignedTo';
    const GET_PROCESS_INSTANCE            = 'GetProcessInstanceById';
    const FETCH_PROCESS_INSTANCES         = 'FetchProcessInstances';
    const CREATE_PROCESS_INSTANCE         = 'CreateProcessInstance';
    const HANDLE_CALLBACK                 = 'HandleCallback';
    const GET_USER_TASK_QUERY             = 'GetUserTaskQuery';
    const GET_USER_TASK_BY_ID             = 'GetUserTaskById';
    const GET_PROCESS_INSTANCE_DETAILS    = 'GetProcessInstanceDetails';
    const GET_PROCESS_INSTANCE_RESOURCES  = 'GetProcessInstanceResources';
    const UPDATE_TASK                     = 'UpdateTask';
    const UPDATE_USER_TASK_LIST           = 'UpdateUserTaskList';
    const CLOSE_CASE                      = 'CloseCase';
    const GET_AUDIT_DETAILS               = 'GetAuditDetails';
    const CREATE_DISPUTE_CASE_CRON        = 'CreateDisputeCasesCron';

    const CREATE_DISPUT_CASE_CRON_CACHE_KEY = 'dispute_representation_automation_case_create_cron';

    const ROUTES_URL_MAP    = [
        self::GET_PROCESS_INSTANCE            => "/twirp\/rzp.cmma.process.v1.ProcessManagementServiceAdminCalls\/GetProcessInstanceById/",
        self::CREATE_PROCESS_INSTANCE         => "/twirp\/rzp.cmma.process.v1.ProcessManagementService\/CreateProcessInstance/",
        self::HANDLE_CALLBACK                 => "/twirp\/rzp.cmma.process.v1.ProcessManagementService\/HandleCallback/",
        self::UPDATE_TASK                     => "/twirp\/rzp.cmma.userTask.v1.UserTaskService\/UpdateUserTask/",
        self::GET_USER_TASK_QUERY             => '/twirp\/rzp.cmma.userTask.v1.UserTaskService\/GetUserTaskQuery/',
        self::GET_USER_TASK_BY_ID             => '/twirp\/rzp.cmma.userTask.v1.UserTaskService\/GetUserTaskById/',
        self::CRON_UPDATE_PROCESS_ASSIGNED_TO => "/twirp\/rzp.cmma.process.v1.ProcessManagementServiceAdminCalls\/UpdateProcessAssignedTo/",
        self::UPDATE_PROCESS_ASSIGNED_TO      => "/twirp\/rzp.cmma.process.v1.ProcessManagementServiceAdminCalls\/UpdateProcessAssignment/",
        self::FETCH_PROCESS_INSTANCES         => "/twirp\/rzp.cmma.process.v1.ProcessManagementServiceAdminCalls\/FetchProcessInstances/",
        self::GET_PROCESS_INSTANCE_DETAILS    => "/twirp\/rzp.cmma.process.v1.ProcessManagementServiceAdminCalls\/GetProcessInstanceDetails/",
        self::GET_PROCESS_INSTANCE_RESOURCES  => "/twirp\/rzp.cmma.process.v1.ProcessManagementServiceAdminCalls\/GetResources/",
        self::GET_AUDIT_DETAILS               => "/twirp\/rzp.cmma.process.v1.ProcessManagementServiceAdminCalls\/GetAuditDetails/",
        self::UPDATE_USER_TASK_LIST           => "/twirp\/rzp.cmma.userTask.v1.UserTaskService\/UpdateUserTaskList/",
        self::CLOSE_CASE                      => "/twirp\/rzp.cmma.process.v1.ProcessManagementService\/CloseCase/",
        self::CREATE_DISPUTE_CASE_CRON        => "/twirp\/rzp.cmma.cron.v1.CronService\/CreateDisputeCases/",
    ];

    const ADMIN_ROUTES   = [
        self::GET_PROCESS_INSTANCE,
        self::FETCH_PROCESS_INSTANCES,
        self::HANDLE_CALLBACK,
        self::GET_USER_TASK_QUERY,
        self::GET_USER_TASK_BY_ID,
        self::GET_PROCESS_INSTANCE_DETAILS,
        self::GET_PROCESS_INSTANCE_RESOURCES,
        self::UPDATE_TASK,
        self::UPDATE_USER_TASK_LIST,
        self::UPDATE_PROCESS_ASSIGNED_TO,
        self::CLOSE_CASE,
        self::GET_AUDIT_DETAILS,
    ];

    const CRON_ROUTES_CACHE_KEYS = [
        self::CREATE_DISPUTE_CASE_CRON => self::CREATE_DISPUT_CASE_CRON_CACHE_KEY,
    ];

    const CRON_ROUTES   = [
        self::CRON_UPDATE_PROCESS_ASSIGNED_TO,
        self::CREATE_PROCESS_INSTANCE, // In api it is called via a cron script
        self::CREATE_DISPUTE_CASE_CRON,
    ];

    const ADMIN_ROUTES_VS_PERMISSION   = [
        self::GET_PROCESS_INSTANCE           => Name::CMMA_PROCESS_VIEW,
        self::FETCH_PROCESS_INSTANCES        => Name::CMMA_PROCESS_VIEW,
        self::HANDLE_CALLBACK                => Name::CMMA_PROCESS_EDIT,
        self::UPDATE_TASK                    => Name::CMMA_PROCESS_EDIT,
        self::GET_USER_TASK_QUERY            => Name::CMMA_PROCESS_VIEW,
        self::GET_USER_TASK_BY_ID            => Name::CMMA_PROCESS_VIEW,
        self::GET_PROCESS_INSTANCE_DETAILS   => Name::CMMA_PROCESS_VIEW,
        self::GET_PROCESS_INSTANCE_RESOURCES => Name::CMMA_PROCESS_VIEW,
        self::UPDATE_USER_TASK_LIST          => Name::CMMA_PROCESS_EDIT,
        self::UPDATE_PROCESS_ASSIGNED_TO     => Name::CMMA_PROCESS_EDIT,
        self::CLOSE_CASE                     => Name::CMMA_LEADS_SOP,
        self::GET_AUDIT_DETAILS              => Name::CMMA_PROCESS_VIEW,
    ];

    /*
    * timeout in seconds
    */
    const PATH_TIMEOUT_MAP  = [
        self::CREATE_DISPUTE_CASE_CRON                  => 1800, // half hour
    ];


    public function __construct()
    {
        parent::__construct("cmma");

        $this->registerRoutesMap(self::ROUTES_URL_MAP);
        $this->registerAdminRoutes(self::ADMIN_ROUTES, self::ADMIN_ROUTES_VS_PERMISSION);
        $this->registerCronRoutes(self::CRON_ROUTES);
        $this->setCacheKeysForRoutes(self::CRON_ROUTES_CACHE_KEYS);

        $this->setDefaultTimeout(30);
        $this->setPathTimeoutMap(self::PATH_TIMEOUT_MAP);

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
            'X-Admin-Name'     => optional($this->ba->getAdmin())->getName() ?? '',
            'X-Task-Id'        => $this->app['request']->getTaskId(),
            'Content-Type'     => 'application/json',
            'Accept'           => 'application/json',
            'Authorization'    => $this->getAuthorizationHeader(),
            'X-Request-ID'     => Request::getTaskId(),
            'X-Client-ID'      => $this->serviceConfig['client_id'] ?? ''
        ];
    }
}
