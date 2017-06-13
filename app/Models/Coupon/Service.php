<?php

namespace RZP\Models\Coupon;

use Illuminate\Database\QueryException;

use RZP\Exception;
use RZP\Constants;
use RZP\Models\Base;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    public function __construct()
    {
        parent::__construct();

        $this->validator = new Validator;
    }

    public function create(array $input)
    {
        $this->trace->info(TraceCode::COUPON_CREATE_REQUEST, $input);

        $coupon = $this->core()->create($input);

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

        return $coupon->toArrayDeleted();
    }

    public function apply(array $input)
    {
        list($couponCode, $merchantId) = $this->parseInput($input);

        $coupon = $this->repo->coupon->fetchByCode($couponCode);

        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $result = $this->core()->apply($merchant, $coupon);

        return $result;
    }

    protected function parseInput(array $input)
    {
        $couponCode = $input[Entity::CODE];

        $merchantId = $input[Entity::MERCHANT_ID];

         $this->trace->info(
            TraceCode::COUPON_APPLY_REQUEST,
            [
                Entity::CODE        => $couponCode,
                Entity::MERCHANT_ID => $merchantId,
            ]);

        $this->validator->validateInput('apply', $input);

        return [$couponCode, $merchantId];
    }

    protected function isUsed(Entity $coupon): bool
    {
        $entity = $coupon->source()->firstOrFail();

        $entityNameSpace = Constants\Entity::getEntityNamespace(
                                $coupon->getEntityType()) . '\Core';

        return (new $entityNameSpace)->isUsed($entity);
    }
}
