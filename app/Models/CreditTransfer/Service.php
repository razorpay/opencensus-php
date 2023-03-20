<?php


namespace RZP\Models\CreditTransfer;

use RZP\Models\Base;

class Service extends Base\Service
{
    /**
     * @var Repository
     */
    protected $entityRepo;

    /**
     * @var Core
     */
    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->entityRepo = $this->repo->credit_transfer;

        $this->core = (new Core());
    }

    public function createAsync(array $input)
    {
        return $this->core->createAsync($input);
    }

    public function fetchMultiple(array $params, $isMerchantIdRequired = true)
    {
        if ($isMerchantIdRequired === false)
        {
            $this->entityRepo->setMerchantIdRequiredForMultipleFetch(false);
        }

        return $this->entityRepo->fetch($params);
    }
}
