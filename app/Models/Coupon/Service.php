<?php

namespace RZP\Models\Coupon;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    public function create(array $input): array
    {
        $this->trace->info(TraceCode::COUPON_CREATE_REQUEST, $input);

        $coupon = $this->core()->create($input);

        return $coupon->toArrayAdmin();
    }

    public function delete(string $id): array
    {
        $this->trace->info(TraceCode::COUPON_DELETE_REQUEST, ['coupon_id' => $id]);

        // TODO discuss
        $coupon = $this->repo->coupon->findOrFailPublic($id);

        if ($coupon->getUsedCount() > 0)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Deleting a used coupon is not allowed');
        }

        $this->repo->deleteOrFail($coupon);

        return $coupon->toArrayDeleted();
    }

    public function apply(array $input): array
    {
        $this->trace->info(TraceCode::COUPON_APPLY_REQUEST, $input);

        (new Validator)->validateInput('apply', $input);

        $merchantId = $input[Entity::MERCHANT_ID];

        $coupon = $this->repo->coupon->fetchByCodeWithRelations($input[Entity::CODE], $merchantId);

        if ($coupon === null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_COUPON_CODE, $input);
        }

        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $result = $this->core()->apply($merchant, $coupon);

        return $result;
    }
}
