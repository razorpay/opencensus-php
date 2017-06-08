<?php

namespace RZP\Models\Coupon;

use RZP\Models\Base;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Credits;
use RZP\Models\Merchant\Promotions as MerchantPromotion;
use Illuminate\Database\QueryException;
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

        $this->repo->coupon->deleteOrFail($coupon);

        $this->trace->info(TraceCode::COUPON_DELETED, $coupon->toArray());

        return $coupon->toArrayAdmin();
    }

    protected function parseInput(array $input)
    {
        $this->validator->validateInput('apply', $input);

        $couponCode = $input['coupon_code'];

        $merchantId = $input['merchant_id'];

        $this->trace->info(
            TraceCode::COUPON_APPLY_REQUEST,
            [
                'coupon_code' => $couponCode,
                'merchant_id' => $merchantId,
            ]);

        return [$couponCode, $merchantId];
    }

    public function apply(array $input)
    {
        $result = [
            'success'           => true,
            'error_description' => '',
        ];

        try
        {
            list($couponCode, $merchantId) = $this->parseInput($input);
        }
        catch (\Exception $e)
        {
            $result = [
                'success' => false,
                'error_description' => 'Invalid Request',
            ];

            return $result;
        }

        try
        {
            $coupon = $this->repo->coupon->fetchByCode($couponCode);
        }
        catch (\Exception $e)
        {
            $result = [
                'success' => false,
                'error_description' => 'Invalid Coupon Code',
            ];

            return $result;
        }

        try
        {
            $merchant = $this->repo->merchant->findOrFailPublic($merchantId);
        }
        catch (\Exception $e)
        {
            $result = [
                'success' => false,
                'error_description' => 'Invalid Merchant',
            ];

            return $result;
        }

        try
        {
            $promotion = $coupon->source()->firstOrFail();

            $this->validator->couponApplyValidator($coupon, $merchantId);

            $this->repo->transaction(function() use ($merchant, $promotion, $coupon)
            {
                $merchantPromotion = (new MerchantPromotion\Core);

                $merchantPromotion->create($merchant, $promotion);

                // Initial apply of Credit is done instantly
                // Subsequent run and expiry will be handled by cron
                $merchantPromotion->applyCredits($merchant, $promotion);

                $coupon->setUsedCount($coupon->getUsedCount() + 1);

                $this->repo->saveOrFail($coupon);
            });
        }
        catch (QueryException $e)
        {
            //TODO move to constants
            $result = [
                'success'           => false,
                'error_description' => 'Coupon Already Applied/Could Not be Applied'
            ];
        }
        catch (\Exception $e)
        {
            $result = [
                'success'           => false,
                'error_description' => $e->getMessage()
            ];
        }

        return $result;
    }
}
