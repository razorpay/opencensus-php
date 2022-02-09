<?php

namespace RZP\Models\Coupon;

use App;
use Throwable;
use RZP\Models\Admin\Org\Entity as Org;
use RZP\Models\Merchant\Store\ConfigKey as StoreConfigKey;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Coupon;
use RZP\Diag\EventCode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Store;

class Service extends Base\Service
{
    public function create(array $input): array
    {
        $this->trace->info(TraceCode::COUPON_CREATE_REQUEST, $input);

        $coupon = $this->core()->create($input);

        return $coupon->toArrayAdmin();
    }


    /**
     * @param string $id
     * @param array $input
     * @return mixed
     */
    public function update(string $id, array $input)
    {
        $this->trace->info(TraceCode::COUPON_UPDATE_REQUEST, $input);

        $coupon = $this->repo->coupon->findOrFailPublic($id);

        $coupon = $this->core()->update($coupon, $input);

        return $coupon->toArrayAdmin();
    }


    public function delete(string $id): array
    {
        $this->trace->info(TraceCode::COUPON_DELETE_REQUEST, ['coupon_id' => $id]);

        $coupon = $this->repo->coupon->findOrFailPublic($id);

        if ($coupon->getUsedCount() > 0)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Deleting a used coupon is not allowed');
        }

        $this->repo->deleteOrFail($coupon);

        return $coupon->toArrayDeleted();
    }

    /**
     * Function will validate Coupon and return its respective error message if validation fails.
     * If all validation passes it returns coupon's expire days and credit amount.
     * @param array $input
     * @return array
     * @throws Throwable
     */
    public function validateCouponAndGetDetails(array $input): array
    {
        $this->trace->info(TraceCode::COUPON_VALIDATE_REQUEST, $input);

        $this->trace->count(Merchant\Metric::COUPON_VALIDATE_TOTAL);

        $merchant = app('basicauth')->getMerchant();

        $coupon = $this->validateCouponAndSendFailureEventsIfApplicable($merchant, $input);

        $promotion = $coupon->source;

        //Here expireDays will return null if Schedule doesn't exist. (Credit will exist indefinitely.)
        $expireDays = null;

        if ($promotion->schedule !== null)
        {
            $expireDays = $promotion->schedule->getInterval();
        }

        return [
            'expire_days'   => $expireDays,
            'credit_amount' => $promotion->getCreditAmount(),
        ];
    }

    public function validateCouponAndSendFailureEventsIfApplicable(Merchant\Entity $merchant, array $input)
    {
        try
        {
            (new Validator)->validateInput('apply', $input);

            return $this->core()->validateAndGetDetails($merchant, $input,false);
        }
        catch (Throwable $exception)
        {
            $code = $input[Entity::CODE] ?? '';

            $this->app['diag']->trackOnboardingEvent(EventCode::SIGNUP_APPLY_COUPON_CODE_FAILED, $merchant, $exception, [Entity::COUPON_CODE => $code]);

            throw  $exception;
        }
    }

    public function apply(array $input): array
    {
        $this->trace->info(TraceCode::COUPON_APPLY_REQUEST, $input);

        (new Validator)->validateInput('apply', $input);

        $merchant = null;

        if (app('basicauth')->isAdminAuth() === false)
        {
            $merchant = app('basicauth')->getMerchant();
        }
        else
        {
            $merchantId = $input[Entity::MERCHANT_ID] ?? null;

            $merchant = $this->repo->merchant->findOrFailPublic($merchantId);
        }

        $result = $this->core()->apply($merchant, $input,false);

        return $result;
    }
}
