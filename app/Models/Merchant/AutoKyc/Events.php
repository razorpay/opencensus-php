<?php

namespace RZP\Models\Merchant\AutoKyc;

use RZP\Models\Base;
use RZP\Diag\EventCode;
use RZP\Models\Merchant\Detail\Constants;
use RZP\Models\Merchant\Document\Constants as DocumentConstant;

class Events extends Base\Core
{

    public function sendServiceVerifierEvents(array $data)
    {
        $eventAttribute = [
            Constants::RESPONSE_TIME => $data[Constants::RESPONSE_TIME] ?? null,
            Constants::STATUS_CODE   => $data[Constants::STATUS_CODE] ?? null,
            Constants::DOCUMENT_TYPE => $data[Constants::DOCUMENT_TYPE] ?? '',
        ];

        $this->app['diag']->trackOnboardingEvent(EventCode::KYC_VERIFIER_SERVICE_RESPONSE_TIME,
                                                 $this->merchant,
                                                 null,
                                                 $eventAttribute);
    }

    public function sendVerificationEvents(array $eventData, array $response, array $verificationData)
    {
        $this->sendServiceVerifierEvents($response);

        $this->app['diag']->trackOnboardingEvent($eventData,
                                                 $this->merchant,
                                                 null,
                                                 $verificationData);
    }

    /**
     * Currently for unregistered merchant we are sending ocr event , till migration is complete
     * we need to send this event . One prod analytics team stop using this then we can remove this .
     *
     * @param array $response
     * @param array $verificationData
     */
    public function sendPOAEvents(array $response, array $verificationData)
    {
        $documentType = $verificationData[Constants::DOCUMENT_TYPE] ?? '';

        $comparisionData = $verificationData[Constants::COMPARISION][$documentType] ?? [];

        $comparisionData = $comparisionData[0] ?? [];

        $eventProperties = [
            Constants::DOCUMENT_TYPE                  => $documentType,
            Constants::API_CALL_SUCCESSFUL            => $verificationData[Constants::API_CALL_SUCCESSFUL] ?? '',
            Constants::VERIFIED                       => $verificationData[Constants::VERIFIED] ?? '',
            DocumentConstant::OCR_NAME                => $response[Constants::NAME] ?? null,
            DocumentConstant::OCR_MATCHING_PERCENTAGE => $comparisionData[Constants::MATCH_PERCENTAGE] ?? 0,
            DocumentConstant::OCR_MATCH_TYPE          => $comparisionData[Constants::MATCH_TYPE] ?? null,
            DocumentConstant::OCR_MATCHING_THRESHOLD  => $comparisionData[Constants::MATCH_THRESHOLD] ?? '',
            Constants::PROMOTER_PAN_NAME              => $comparisionData[Constants::DETAILS_FROM_USER] ?? '',
        ];

        $this->app['diag']->trackOnboardingEvent(EventCode::DOCUMENT_VERIFICATION_OCR, $this->merchant, null, $eventProperties);

    }

}
