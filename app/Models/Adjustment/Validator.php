<?php

namespace RZP\Models\Adjustment;

use RZP\Base;
use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Models\Transaction;
use RZP\Models\Merchant\Balance;
use RZP\Models\Base\PublicEntity;
use RZP\Constants\Entity as ConstantEntity;
use RZP\Models\Dispute\Entity as DisputeEntity;
use RZP\Models\Merchant\Invoice as MerchantInvoice;
use RZP\Models\Settlement\Channel as SettlementChannel;

class Validator extends Base\Validator
{
    const FEES = 'fees';

    const SUB_BANKING_BALANCE_ADJUSTMENT_CREATE = 'sub_banking_balance_adjustment_create';

    const ADJUSTMENT_BETWEEN_BALANCES = 'adjustment_between_balances';

    // This is the maximum balance supported in reserve balance.
    // If user tries to add balance > 50,000 INR, this will throw exception and balance will not be added.
    // This limit is there for both reserve_primary and reserve_banking balance types.
    // However, there is no lower limit on reserve balance.
    // Also, in case the user wants to withdraw from his reserve balance,
    // we can support that by negative adjustment to reserve balance.

//    We are removing upper limit for Reserve balance
//    const MAX_RESERVE_BALANCE_AMOUNT = 50000000; //5,00,000 INR

    protected static $createRules = [
        Entity::AMOUNT        => 'required|integer',
        Entity::CHANNEL       => 'sometimes|string|max:32|custom',
        Entity::CURRENCY      => 'required|in:INR,MYR',
        Entity::DESCRIPTION   => 'required|min:10|max:255',
        Entity::SETTLEMENT_ID => 'sometimes|size:14',
    ];

    protected static $subBankingBalanceAdjustmentCreateRules = [
        Entity::AMOUNT        => 'required|integer',
        Entity::CHANNEL       => 'sometimes|string|max:32|custom',
        Entity::CURRENCY      => 'required|in:INR',
        Entity::DESCRIPTION   => 'required|min:10|max:255',
        Entity::BALANCE_ID    => 'required|size:14'
    ];

    protected static $adjustmentBetweenBalancesRules = [
        Entity::AMOUNT                 => 'required|integer',
        Entity::CHANNEL                => 'sometimes|string|max:32|custom',
        Entity::TYPE                   => 'required|in:banking',
        Entity::CURRENCY               => 'required|in:INR',
        Entity::DESCRIPTION            => 'required|min:10|max:255',
        Entity::MERCHANT_ID            => 'required|size:14',
        Entity::SOURCE_BALANCE_ID      => 'required|size:14',
        Entity::DESTINATION_BALANCE_ID => 'required|size:14'
    ];

    protected static $feeAdjustmentRules = [
        Entity::AMOUNT                 => 'sometimes|integer',
        MerchantInvoice\Entity::TAX    => 'sometimes|integer',
        Entity::CURRENCY               => 'required|in:INR',
        Entity::DESCRIPTION            => 'required|min:10|max:255',
        Validator::FEES                => 'sometimes|integer',
    ];

    // Payment id is actually a comma separated list of payment_ids
    // that add up to the adjustment amount
    protected static $splitAdjustmentRules = [
        Entity::ID                => 'required|alpha_num|size:14',
        DisputeEntity::PAYMENT_ID => 'required|string',
    ];

    public function validateAdjustmentCreateInput(array $input, Merchant\Entity $merchant)
    {
        // Throw exception when input currency is not same as merchant currency
        $this->validateCurrency($input, $merchant);

        // Presence of all three keys is not allowed
        if (isset($input[Entity::AMOUNT]) === true and
            isset($input[MerchantInvoice\Entity::TAX]) === true and
            isset($input['fees']) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Either amount OR tax/fees should be passed');
        }

        // Throw exception when none of the keys are present
        if (isset($input[Entity::AMOUNT]) === false and
            isset($input[MerchantInvoice\Entity::TAX]) === false and
            isset($input['fees']) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'At least one out of amount OR tax/fees should be passed');
        }

        $this->validateReserveBalance($input);
    }

    /**
     * This validation is strictly for checking Reserve Type adjustment.
     * If the type is not present or if it is not one of the reserve balance type, then we return.
     * Else, we validate the amount for Reserve balance.
     * Amount is mandatory in case of Reserve Balance Type
     *
     * @param array $input
     * @throws Exception\BadRequestValidationFailureException
     */
    private function validateReserveBalance(array $input)
    {
        if (isset($input[Balance\Entity::TYPE]) === false)
        {
            return;
        }

        $isReserveType = ($input[Entity::TYPE] === Balance\Type::RESERVE_PRIMARY) or
                            ($input[Entity::TYPE] === Balance\Type::RESERVE_BANKING);


        if ($isReserveType === true)
        {
            // Amount is mandatory in case of Reserve Balance Type
            if (isset($input[Entity::AMOUNT]) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Amount should be passed for reserve balance.');
            }
        }
    }

    /**
     * This validation if for checking currency while adding adjustment for a merchant.
     *
     * @param array $input
     * @param Merchant\Entity $merchant
     * @throws Exception\BadRequestValidationFailureException
     */
    private function validateCurrency(array $input, Merchant\Entity $merchant)
    {
        if ($input[Entity::CURRENCY] != $merchant->getCurrency()) {
            throw new Exception\BadRequestValidationFailureException(
                'Currency should be same as merchant currency'
            );
        }
    }

    /**
     * Validate merchant balance before an adjustment is processed
     *
     * @param Merchant\Entity $merchant
     * @param PublicEntity    $entity
     * @param array           $input
     *
     * @throws Exception\BadRequestException
     */
    public function validateMerchantBalance(Merchant\Entity $merchant,
                                            PublicEntity $entity,
                                            array $input)
    {
        if($entity->getEntityName() === ConstantEntity::DISPUTE)
        {
            return;
        }

        if ((isset($input[Entity::AMOUNT]) === false) or
            $input[Entity::AMOUNT] > 0)
        {
            return;
        }

        $amountToBeDeducted = abs($input[Entity::AMOUNT]);

        try
        {
            (new Merchant\Balance\Core)->checkMerchantBalance($merchant,
                -1 * $amountToBeDeducted, Transaction\Type::DISPUTE);
        }
        catch (\Exception $e)
        {
            $traceData = [
                'message'               => TraceCode::getMessage(TraceCode::MERCHANT_BALANCE_DEBIT_FAILURE),
                'adjustment_amount'     => $amountToBeDeducted,
                'entity_type'           => $entity->getEntityName(),
                'entity_id'             => $entity->getId(),
            ];

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INSUFFICIENT_BALANCE_FOR_ADJUSTMENT,
                $traceData);
        }
    }

    protected function validateChannel($attribute, $channel)
    {
        SettlementChannel::validate($channel);
    }
}
