<?php

namespace RZP\Models\Coupon;

use RZP\Exception;
use RZP\Constants;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    public function __construct()
    {
        parent::__construct();
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
        $coupon = $this->repo->coupon->fetchByCode($input);

        if ($coupon === null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_COUPON_CODE, $input);
        }

        $merchant = $this->repo->merchant->findOrFailPublic($input[Entity::MERCHANT_ID]);

        $result = $this->core()->apply($merchant, $coupon);

        return $result;
    }

    //This will check if the entity is used for any merchant
    protected function isUsed(Entity $coupon): bool
    {
        $entity = $coupon->source()->firstOrFail();

        $ns = Constants\Entity::getEntityNamespace($coupon->getEntityType());

        $coreClass = $ns . '\Core';

        $core = (new $coreClass);

        return $core->isUsed($entity);
    }
}
