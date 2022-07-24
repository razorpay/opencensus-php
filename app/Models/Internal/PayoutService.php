<?php

namespace RZP\Models\Internal;

use RZP\Models\Payout;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Exception\BadRequestException;

class PayoutService extends Service
{
    public function createOnPayout(Payout\Entity $payout): array
    {
        $payoutType = Payout\Core::getInterAccountPayoutType($payout);

        $this->trace->info(TraceCode::INTERNAL_CREATE_ON_PAYOUT_INPUT_DATA,
            [
                Payout\Entity::ID          => $payout->getPublicId(),
                Payout\Entity::AMOUNT      => $payout->getAmount(),
                Payout\Entity::BASE_AMOUNT => $payout->getBaseAmount(),
                Payout\Entity::UTR         => $payout->getUtr(),
                Payout\Entity::CURRENCY    => $payout->getCurrency(),
                Payout\Entity::TYPE        => self::TYPE_CREDIT,
                Payout\Entity::UPDATED_AT  => $payout->getUpdatedAt(),
                Payout\Entity::MODE        => $payout->getMode(),
                self::PAYOUT_TYPE          => $payoutType,
            ]);

        if ($payoutType === self::TEST_INTER_ACCOUNT_PAYOUT)
        {
            $request = $this->createRequestForInterAccountTestPayout($payout);
        }
        else if ($payoutType === self::INTER_ACCOUNT_PAYOUT)
        {
            $request = $this->createRequestForInterAccountPayout($payout);
        }
        else if ($payoutType === self::ONDEMAND_SETTLEMENT_XVA_PAYOUT)
        {
            $request = $this->createRequestForOndemandSettlementXVaPayout($payout);
        }
        else
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_ERROR);
        }

        $response = $this->create($request);
        $this->trace->info(TraceCode::INTERNAL_ENTITY_CREATED, $response);

        return $response;
    }

    public function failOnPayoutReversal(Payout\Entity $payout, array $params = []): array
    {
        $this->trace->info(TraceCode::INTERNAL_FAIL_ON_PAYOUT_REVERSAL_INPUT_DATA, [
            Payout\Entity::ID => $payout->getPublicId(),
            'params'          => $params,
        ]);

        // fetch internal entity from the utr
        $internal = $this->repo->internal->fetchByEntityIDAndType($payout->getId(), $payout->getEntity());
        if ($internal === null)
        {
            // throw exception
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INTERNAL_ENTITY_NOT_FOUND);
        }

        return $this->fail($internal->getId(), $params);
    }

    protected function createRequestForInterAccountTestPayout(Payout\Entity $payout)
    {
        $remarks = null;
        $beneBankName = null;
        $beneMerchantId = null;

        [$beneBankName, $beneMerchantId] = $this->getBeneBankNameAndMerchantIdIfBeneficiaryAccountIsWhitelistedForPayout($payout);

        if (empty($beneMerchantId) === true) {
            // throw exception
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INTERNAL_MERCHANT_NOT_FOUND);
        }
        // This remark will be used by ART team to differentiate between regular inter-nodal transfers and
        // inter account test payouts and help in reconciliation of the same.

        $remarks = self::TEST_PAYOUT_REMARK;

        return [
            Entity::AMOUNT => $payout->getAmount(),
            Entity::BASE_AMOUNT => $payout->getBaseAmount(),
            Entity::UTR => $payout->getUtr(),
            Entity::MODE => $payout->getMode(),
            Entity::ENTITY_ID => $payout->getId(),
            Entity::ENTITY_TYPE => $payout->getEntity(),
            Entity::BANK_NAME => $beneBankName,
            Entity::CURRENCY => $payout->getCurrency(),
            Entity::TYPE => self::TYPE_CREDIT,
            Entity::TRANSACTION_DATE => $payout->getUpdatedAt(),
            Entity::MERCHANT_ID => $beneMerchantId,
            Entity::REMARKS => $remarks,
        ];
    }

    protected function createRequestForInterAccountPayout(Payout\Entity $payout)
    {
        $remarks = null;
        $beneBankName = null;
        $beneMerchantId = null;

        list($beneBankName, $beneMerchantId) = $this->getBeneBankNameAndMerchantIdIfBeneficiaryAccountIsWhitelistedForPayout($payout);

        if (empty($beneMerchantId) === true) {
            // throw exception
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INTERNAL_MERCHANT_NOT_FOUND);
        }

        return [
            Entity::AMOUNT => $payout->getAmount(),
            Entity::BASE_AMOUNT => $payout->getBaseAmount(),
            Entity::UTR => $payout->getUtr(),
            Entity::MODE => $payout->getMode(),
            Entity::ENTITY_ID => $payout->getId(),
            Entity::ENTITY_TYPE => $payout->getEntity(),
            Entity::BANK_NAME => $beneBankName,
            Entity::CURRENCY => $payout->getCurrency(),
            Entity::TYPE => self::TYPE_CREDIT,
            Entity::TRANSACTION_DATE => $payout->getUpdatedAt(),
            Entity::MERCHANT_ID => $beneMerchantId,
        ];
    }

    protected function createRequestForOndemandSettlementXVaPayout(Payout\Entity $payout)
    {
        $remarks = self::ONDEMAND_SETTLEMENT_XVA_PAYOUT;
        $beneBankName = null;
        $beneMerchantId = null;

        list($beneBankName, $beneMerchantId) = $this->getBeneBankNameAndMerchantIdIfBeneficiaryAccountIsWhitelistedForPayout($payout);

        if (empty($beneMerchantId) === true) {
            // throw exception
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INTERNAL_MERCHANT_NOT_FOUND);
        }

        return [
            Entity::AMOUNT            => $payout->getAmount(),
            Entity::BASE_AMOUNT       => $payout->getBaseAmount(),
            Entity::UTR               => $payout->getUtr(),
            Entity::MODE              => $payout->getMode(),
            Entity::ENTITY_ID         => $payout->getId(),
            Entity::ENTITY_TYPE       => $payout->getEntity(),
            Entity::BANK_NAME         => $beneBankName,
            Entity::CURRENCY          => $payout->getCurrency(),
            Entity::TYPE              => self::TYPE_CREDIT,
            Entity::TRANSACTION_DATE  => $payout->getUpdatedAt(),
            Entity::MERCHANT_ID       => $beneMerchantId,
            Entity::REMARKS           => $remarks
        ];
    }
}
