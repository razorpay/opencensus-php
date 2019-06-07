<?php

namespace RZP\Models\P2p\Transaction\Concern;

use RZP\Exception;
use RZP\Models\P2p\Base;

class Action extends Base\Action
{
    const RAISE            = 'raise';
    const RAISE_SUCCESS    = 'raiseSuccess';
    const RAISE_FAILURE    = 'raiseFailure';

    const QUERY            = 'query';
    const QUERY_SUCCESS    = 'querySuccess';
    const QUERY_FAILURE    = 'queryFailure';

    const UPDATE           = 'update';
    const UPDATE_SUCCESS   = 'updateSuccess';
    const UPDATE_FAILURE   = 'updateFailure';
}
