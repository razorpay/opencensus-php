<?php

namespace RZP\Models\FundAccount\Validation;

use RZP\Models\Merchant;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Base\Traits;

class Service extends Base\Service
{
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

    /**
     * @param array $input
     *
     * @return array
     *
     * @throws Exception\BadRequestException
     * @throws \Throwable
     */
    public function create(array $input): array
    {
        $this->processAccountNumber($input);

        $entity = $this->core->create($input, $this->merchant);

        return $entity->toArrayPublic();
    }

    /**
     * @param array $input
     *
     * @return array
     *
     * @throws Exception\BadRequestValidationFailureException
     * @throws Exception\InvalidArgumentException
     */
    public function fetchMultiple(array $input): array
    {
        $this->processAccountNumber($input);

        $entities = $this->entityRepo
            ->fetch($input, $this->merchant->getId());

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

    /**
     * If account number present, inject balance id for RX
     *
     * @param array $input
     *
     * @throws Exception\BadRequestException
     */
    protected function processAccountNumber(array & $input)
    {
        /** @var Merchant\Validator $merchantValidator */
        $merchantValidator = $this->merchant->getValidator();

        if ($merchantValidator->isAccountNumberPresentInArray($input))
        {
            $merchantValidator->validateAndTranslateAccountNumberForBanking($input);
        }

        return;
    }
}
