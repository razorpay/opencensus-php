<?php

namespace Rzp\Models\P2p\Device;

use RZP\Exception;
use RZP\Models\P2p\Base;

class Action extends Base\Action
{
    const CREATE               = 'create';

    const FETCH                = 'fetch';

    const REFRESH_CL_TOKEN     = 'refreshClToken';

    const DELETE               = 'delete';
}
