<?php

namespace RZP\Models\Merchant\VerificationDetail;

use Mail;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Constants as MerchantConstants;
use RZP\Http\Controllers\MerchantOnboardingProxyController;

class Core extends Base\Core
{
    const VERIFICATION_DETAIL_CREATE_MUTEX_PREFIX = 'api_verification_detail_create_';

    public function createOrEditVerificationDetail(Detail\Entity $merchantDetails, $input)
    {
        return $this->repo->transactionOnLiveAndTest(function() use ($merchantDetails, $input) {

            $merchantId = $merchantDetails->getMerchantId();

            $verificationDetail = $this->repo->merchant_verification_detail->getDetailsForTypeAndIdentifier(
                $merchantId,
                $input[Entity::ARTEFACT_TYPE],
                $input[Entity::ARTEFACT_IDENTIFIER]
            );

            if ($verificationDetail === null)
            {
                $this->trace->info(
                    TraceCode::VERIFICATION_DETAIL_DOES_NOT_EXIST,
                    [
                        'merchant_id' => $merchantId,
                    ]
                );

                $input[Entity::MERCHANT_ID] = $merchantId;

                $verificationDetail = $this->createVerificationDetail($merchantDetails, $input);

                $merchantDetails->setRelation(Detail\Entity::MERCHANT_VERIFICATION_DETAIL, $verificationDetail);
            }

            else
            {
                if (empty($input[Entity::METADATA]) === false)
                {
                    $input[Entity::METADATA] = $this->mergeJson($verificationDetail->getMetadata(), $input[Entity::METADATA]);
                }

                $verificationDetail->edit($input, 'edit');

                $this->repo->merchant_verification_detail->saveOrFail($verificationDetail);
            }

            return $verificationDetail;
        });
    }

    private function createVerificationDetail($merchantDetails, $input)
    {
        $mutexResource = self::VERIFICATION_DETAIL_CREATE_MUTEX_PREFIX . $merchantDetails->getMerchantId();

        return $this->app['api.mutex']->acquireAndRelease($mutexResource, function() use ($merchantDetails, $input) {

            $verificationDetail = new Entity;

            $verificationDetail->generateId();

            $this->trace->info(TraceCode::MERCHANT_CREATE_VERIFICATION_DETAILS, $input);

            $verificationDetail->build($input);

            $this->repo->merchant_verification_detail->saveOrFail($verificationDetail);

            return $verificationDetail;
        });
    }

    public function mergeJson($existingDetails, $newDetails)
    {
        if (empty($newDetails) === false)
        {
            foreach ($newDetails as $key => $value)
            {
                $existingDetails[$key] = $value;
            }
        }

        return $existingDetails;
    }

    public function savePGOSDataToAPI(array $data)
    {
        $splitzResult = (new Detail\Core)->getSplitzResponse($data[Entity::MERCHANT_ID], 'pgos_migration_dual_writing_exp_id');

        if ($splitzResult === 'variables')
        {
            $merchant = $this->repo->merchant->find($data[Entity::MERCHANT_ID]);

            // dual write only for below merchants
            // merchants for whom pgos is serving onboarding requests
            // merchants who are not completely activated
            if ($merchant->getService() === MerchantConstants::PGOS and
                $merchant->merchantDetail->getActivationStatus()!=Detail\Status::ACTIVATED)
            {
                $verification = (new Repository())->getDetailsForTypeAndIdentifier($data[Entity::MERCHANT_ID],
                                                                                   $data[Entity::ARTEFACT_TYPE],
                                                                                   $data[Entity::ARTEFACT_IDENTIFIER]
                );

                if (empty($verification) === false)
                {
                    $verification->edit($data);

                    $this->repo->saveOrFail($verification);
                }
                else
                {
                    $verification = new Entity;

                    $verification->generateId();

                    $verification->build($data);

                    $this->repo->merchant_verification_detail->saveOrFail($verification);

                }
            }
        }
    }
}
