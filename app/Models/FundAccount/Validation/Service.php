<?php

namespace RZP\Models\FundAccount\Validation;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Base\Traits;
use RZP\Models\Merchant\Balance;

class Service extends Base\Service
{
    use Traits\ProcessAccountNumber;
    use Traits\ServiceHasCrudMethods;

    /**
     * @var Core
     */
    protected $core;

    /**
     * @var Repository
     */
    protected $entityRepo;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core();

        $this->entityRepo = $this->repo->fund_account_validation;
    }

    public function create(array $input): array
    {
        if (empty($input[Balance\Entity::ACCOUNT_NUMBER]) === false)
        {
            // mandates account number and converts to balance id
            $this->processAccountNumber($input);
        }

        $entity = $this->core->create($input, $this->merchant);

        s($entity->toArrayPublic());

        return $entity->toArrayPublic();
    }

    public function fetchMultiple(array $input): array
    {
        if (empty($input[Balance\Entity::ACCOUNT_NUMBER]) === false)
        {
            // mandates account number and converts to balance id
            $this->processAccountNumber($input);
        }

        $entities = $this->entityRepo->fetch($input, $this->merchant->getId());

        return $entities->toArrayPublic();
    }

    public function retry(array $input): array
    {
        $this->trace->info(TraceCode::FUND_ACCOUNT_VALIDATION_RETRY_REQUEST, [
            'input' => $input
        ]);

        $response = $this->core->retry($input);

        return $response;
    }

    public function retryAllFundAccountValidations(array $input): array
    {
        $this->trace->info(TraceCode::FUND_ACCOUNT_VALIDATION_RETRY_REQUEST, [
            'input' => $input
        ]);

        $response = $this->core->retryAllFundAccountValidations($input);

        return $response;
    }
}
