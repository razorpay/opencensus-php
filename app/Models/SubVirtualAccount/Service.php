<?php

namespace RZP\Models\SubVirtualAccount;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;

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
     * @throws Exception\BadRequestException
     */
    public function create(array $input): array
    {
        $this->trace->info(TraceCode::SUB_VIRTUAL_ACCOUNT_CREATE_REQUEST, ['input' => $input]);

        (new Validator)->validateInput('create', $input);

        $subVirtualAccount = $this->core->create($input);

        return $subVirtualAccount->toArrayPublic();
    }

    /**
     * This route is for admin route. We need to return
     * only active and inactive accounts on admin dashboard
     *
     * @param string $id
     *
     * @return array
     */
    public function fetchMultipleAdmin(string $id): array
    {
        $input = [
            Entity::MASTER_MERCHANT_ID => $id,
        ];

        $subVirtualAccounts = $this->core->fetchMultiple($input);

        return $subVirtualAccounts->toArrayPublic();
    }

    /**This route is for proxy route. We need to return
     * only active accounts on merchant dashboard
     *
     * @return array
     */
    public function fetchMultiple(): array
    {
        $input = [
            Entity::MASTER_MERCHANT_ID => $this->merchant->getId(),
            Entity::ACTIVE             => true
        ];

        $subVirtualAccounts = $this->core->fetchMultiple($input);

        return $subVirtualAccounts->toArrayPublic();
    }

    /**
     * @param string $id
     * @param array $input
     * @return array
     * @throws Exception\BadRequestException
     */
    public function enableOrDisable(string $id, array $input)
    {
        $this->trace->info(TraceCode::SUB_VIRTUAL_ACCOUNT_ENABLE_DISABLE_REQUEST, ['input' => $input]);

        (new Validator)->validateInput('enable_or_disable', $input);

        $response = $this->core->enableOrDisable($id, $input);

        return $response->toArrayPublic();
    }
}
