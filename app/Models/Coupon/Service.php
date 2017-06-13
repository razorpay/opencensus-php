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
                'code'        => $couponCode,
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


    public function apply(array $input)
    {
        list($couponCode, $merchantId) = $this->parseInput($input);

        $coupon = $this->repo->coupon->fetchByCode($couponCode);

        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $result = $this->core->apply($merchant, $coupon);

        return $result;
    }
}
