<?php

namespace RZP\Models\Coupon;

use RZP\Models\Base;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Credits;
use RZP\Models\Merchant\Promotions as MerchantPromotion;
use Illuminate\Database\QueryException;

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

    public function apply(array $input)
    {
        $result = [
            'success'           => true,
            'error_description' => '',
        ];

        try
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

            $coupon = $this->repo->coupon->fetchByCode($couponCode);

            $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

            $promotion = $coupon->source()->firstOrFail();

            $this->validator->couponApplyValidator($coupon, $merchantId);

            $this->repo->transaction(function() use ($merchant, $promotion)
            {
                $merchantPromotion = (new MerchantPromotion\Core);

                $merchantPromotion->create($merchant, $promotion);

                // Initial apply of Credit is done instantly
                // Subsequent run and expiry will be handled by cron
                $merchantPromotion->applyCredits($merchant, $promotion);
            });
        }
        catch (QueryException $e)
        {
            //TODO move to constants
            $result = [
                'success'           => false,
                'error_description' => 'Coupon Already Applied'
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
