<?php

namespace RZP\Models\SubVirtualAccount;

use RZP\Constants;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\User;
use RZP\Trace\TraceCode;
use RZP\Models\Adjustment\Entity as AdjustmentEntity;
use RZP\Models\Merchant\Entity as MerchantEntity;

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

    public function transferWithOtp(array $input)
    {
        (new Validator)->validateInput('sub_virtual_account_transfer_with_otp', $input);

        $transferInput = $this->verifyOtpForTransfer($input);

        return $this->transfer($transferInput);
    }

    protected function transfer(array $input)
    {
        $this->trace->info(TraceCode::SUB_VIRTUAL_ACCOUNT_TRANSFER_REQUEST, ['input' => $input]);

        $validator = new Validator;

        $validator->validateInput('sub_virtual_account_transfer', $input);

        $masterMerchantId = $this->merchant->getId();

        $subVirtualAccount = $this->repo->sub_virtual_account->getSubVirtualAccountWithMasterMerchantIdAndAccountNumbers($input, $masterMerchantId);

        $validator->validateSubVirtualAccount($subVirtualAccount, $input);

        $validator->validateMasterMerchant($this->merchant);

        /** @var  $subMerhcantEntity  MerchantEntity*/
        $subMerhcantEntity = $this->repo->merchant->findOrFail($subVirtualAccount->getSubMerchantId());

        $validator->validateSubMerchant($subMerhcantEntity);

        /** @var  $masterAdjEntity AdjustmentEntity*/
        $masterAdjEntity = $this->core->transfer($input, $this->merchant, $subMerhcantEntity);

        $this->trace->info(
            TraceCode::SUB_VIRTUAL_ACCOUNT_TRANSFER_RESPONSE,
            [
                Entity::MASTER_ADJUSTMENT_ENTITY => $masterAdjEntity->toArrayPublic(),
            ]);

        return $masterAdjEntity->toArrayPublic();
    }

    private function verifyOtpForTransfer(array $input)
    {
        $this->user->validateInput('verifyOtp', array_only($input, ['otp', 'token']));

        (new User\Core)->verifyOtp($input + ['action' => 'sub_virtual_account_transfer'],
            $this->merchant,
            $this->user,
            $this->mode === Constants\Mode::TEST);

        return array_except($input, ['otp', 'token']);
    }
}
