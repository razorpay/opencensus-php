<?php

namespace RZP\Models\P2p\BlackList;

use RZP\Exception;
use RZP\Models\P2p\Base;

class Action extends Base\Action
{
    const ADD       = 'add';

    const REMOVE    = 'remove';

    const FETCH_ALL = 'fetch_all';
}
