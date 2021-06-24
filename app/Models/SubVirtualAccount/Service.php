<?php

namespace RZP\Models\SubVirtualAccount;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestValidationFailureException;

/**
 * Class Service
 *
 * @package RZP\Models\SubVirtualAccount
 */
class Service extends Base\Service
{
    use Base\Traits\ServiceHasCrudMethods;

    /**
     * @var Core
     */
    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;
    }

    /**
     * @param array $input
     *
     * @return array
     * @throws BadRequestValidationFailureException
     */
    public function create(array $input): array
    {
        $this->trace->info(TraceCode::SUB_VIRTUAL_ACCOUNT_CREATE_REQUEST, ['input' => $input]);

        if ($this->auth->isAdminAuth() !== true)
        {
            throw new Exception\BadRequestValidationFailureException(ErrorCode::BAD_REQUEST_SUB_VIRTUAL_ACCOUNT_CREATE_ADMIN_AUTH_ONLY);
        }

        (new Validator)->validateInput('create', $input);

        $subVirtualAccount = $this->core->create($input);

        return $subVirtualAccount->toArrayPublic();
    }
}
