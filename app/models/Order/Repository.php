<?php

namespace Models\Order;

use Models\Base;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;
    use Base\RepositoryUpdateTestAndLive;

    protected $entity = 'order';
}
