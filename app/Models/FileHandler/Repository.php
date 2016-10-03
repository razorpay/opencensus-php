<?php

namespace RZP\Models\FileHandler;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'file_handler';
}
