<?php

namespace RZP\Models\Merchant\Document;

use RZP\Models\Base;

class Service extends Base\Service
{
    use Base\Traits\ServiceHasCrudMethods;

    /**
     * @var Repository
     */
    protected $entityRepo;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;

        $this->entityRepo = $this->repo->merchant_document;
    }

}
