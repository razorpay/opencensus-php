<?php

namespace RZP\Models\SubVirtualAccount;

use Razorpay\Trace\Logger;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Settlement;
use RZP\Models\Adjustment;
use RZP\Models\CreditTransfer as CT;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\Balance\Type as BalanceType;
use RZP\Models\Merchant\Balance\Entity as BalanceEntity;

class Transfer extends Base\Core
{
    public function transferToSubMerchantUsingAdjustments(Entity $subVirtualAccount, $input)
    {
        $this->trace->info(TraceCode::SUB_VIRTUAL_ACCOUNT_TRANSFER_VIA_ADJUSTMENTS_INIT,
                           [
                               'input'               => $input,
                               'sub_virtual_account' => $subVirtualAccount->toArray()
                           ]);

        $masterMerchant = $subVirtualAccount->masterMerchant;

        $subMerchant = $subVirtualAccount->subMerchant;

        $masterBalance = $subVirtualAccount->balance;

        $amount = $input[Entity::AMOUNT];

        if ($masterBalance->getBalance() < $amount) {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_SUB_VIRTUAL_ACCOUNT_TRANSFER_NOT_ENOUGH_BANKING_BALANCE,
                null,
                [
                    Entity::MASTER_MERCHANT_ID => $this->merchant->getId(),
                ]
            );
        }

        $masterDescription =  Constants::ADJUSTMENT_DESCRIPTION_PREFIX . ' to ' . $input[Entity::SUB_ACCOUNT_NUMBER];

        $subDescription = Constants::ADJUSTMENT_DESCRIPTION_PREFIX . ' from ' . $input[Entity::MASTER_ACCOUNT_NUMBER];

        $masterAdjInput = [
            Entity::AMOUNT => -$amount,
            Entity::DESCRIPTION => $masterDescription,
            BalanceEntity::TYPE => BalanceType::BANKING,
            Entity::CURRENCY => $input[Entity::CURRENCY] ?? 'INR',
        ];

        $subAdjInput = [
            Entity::AMOUNT => $amount,
            Entity::DESCRIPTION => $subDescription,
            BalanceEntity::TYPE => BalanceType::BANKING,
            Entity::CURRENCY => $input[Entity::CURRENCY] ?? 'INR',
        ];

        $masterAdjEntity = $this->repo->transaction(function() use (
            $masterAdjInput,
            $masterMerchant,
            $subAdjInput,
            $subMerchant
        ) {
            $masterAdjEntity = (new Adjustment\Core())->createAdjustment($masterAdjInput, $masterMerchant);

            $subAdjEntity = (new Adjustment\Core())->createAdjustment($subAdjInput, $subMerchant);

            $this->trace->info(
                TraceCode::SUB_VIRTUAL_ACCOUNT_TRANSFER_ADJUSTMENT_RESPONSE,
                [
                    Entity::MASTER_ADJUSTMENT_ID => $masterAdjEntity->getId(),
                    Entity::SUB_ADJUSTMENT_ID    => $subAdjEntity->getId(),
                ]);

            return $masterAdjEntity;
        });

        $this->trace->info(TraceCode::SUB_VIRTUAL_ACCOUNT_TRANSFER_USING_ADJUSTMENTS_SUCCESS,
                           [
                               Entity::MASTER_ADJUSTMENT_ENTITY => $masterAdjEntity->toArrayPublic(),
                           ]);

        return $masterAdjEntity;
    }

    /*
     * 1. Create transfer payload
     * 2. Crete credit transfer to the sub merchant
     */
    public function transferToSubAccountUsingCreditTransfer(Entity $subVirtualAccount, $input)
    {
        $subMerchant = $subVirtualAccount->subMerchant;

        $this->trace->info(TraceCode::SUB_VIRTUAL_ACCOUNT_TRANSFER_USING_CREDIT_TRANSFER,
                           [
                               'input'                 => $input,
                               'sub_merchant_id'       => $subMerchant->getId(),
                               'sub_merchant_features' => $subMerchant->getEnabledFeatures()
                           ]);

        $CTRequest = $this->createCreditTransferInputForSubAccountTransfer($subVirtualAccount, $input);

        $subBalance = $subMerchant->sharedBankingBalance;

        try
        {
            $creditTransfer = (new CT\Core())->createCreditTransferForSubAccount($CTRequest, $subBalance);
        }
        catch (\Throwable $ex)
        {
            $this->trace->count(Metric::SUB_MERCHANT_LIMIT_ADDITION_EXCEPTIONS_TOTAL,
                                [
                                    Entity::SUB_MERCHANT_ID => $subMerchant->getId(),
                                ]);

            $this->trace->traceException(
                $ex,
                Logger::ALERT,
                TraceCode::SUB_ACCOUNT_TRANSFER_LIMIT_ADDITION_EXCEPTION,
                [
                    'credit_transfer_request' => $CTRequest,
                    'sub_merchant_balance'    => $subBalance->getId(),
                ]
            );

            throw $ex;
        }

        $this->trace->info(TraceCode::SUB_VIRTUAL_ACCOUNT_TRANSFER_USING_CREDIT_TRANSFER_PROCESSED,
                           [
                               'credit_transfer' => $creditTransfer->toArray(),
                           ]);

        return $creditTransfer;
    }

    public function createCreditTransferInputForSubAccountTransfer(Entity $subVirtualAccount, $transferInput)
    {
        $subAccountTransferDescription = $this->getSubAccountTransferDescription($subVirtualAccount);

        return [
            CT\Entity::AMOUNT            => (int) $transferInput[Entity::AMOUNT],
            CT\Entity::CURRENCY          => $transferInput[Entity::CURRENCY],
            CT\Entity::DESCRIPTION       => $subAccountTransferDescription,
            CT\Entity::CHANNEL           => Settlement\Channel::RZPX,
            CT\Entity::MODE              => \RZP\Models\Payout\Mode::IFT,
            CT\Entity::PAYER_MERCHANT_ID => $subVirtualAccount->getMasterMerchantId(),
            CT\Entity::PAYER_NAME        => $subVirtualAccount->masterMerchant->getDisplayNameElseName()
        ];
    }

    /*
     * https://razorpay.slack.com/archives/C022TEXUCMV/p1674462780919489?thread_ts=1674452957.013139&cid=C022TEXUCMV
     */
    public function getSubAccountTransferDescription(Entity $subVirtualAccount)
    {
        $masterMerchantId   = $subVirtualAccount->getMasterMerchantId();
        $masterMerchantName = $subVirtualAccount->masterMerchant->getDisplayNameElseName();

        $subMerchantName          = $subVirtualAccount->subMerchant->getDisplayNameElseName();
        $subMerchantAccountNumber = $subVirtualAccount->getSubAccountNumber();

        return sprintf(Constants::CREDIT_TRANSFER_DESCRIPTION,
                       $masterMerchantName,
                       $masterMerchantId,
                       $subMerchantName,
                       $subMerchantAccountNumber);
    }
}
