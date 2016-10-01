<?php

namespace RZP\Models\Pricing\FeeBreakup;

use RZP\Models\Base;
use RZP\Exception;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'fee_breakup';
}
