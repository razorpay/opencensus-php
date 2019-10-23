<?php

namespace RZP\Models\Merchant\LegalEntity;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    use Base\RepositoryUpdateTestAndLive;

    protected $entity = 'legal_entity';

}
