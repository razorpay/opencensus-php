<?php

namespace RZP\Models\Coupon;

use Illuminate\Database\QueryException;

use RZP\Models\Base;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;
use RZP\Exception;
use RZP\Models\Merchant\Credits;
use RZP\Constants;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant\Promotions as MerchantPromotion;
use Razorpay\Spine\Exception\DbQueryException;

class Service extends Base\Service
{
    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;

        $this->validator = new Validator;
    }

    public function create(array $input)
    {
        $this->trace->info(TraceCode::COUPON_CREATE_REQUEST, $input);

        $coupon = $this->core->create($input);

        return $coupon->toArrayAdmin();
    }

    public function fetchMultiple(array $input)
    {
        $coupons = $this->repo->coupon->fetch($input);

        return $coupons->toArrayAdmin();
    }

    public function delete(string $id)
    {
        $this->trace->info(TraceCode::COUPON_DELETE_REQUEST, ['coupon_id' => $id]);

        $coupon = $this->repo->coupon->findOrFailPublic($id);

        if ($this->isUsed($coupon) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Deleting a used coupon is not allowed');
        }

        $this->repo->coupon->deleteOrFail($coupon);

        $this->trace->info(TraceCode::COUPON_DELETED, $coupon->toArray());

        return $coupon->toArrayDeleted();
    }

    protected function parseInput(array $input)
    {
        $this->validator->validateInput('apply', $input);

        $couponCode = $input['code'];

        $merchantId = $input['merchant_id'];

        $this->trace->info(
            TraceCode::COUPON_APPLY_REQUEST,
            [
                'code' => $couponCode,
                'merchant_id' => $merchantId,
            ]);

        return [$couponCode, $merchantId];
    }

    protected function isUsed($coupon)
    {
        $entity = $coupon->source()->firstOrFail();

        $entityNameSpace = Constants\Entity::getEntityNamespace(
                                $coupon->getEntityType()) . '\Core';

        return (new $entityNameSpace)->isUsed($entity);
    }

    //TODO add typehinting
    protected function validateAndApplyMerchantPromotion($merchant, $coupon)
    {
        $result = [
            'success'           => true,
            'error_description' => '',
        ];

        try
        {
            $promotion = $coupon->source()->firstOrFail();

            $merchantPromotion = $this->repo->merchant_promotion->findByMerchantAndPromotionId(
                                    $merchant->getId(), $promotion->getId());

            if ($merchantPromotion !== null)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_COUPON_ALREADY_USED);
            }

            $this->validator->couponApplyValidator($coupon, $merchant);

            $this->repo->transaction(function() use ($merchant, $promotion, $coupon)
            {
                $merchantPromotionCore = (new MerchantPromotion\Core);

                $merchantPromotion = $merchantPromotionCore->create($merchant, $promotion);

                // Initial apply of Credit is done instantly
                // Subsequent run and expiry will be handled by cron
                $merchantPromotionCore->applyCredits($merchant, $promotion);

                $coupon->setUsedCount($coupon->getUsedCount() + 1);

                $merchantPromotion->updateRemainingRuns();

                $this->repo->saveOrFail($coupon);

                $this->repo->saveOrFail($merchantPromotion);
            });

            $result = [
                'success' => true,
                'error_description' => '',
            ];
        }
        catch (QueryException $e)
        {
            //TODO move to constants
            $result = [
                'success'           => false,
                'error_description' => 'Coupon Already Applied/Could Not be Applied',
            ];
        }
        catch (\Exception $e)
        {
            $result = [
                'success'            => false,
                'error_description' => $e->getMessage(),
            ];
        }

        return $result;
    }

    public function apply(array $input)
    {
        try
        {
            list($couponCode, $merchantId) = $this->parseInput($input);
        }
        catch (\Exception $e)
        {
            return [
                'success'           => false,
                'error_description' => 'Invalid Request',
            ];
        }

        try
        {
            $coupon = $this->repo->coupon->fetchByCode($couponCode);
        }
        catch (\Exception $e)
        {
            return [
                'success'           => false,
                'error_description' => 'Invalid Coupon Code',
            ];
        }

        try
        {
            $merchant = $this->repo->merchant->findOrFailPublic($merchantId);
        }
        catch (\Exception $e)
        {
            return [
                'success'           => false,
                'error_description' => 'Invalid Merchant',
            ];
        }

        $result = $this->validateAndApplyMerchantPromotion(
            $merchant,
            $coupon);

        return $result;
    }
}
