<?php

namespace RZP\Models\Workflow\Service\Adapter;

class Constants
{
    const SERVICE_RX                    = 'rx_';
    const PAYOUT_SERVICE_CALLBACK       = 'payouts_';

    const WORKFLOW_TYPE                 = 'workflow_type';
    const PAYOUT_APPROVAL_TYPE          = 'payout-approval';

    const SUCCESS_STATUS_CODES          = 'success_status_codes';

    const MERCHANT                      = 'merchant';
    const USER                          = 'user';
    const ADMIN                         = 'admin';
    const SERVICE                       = 'service';
    const ROLE                          = 'role';
    const API                           = 'api';
    const NAME                          = 'name';
    const INTERNAL_ACTOR_NAME           = 'internalsystem';

    const WORKFLOW_ID           = 'workflow_id';
    const STATUS                = 'status';
    const DOMAIN_STATUS         = 'domain_status';
    const CONFIG_ID             = 'config_id';

    const WORKFLOW_HISTORY      = 'workflow_history';
    const WORKFLOW_STATES       = 'states';
    const WORKFLOW_ACTIONS      = 'actions';

    const ID                    = 'id';
    const STATE_ID              = 'state_id';
    const ACTION_TYPE           = 'action_type';
    const COMMENT               = 'comment';
    const ACTOR_ID              = 'actor_id';
    const ACTOR_TYPE            = 'actor_type';
    const ACTOR_PROPERTY_KEY    = 'actor_property_key';
    const ACTOR_PROPERTY_VALUE  = 'actor_property_value';
    const ACTOR_META            = 'actor_meta';
    const NARRATION             = 'narration';
    const NOTES                 = 'notes';
    const REJECTED              = 'rejected';
}
