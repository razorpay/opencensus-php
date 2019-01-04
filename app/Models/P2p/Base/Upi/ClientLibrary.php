<?php

namespace RZP\Models\P2p\Base\Upi;

use RZP\Models\P2p\Base\Libraries\ArrayBag;

class ClientLibrary extends ArrayBag
{
    const CL                = 'cl';
    const CAPABILITY        = 'capability';
    const CHALLENGE         = 'challenge';
    const TOKEN             = 'token';
    const PAYLOAD           = 'payload';
}
