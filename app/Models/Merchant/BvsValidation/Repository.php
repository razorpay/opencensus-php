<?php

namespace RZP\Models\Merchant\BvsValidation;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    use Base\RepositoryUpdateTestAndLive;

    protected $entity = 'bvs_validation';

}
