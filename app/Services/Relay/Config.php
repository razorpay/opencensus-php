<?php

namespace RZP\Services\Relay;

use RZP\Constants\Entity as E;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Trace\TraceCode;

class Config extends Base
{
    //Relay Service URLs

    //Property URLs
    const NEW_CONFIG_CREATE_URI         = '/apps/:appID/props';
    const CONFIG_UPDATE_URI             = '/apps/:appID/props/:propId';
    const CONFIG_DELETE_URI             = '/apps/:appID/props/:propId';
    const CREATE_RELAY_WORKFLOW_CONFIG  = '/apps/:appID/props';
    const FETCH_RELAY_PROPS             = '/apps/:appID/props';
    const CREATE_BULK_PROPERTIES_URI    = '/apps/:appID/props/bulk';
    const FETCH_CONFIG_HISTORY_URI      = '/apps/:appID/props/:propId/history';

    //Property Action URLs
    const RELAY_CONFIG_ACTION_URI           = '/props/pending/check';
    const FETCH_RELAY_PENDING_CONFIG_URI    = '/apps/:appID/props/pending';

    //Application URLs
    const FETCH_RELAY_APPS = '/apps';
    const FETCH_RELAY_APP_BY_ID = '/apps/:appID';
    const CREATE_RELAY_APP = '/apps';
    const DELETE_RELAY_APPS = '/apps/:appID';
    const UPDATE_RELAY_APPS = '/apps/:appID';

    //properties constant
    const APP_ID = "app_id";
    const APPROVAL_REQUIRED = "approval_required";
    const APPROVAL_DETAILS = "approval_details";
    const PROPERTIES_ID = "property_id";


    public function __construct($app)
    {
        parent::__construct($app);
    }

    public function createProps($appID, $input): array
    {
        $endpoint = self::NEW_CONFIG_CREATE_URI;
        $endpoint = str_replace_first(':appID', $appID, $endpoint);

        //add maker checker flag
        $input[self::APPROVAL_REQUIRED] = true;

        //set approval details
        $input[self::APPROVAL_DETAILS] = $this->setApprovalDetails();

        return $this->makeRequest($endpoint, self::METHOD_POST, $input);
    }

    public function createBulkProps($appID, $input): array
    {
        $endpoint = self::CREATE_BULK_PROPERTIES_URI;
        $endpoint = str_replace_first(':appID', $appID, $endpoint);

        //add maker checker flag
        $input[self::APPROVAL_REQUIRED] = true;

        //set approval details
        $input[self::APPROVAL_DETAILS] = $this->setApprovalDetails();

        return $this->makeRequest($endpoint, self::METHOD_POST, $input);
    }

    public function updateProps($appID, $propID, $input): array
    {
        $endpoint = self::CONFIG_UPDATE_URI;

        $endpoint = str_replace_first(':appID', $appID, $endpoint);

        $endpoint = str_replace_first(':propId', $propID, $endpoint);

        //add maker checker flag
        $input[self::APPROVAL_REQUIRED] = true;

        //Version is converting to string when requesting is coming to api.
        //Doing a manual conversion here for now
        $input["version"] = (int)$input["version"];

        //set approval details
        $input[self::APPROVAL_DETAILS] = $this->setApprovalDetails();

        return $this->makeRequest($endpoint, self::METHOD_PATCH, $input);
    }

    public function deleteProps($appID, $propID, $input): array
    {
        $endpoint = self::CONFIG_DELETE_URI;

        $endpoint = str_replace_first(':appID', $appID, $endpoint);

        $endpoint = str_replace_first(':propId', $propID, $endpoint);

        //add maker checker flag
        $input[self::APPROVAL_REQUIRED] = true;

        //set approval details
        $input[self::APPROVAL_DETAILS] = $this->setApprovalDetails();

        return $this->makeRequest($endpoint, self::METHOD_DELETE, $input);

    }

    public function getPendingProps($appID, $input): array
    {
        $endpoint = self::FETCH_RELAY_PENDING_CONFIG_URI;

        $endpoint = str_replace_first(':appID', $appID, $endpoint);

        return $this->makeRequest($endpoint, self::METHOD_GET, $input);
    }

    public function propsAction($input): array
    {
        $actorInfo = $this->enrichActorFieldsForActions();

        $input["actor_details"] = $actorInfo;

        $input[self::OWNER_ID] = "10000000000000";
        $input[self::OWNER_TYPE] = "user";

        return $this->makeRequest(self::RELAY_CONFIG_ACTION_URI,
            self::METHOD_POST,
            $input);
    }

    protected function enrichActorFieldsForActions(): array
    {
        $roleNames = [];

        $actorPropertyKey = 'role';

        $actorPropertyValue = '';

        $actorId = '';

        $actorType = '';

        $adminRoles = $this->auth->getAdmin()->roles()->get();

        foreach ($adminRoles as $adminRole)
        {
            $role = $this->service(E::ROLE)->getRole($adminRole->getPublicId());

            array_push($roleNames, $role['name']);
        }

        if ($this->auth->isAdminAuth() === true)
        {
            $actorId = $this->auth->getAdmin()->getId();
            $actorType = 'user';
        }

        return [
        'actor_id'              => $actorId,
        'actor_type'            => $actorType,
        'actor_property_key'    => $actorPropertyKey,
        'actor_property_value'  => $actorPropertyValue,
        'actor_meta' =>        ['email' => null, 'name' => null],
        'all_roles' => $roleNames,
        ];
    }

    public function getApps($input): array
    {
        $endpoint = self::FETCH_RELAY_APPS;

        return $this->makeRequest($endpoint, self::METHOD_GET, $input);
    }

    public function getAppByID($appID, $input): array
    {
        $endpoint = self::FETCH_RELAY_APP_BY_ID;

        $endpoint = str_replace_first(':appID', $appID, $endpoint);

        return $this->makeRequest($endpoint, self::METHOD_GET, $input);
    }

    public function updateApps($appID, $input): array
    {
        $endpoint = self::UPDATE_RELAY_APPS;

        $endpoint = str_replace_first(':appID', $appID, $endpoint);

        return $this->makeRequest($endpoint, self::METHOD_PATCH, $input);
    }

    public function deleteApps($appID, $input): array
    {
        $endpoint = self::DELETE_RELAY_APPS;

        $endpoint = str_replace_first(':appID', $appID, $endpoint);

        return $this->makeRequest($endpoint, self::METHOD_DELETE, $input);
    }

    public function createApp($input): array
    {
        $endpoint = self::CREATE_RELAY_APP;

        return $this->makeRequest($endpoint, self::METHOD_POST, $input);
    }

    public function getProps($appID, $input): array
    {
        $endpoint = self::FETCH_RELAY_PROPS;

        $endpoint = str_replace_first(':appID', $appID, $endpoint);

        return $this->makeRequest($endpoint, self::METHOD_GET, $input);
    }

    public function getPropsHistory($appID, $propID, $input): array
    {
        $endpoint = self::FETCH_CONFIG_HISTORY_URI;

        $endpoint = str_replace_first(':appID', $appID, $endpoint);

        $endpoint = str_replace_first(':propId', $propID, $endpoint);

        return $this->makeRequest($endpoint, self::METHOD_GET, $input);
    }

    /**
     * Returns the service instance.
     * Three ways to get the service instance:
     *  1. If entity is passed to the function, we get the class
     *     from the entity namespace.
     *  2. If service variable is defined in the child controller class,
     *     we get an object of the class defined by the service variable.
     *  3. If both the above conditions don't match, we get the entity name
     *     using the child controller class name and follow the (1) flow.
     *
     * @param null $entity
     *
     * @return mixed Either Base\Service or external package services like OAuth
     */
    protected function service($entity = null)
    {
        if ($entity !== null)
        {
            $class = E::getEntityService($entity);
        }
        else if ($this->service !== null)
        {
            $class = $this->service;
        }
        else
        {
            $controllerClassFQN = explode('\\', static::class);

            $controllerClass = end($controllerClassFQN);

            $classNameArray = explode("Controller", $controllerClass);

            $entity = snake_case($classNameArray[0]);

            $class = E::getEntityService($entity);
        }
        return new $class;
    }
}
