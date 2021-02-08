<?php

namespace RZP\Models\BankingAccountTpv;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\BankAccount;
use RZP\Models\Merchant\Balance\Type;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Models\FundAccount\Validation\Entity as FundAccountValidation;

class Core extends Base\Core
{

    public function create(array $input)
    {
        $this->trace->info(TraceCode::ADMIN_CREATE_TPV, $input);

        $this->duplicateTpvCheckAndPullFavId($input);

        $tpv = (new Entity)->build($input);

        if ($input[Entity::STATUS] === Status::APPROVED)
        {
            $tpv->setIsActive(true);
        }

        $this->repo->saveOrFail($tpv);

        return $tpv->toArrayPublic();
    }

    public function edit(array $input)
    {
        $this->trace->info(TraceCode::ADMIN_EDIT_TPV, $input);

        $tpv = $this->repo->banking_account_tpv->fetchTpvOnMerchantBalanceAccountNumberIfsc($input);

        if (empty($tpv) === true)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_TPV_NOT_EXISTS);
        }

        $tpv->edit($input, 'admin_edit');

        if (isset($input[Entity::STATUS]) === true)
        {
            if ($input[Entity::STATUS] === Status::APPROVED)
            {
                $tpv->setIsActive(true);
            }
            else
            {
                $tpv->setIsActive(false);
            }
        }

        $this->repo->saveOrFail($tpv);

        return $tpv->toArrayPublic();
    }

    public function fetchMerchantTpvs()
    {
        $tpvs = $this->repo->banking_account_tpv->fetchMerchantTpvs($this->merchant->getId());

        $this->trace->info(TraceCode::MERCHANT_FETCH_TPV, $tpvs->toArrayPublic());

        return $tpvs->toArrayPublic();
    }

    public function getMerchantTpvsWithFavDetails($input, $merchantId)
    {
        $this->repo->merchant->findOrFail($merchantId);

        $tpvs = $this->repo->banking_account_tpv->fetch($input, $merchantId);

        $result = $tpvs->toArrayPublic();

        $this->trace->info(TraceCode::MERCHANT_TPV_FAV_INFO, $result);

        return $result;
    }

    public function duplicateTpvCheckAndPullFavId(array &$input)
    {
        if (isset($input[Entity::FUND_ACCOUNT_VALIDATION_ID]) === true)
        {
            $input[Entity::FUND_ACCOUNT_VALIDATION_ID] = FundAccountValidation::verifyIdAndSilentlyStripSign($input[Entity::FUND_ACCOUNT_VALIDATION_ID]);

            $this->repo->fund_account_validation->findOrFail($input[Entity::FUND_ACCOUNT_VALIDATION_ID]);
        }

        $tpvRecord = $this->repo->banking_account_tpv->fetchTpvOnMerchantBalanceAccountNumberIfsc($input);

        if (empty($tpvRecord) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_DUPLICATE_TPV);
        }

    }

    public function createAutoApprovedTpvForActivatedMerchants(Merchant $merchant, string $mode)
    {
        try
        {
            $bankAccount = (new BankAccount\Repository())->getBankAccountOnConnection($merchant, $mode);

            $balance = $this->repo->balance->getMerchantBalanceByTypeAndAccountType(
                                                                    $merchant->getId(),
                                                                    Type::BANKING,
                                                                    AccountType::SHARED,
                                                                    $mode);

            $tpvInput = [
                Entity::MERCHANT_ID          => $merchant->getMerchantId(),
                Entity::BALANCE_ID           => $balance->getId(),
                Entity::STATUS               => Status::APPROVED,
                Entity::PAYER_NAME           => $bankAccount->getBeneficiaryName(),
                Entity::PAYER_ACCOUNT_NUMBER => $bankAccount->getAccountNumber(),
                Entity::PAYER_IFSC           => $bankAccount->getIfscCode(),
            ];

            $tpvExists = $this->repo->banking_account_tpv->fetchTpvOnMerchantBalanceAccountNumberIfsc($tpvInput);

            if (empty($tpvExists) === true)
            {
                $tpv = $this->create($tpvInput);

                $this->trace->info(TraceCode::AUTO_APPROVED_TPV_FOR_ACTIVATED_MERCHANT,
                                   [
                                       'tpv'         => $tpv,
                                       'merchant_id' => $merchant->getMerchantId(),
                                   ]);
            }
        }
        catch (\Exception $e)
        {
            $trace['error'] = $e->getMessage();

            $this->trace->error(
                TraceCode::AUTO_APPROVED_TPV_CREATION_ERROR,
                $trace);
        }
    }
}
