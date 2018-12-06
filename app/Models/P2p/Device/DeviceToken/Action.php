<?php

namespace RZP\Models\P2p\Device\DeviceToken;

use RZP\Exception;
use RZP\Models\P2p\Base;

class Action extends Base\Action
{
    const ADD                  = 'add';

    const REFRESH_CL_TOKEN     = 'refreshClToken';

    const DEREGISTER           = 'deregister';
}
