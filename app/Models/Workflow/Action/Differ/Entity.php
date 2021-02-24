<?php

namespace RZP\Models\Workflow\Action\Differ;

use RZP\Models\Workflow\Base;

/*
    We don't have a DB table for this. This entity
    is just used to represent our differ data that eventually
    goes into ES. This is how it looks like (sorta):

    array (14) [
        'id' => string (14) "7bWmWhpptgAjhY"
        'entity_name' => string (5) "hello"
        'entity_id' => string (20) "admin_7bU48ZewEqdPUk"
        'maker' => string (15) "Rishabh Pugalia"
        'type' => string (5) "maker"
        'url' => string (78) "http://api.razorpay.in/v1/orgs/org_100000razorpay/admins/admin_7bU48ZewEqdPUk"
        'route_params' => array (2) [
            'orgId' => string (18) "org_100000razorpay"
            'id' => string (20) "admin_7bU48ZewEqdPUk"
        ]
        'method' => string (3) "PUT"
        'payload' => array (5) [
            'name' => string (4) "test"
            'disabled' => integer 0
            'roles' => array (1) [
                string (19) "role_6dLbNSpv5XbC5E"
            ]
            'groups' => array (0)
            'allow_all_merchants' => string (1) "1"
        ]
        'controller' => string (53) "RZP\Http\Controllers\OrganizationController@editAdmin"
        'route' => string (10) "admin_edit"
        'permission' => string (21) "edit_merchant_archive"
        'action_id' => string (14) "7bWmWeONFIeZrJ"
        'created_at' => integer 1491488037
        'state' => string (4) "open"
        'auth_details' => [
            'merchant_id' => string (14) "10000000000000"
        ]
    ]
*/

class Entity extends Base\Entity
{
    const ID                        = 'id';
    const ENTITY_NAME               = 'entity_name';
    const ENTITY_ID                 = 'entity_id';
    const MAKER_ID                  = 'maker_id';
    const MAKER_TYPE                = 'maker_type';
    const MAKER                     = 'maker';
    const TYPE                      = 'type';
    const URL                       = 'url';
    const ROUTE_PARAMS              = 'route_params';
    const METHOD                    = 'method';
    const PAYLOAD                   = 'payload';
    const CONTROLLER                = 'controller';
    const ROUTE                     = 'route';
    const DIFF                      = 'diff';
    const ACTION_ID                 = 'action_id';
    const STATE                     = 'state';
    const AUTH_DETAILS              = 'auth_details';

    const CREATED_AT                = 'created_at';

    const FUNCTION_NAME             = 'function_name';
    const PERMISSION                = 'permission';
    const OLD                       = 'old';
    const NEW                       = 'new';
    const WORKFLOW_OBSERVER_DATA    = 'workflow_observer_data';

    protected $entity   = 'action';

    protected $fillable = [
        self::ENTITY_NAME,
        self::ENTITY_ID,
        self::MAKER_ID,
        self::MAKER_TYPE,
        self::MAKER,
        self::TYPE,
        self::URL,
        self::ROUTE_PARAMS,
        self::METHOD,
        self::PAYLOAD,
        self::CONTROLLER,
        self::ROUTE,
        self::DIFF,
        self::ACTION_ID,
        self::CREATED_AT,
        self::STATE,
        self::PERMISSION,
        self::AUTH_DETAILS,
        self::WORKFLOW_OBSERVER_DATA,
    ];

    protected $visible = [
        self::ID,
        self::ENTITY_NAME,
        self::ENTITY_ID,
        self::MAKER_ID,
        self::MAKER_TYPE,
        self::MAKER,
        self::TYPE,
        self::URL,
        self::ROUTE_PARAMS,
        self::METHOD,
        self::PAYLOAD,
        self::CONTROLLER,
        self::ROUTE,
        self::DIFF,
        self::ACTION_ID,
        self::CREATED_AT,
        self::STATE,
        self::PERMISSION,
        self::AUTH_DETAILS,
        self::WORKFLOW_OBSERVER_DATA,
    ];

    protected $public = [
        self::ID,
        self::ENTITY_NAME,
        self::ENTITY_ID,
        self::MAKER_ID,
        self::MAKER_TYPE,
        self::MAKER ,
        self::TYPE,
        self::URL,
        self::ROUTE_PARAMS,
        self::METHOD,
        self::PAYLOAD,
        self::CONTROLLER,
        self::ROUTE,
        self::DIFF,
        self::ACTION_ID,
        self::CREATED_AT,
        self::STATE,
        self::PERMISSION,
        self::AUTH_DETAILS,
        self::WORKFLOW_OBSERVER_DATA,
    ];

    protected $casts = [
        self::WORKFLOW_OBSERVER_DATA   => 'json',
    ];

    public function setDiff(array $diff)
    {
        $this->setAttribute(self::DIFF, $diff);
    }

    public function getDiff()
    {
        return $this->getAttribute(self::DIFF);
    }

    public function getEntityName() : string
    {
        return $this->getAttribute(self::ENTITY_NAME);
    }

    public function getEntityId()
    {
        return $this->getAttribute(self::ENTITY_ID);
    }

    public function getWorkflowObserverData()
    {
        return $this->getAttribute(self::WORKFLOW_OBSERVER_DATA);
    }

    public function getRoute() : string
    {
        return $this->getAttribute(self::ROUTE);
    }

    public function getPayload()
    {
        return $this->getAttribute(self::PAYLOAD);
    }
}
