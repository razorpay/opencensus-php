<?php

namespace RZP\Models\Growth;

use Mail;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Mail\Growth\PricingBundle;
use RZP\Models\Base;
use RZP\Models\Growth\BundleFee\Entity;
use RZP\Models\Ledger\Constants as LedgerConstants;
use RZP\Models\Merchant\Credits;
use RZP\Models\Merchant;
use RZP\Models\Transaction;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    public function createBundleFeeTransaction(array $input)
    {
        $this->trace->info(TraceCode::GROWTH_TRANSACTION_CREATE_REQUEST, $input);
        (new Validator)->validateInput('create_internal_transaction', $input);

        app('request.ctx')->setLedgerDualWriteFlow(true);

        $transactorIdArr = explode('_', $input[Constants::TRANSACTOR_ID]);

        if(count($transactorIdArr) != 2)
        {
            $this->trace->debug(
                TraceCode::INVALID_TRANSACTOR_ID,
                [
                    LedgerConstants::MESSAGE        => "provide a valid public ID for transactor",
                    LedgerConstants::TRANSACTOR_ID  => $input[Constants::TRANSACTOR_ID]
                ]);

            throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_TRANSACTOR_ID);
        }

        $publicIdPrefix = $transactorIdArr[0];
        $entityId = $transactorIdArr[1];

        return $this->app['api.mutex']->acquireAndRelease($entityId, function () use ($input, $publicIdPrefix, $entityId) {

            return $this->repo->transaction(function () use ($input, $publicIdPrefix, $entityId) {
                $merchant = $this->repo->merchant->findOrFail($input[Constants::MERCHANT_ID]);
                $txn = $this->repo->transaction->findByEntityId($entityId, $merchant);

                // if transaction already exists, return
                if (isset($txn) === true)
                {
                    return $txn;
                }

                if ($publicIdPrefix == 'bundfee')
                {
                    $bundleFee = new Entity;
                    $bundleFeeInput = [
                        Entity::ID          => $entityId,
                        Entity::BASE_AMOUNT => $input[Constants::AMOUNT],
                        Entity::CURRENCY    => $input[Constants::CURRENCY],
                        Entity::AMOUNT      => $input[Constants::AMOUNT],
                        Entity::MERCHANT_ID => $input[Constants::MERCHANT_ID],
                        Entity::IS_REVERSAL => $input[Constants::IS_REVERSAL],
                    ];
                    $bundleFee->fill($bundleFeeInput);
                    $bundleFee->merchant()->associate($merchant);

                    $txnCore = new Transaction\Core;

                    list($txn, $feeSplit) = $txnCore->createTransactionForSource($bundleFee, $input[Constants::JOURNAL_ID]);

                    $this->repo->saveOrFail($txn);

                    $txnCore->saveFeeDetails($txn, $feeSplit);

                    $this->trace->info(TraceCode::GROWTH_TRANSACTION_CREATED,
                        [
                            'bundle_fee_id'        => $bundleFee->getId(),
                            'transaction_id'    => $txn->getId(),
                        ]);

                    return $txn;
                }

                throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_TRANSACTOR_ID);
            });
        });
    }

    public function sendPricingBundleEmail(array $input)
    {
        (new Validator)->validateInput('send_pricing_bundle_email', $input);

        $merchant = $this->repo->merchant->findOrFail($input[Constants::MERCHANT_ID]);

        $data = $input['data'];
        $data['merchant'] = $merchant->toArrayPublic();

        switch ($input[Constants::TYPE])
        {
            case Constants::PAYMENT_SUCCESS:
                $mail = new PricingBundle\PaymentSuccess($data);
                break;
            case Constants::PAYMENT_FAILURE:
                $mail = new PricingBundle\PaymentFailure($data);
                break;
            case Constants::WELCOME:
                $mail = new PricingBundle\Welcome($data, $input[Constants::PACKAGE_NAME]);
                break;
            case Constants::PLAN_UPDATED:
                $mail = new PricingBundle\PlanUpdated($data);
                break;
            default:
                $mail = new PricingBundle\Email($data, $input);
        }
        Mail::send($mail);

        return [
            'data' => $data,
        ];
    }

    public function addAmountCredits(array $input)
    {
        (new Validator)->validateInput('add_amount_credits', $input);

        return (new Credits\Service)->grantCreditsForMerchant($input[Constants::MERCHANT_ID], [
            'value' => $input[Constants::AMOUNT],
            'type' => Constants::AMOUNT,
            'campaign' => $input[Constants::CAMPAIGN_NAME],
            'expired_at' => $input[Constants::EXPIRED_AT],
        ]);
    }

    public function editAmountCredits(array $input)
    {
        (new Validator)->validateInput('edit_amount_credits', $input);

        return (new Credits\Service)->updateCreditsLog($input[Constants::MERCHANT_ID], $input[Constants::ID], ['value' => $input[Constants::AMOUNT]]);
    }

    /**
     * @throws BadRequestException
     */
    public function assignPricingRuleToMerchant(array $params) {

        (new Validator)->validateInput('assign_pricing_plan', $params);
        $input = [
            'pricing_plan_id' => $params['pricing_plan_id'],
            'caller' => 'pricing_bundle',
            'spr_approved' => true,
        ];
        return (new Merchant\Service)->assignPricingPlan($params['merchant_id'], $input);
    }

}
