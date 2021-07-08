<?php

namespace RZP\Models\Merchant\VerificationDetail;

use Mail;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Detail;


class Core extends Base\Core
{
    const VERIFICATION_DETAIL_CREATE_MUTEX_PREFIX = 'api_verification_detail_create_';

    public function createOrEditVerificationDetail(Detail\Entity $merchantDetails, $input)
    {
        return $this->repo->transactionOnLiveAndTest(function () use ($merchantDetails, $input) {

            $verificationDetail = $merchantDetails->verificationDetail;

            if ($verificationDetail === null)
            {
                $this->trace->info(
                    TraceCode::VERIFICATION_DETAIL_DOES_NOT_EXIST,
                    [
                        'merchant_id' => $merchantDetails->getMerchantId(),
                    ]
                );

                $input[Entity::MERCHANT_ID] = $merchantDetails->merchant->getId();

                $verificationDetail = $this->createVerificationDetail($merchantDetails, $input);

                $merchantDetails->setRelation(Detail\Entity::MERCHANT_VERIFICATION_DETAIL, $verificationDetail);
            }

            else
            {
                $verificationDetail->edit($input, 'edit');

                $this->repo->merchant_verification_detail->saveOrFail($verificationDetail);
            }

            return $verificationDetail;
        });
    }

    private function createVerificationDetail($merchantDetails, $input)
    {
        $mutexResource = self::VERIFICATION_DETAIL_CREATE_MUTEX_PREFIX . $merchantDetails->getMerchantId();

        return $this->app['api.mutex']->acquireAndRelease($mutexResource, function () use ($merchantDetails, $input) {

            $verificationDetail = new Entity;

            $verificationDetail->generateId();

            $this->trace->info(TraceCode::MERCHANT_CREATE_VERIFICATION_DETAILS, $input);

            $verificationDetail->build($input);

            $this->repo->merchant_verification_detail->saveOrFail($verificationDetail);

            return $verificationDetail;
        });
    }

}
