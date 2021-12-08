<?php

namespace RZP\Models\CorporateCard;

use DB;

use RZP\Models\Base;
use RZP\Models\Base\Traits\ExternalCore;
use RZP\Models\Base\Traits\ExternalRepo;

class Repository extends Base\Repository
{
    use ExternalRepo, ExternalCore;

    protected $entity = 'corporate_card';

}
