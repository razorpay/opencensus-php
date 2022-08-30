<?php

namespace RZP\Models\P2p\BlackList;

use RZP\Exception;
use RZP\Models\P2p\Base;

class Action extends Base\Action
{
    const ADD_BLACKLIST       = 'add_blacklist';

    const REMOVE_BLACKLIST    = 'remove_blacklist';

    const FETCH_ALL           = 'fetch_all';
}
