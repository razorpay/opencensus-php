<?php

namespace RZP\Models\Adjustment;

use RZP\Base;
use RZP\Models\Merchant;
use RZP\Models\Dispute\Entity as DisputeEntity;
use RZP\Models\Merchant\Invoice as MerchantInvoice;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    const FEES = 'fees';

    protected static $createRules = [
        Entity::AMOUNT        => 'required|integer',
        Entity::CURRENCY      => 'required|in:INR',
        Entity::DESCRIPTION   => 'required|min:10|max:255',
        Entity::SETTLEMENT_ID => 'sometimes|size:14',
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

    public function validateAdjusmentCreateInput(array $input)
    {
        // Presence of all three keys is not allowed
        if (isset($input[Entity::AMOUNT]) === true and
            isset($input[MerchantInvoice\Entity::TAX]) === true and
            isset($input['fees']) === true)
        {
            throw new BadRequestValidationFailureException('Either amount OR tax/fees should be passed');
        }

        // Throw exception when none of the keys are present
        if (isset($input[Entity::AMOUNT]) === false and
            isset($input[MerchantInvoice\Entity::TAX]) === false and
            isset($input['fees']) === false)
        {
            throw new BadRequestValidationFailureException('Atleast one out of amount OR tax/fees should be passed');
        }
    }

    /**
     * Validate merchant balance before an adjustment is processed
     *
     * @param Merchant\Entity $merchant
     * @param string $entityType
     * @param string $entityId
     * @param array $input
     * @throws BadRequestValidationFailureException
     */
    public function validateMerchantBalanceForAdjustment(Merchant\Entity $merchant,
                                                         string $entityType,
                                                         string $entityId,
                                                         array $input)
    {
        if (isset($input[Entity::AMOUNT]) and
            $input[Entity::AMOUNT] < 0)
        {
            $amount = $input[Entity::AMOUNT];

            $balance = (new Merchant\Balance\Repository)->getMerchantBalance($merchant);

            if (($balance->getBalance() + $amount) < 0)
            {
                $traceData = [
                    'message'               => 'Not enough balance',
                    'merchant_balance'      => $balance->getBalance(),
                    'adjustment_amount'     => $amount,
                    'entity_type'           => $entityType,
                    'entity_id'             => $entityId,
                ];

                throw new BadRequestValidationFailureException('Merchant does not have enough balance for negative adjustment.',
                    Entity::AMOUNT,
                    $traceData);
            }
        }
    }
}
