<?php

namespace RZP\Models\Payout;

use App;
use RZP\Diag\EventCode;
use RZP\Trace\TraceCode;

class Events
{
    const PAYOUTS_TO_PHONE_NUMBER_VPA_NOT_FOUND = 'PAYOUTS_TO_PHONE_NUMBER_VPA_NOT_FOUND';

    const PAYOUTS_TO_PHONE_NUMBER_NAME_MATCHING_BELOW_THRESHOLD = 'PAYOUTS_TO_PHONE_NUMBER_NAME_MATCHING_BELOW_THRESHOLD';

    const PAYOUTS_TO_PHONE_NUMBER_MOBILE_NUMBER_FORMAT_INVALID = 'PAYOUTS_TO_PHONE_NUMBER_MOBILE_NUMBER_FORMAT_INVALID';

    const PAYOUTS_TO_PHONE_NUMBER_VPA_UPDATED = 'PAYOUTS_TO_PHONE_NUMBER_VPA_UPDATED';

    const PAYOUTS_TO_PHONE_NUMBER_FUND_ACCOUNT_CREATED = 'PAYOUTS_TO_PHONE_NUMBER_FUND_ACCOUNT_CREATED';


    protected $app;
    protected $trace;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->trace = $this->app['trace'];
    }

    public function sanitizeDataForTracking(array $data): array
    {
        $results = [];
        foreach ($data as $type => $value) {
            switch ($type){
                case Constants::MOBILE:
                    // Replace all but the last 5 characters with 'x'
                    if (strlen($value) < 5) {
                        $results[$type] = str_repeat('x', strlen($value));
                    } else {
                        $maskLength = strlen($value) - 5;
                        $results[$type] = str_repeat('x', $maskLength) . substr($value, -5);
                    }
                    break;
                case Constants::VPA:
                    // Mask the VPA by replacing everything before '@' with 'x'
                    $atPosition = strpos($value, '@');
                    if ($atPosition !== false) {
                        $results[$type] = str_repeat('x', $atPosition) . substr($value, $atPosition);
                    } else {
                        $results[$type] = str_repeat('x', strlen($value)); // If no '@' found, mask entire string
                    }
                    break;
                default:
                    $results[$type] = $value; // Return data as is for unknown types
                    break;
            }
        }
        return $results;
    }

    public function trackPhoneNumberPayoutEvents(
        string $eventName,
        array $properties): void
    {
        $eventDataGroup = null;

        switch ($eventName)
        {
            case self::PAYOUTS_TO_PHONE_NUMBER_VPA_NOT_FOUND:
                $eventDataGroup = EventCode::PAYOUTS_TO_PHONE_NUMBER_EVENT_VPA_NOT_FOUND;
                break;
            case self::PAYOUTS_TO_PHONE_NUMBER_NAME_MATCHING_BELOW_THRESHOLD:
                $eventDataGroup = EventCode::PAYOUTS_TO_PHONE_NUMBER_EVENT_NAME_MATCHING_BELOW_THRESHOLD;
                break;
            case self::PAYOUTS_TO_PHONE_NUMBER_MOBILE_NUMBER_FORMAT_INVALID:
                $eventDataGroup = EventCode::PAYOUTS_TO_PHONE_NUMBER_EVENT_MOBILE_NUMBER_FORMAT_INVALID;
                break;
            case self::PAYOUTS_TO_PHONE_NUMBER_VPA_UPDATED:
                $eventDataGroup = EventCode::PAYOUTS_TO_PHONE_NUMBER_EVENT_VPA_UPDATED;
                break;
            case self::PAYOUTS_TO_PHONE_NUMBER_FUND_ACCOUNT_CREATED:
                $eventDataGroup = EventCode::PAYOUTS_TO_PHONE_NUMBER_EVENT_FUND_ACCOUNT_CREATED;
                break;
        }

        $this->app['diag']->trackPhoneNumberPayoutEvents(
            $eventDataGroup,
            $properties
        );

        $this->trace->info(TraceCode::PAYOUTS_TO_PHONE_NUMBER_EVENT_PUSHED,[
            "event_name"       => $eventName,
            "event_properties" => $properties,
            "event_data_group" => $eventDataGroup
        ]);
    }

    public function trackPayoutsToPhoneNumberVPAUpdatedEvent(
        string $merchantId,
        string $mobileNumber,
        string $accountHolderName,
        string $updatedVpa,
        string $storedVpa,
        string $fundAccountId
    ): void
    {
        try{
            $sanitizedExistingData = $this->sanitizeDataForTracking([
                Constants::VPA    => $storedVpa
            ]);

            $sanitizedUpdatedData = $this->sanitizeDataForTracking([
                Constants::MOBILE => $mobileNumber,
                Constants::VPA    => $updatedVpa
            ]);

            $this->trackPhoneNumberPayoutEvents(
                self::PAYOUTS_TO_PHONE_NUMBER_VPA_UPDATED,
                [
                    Constants::MERCHANT_ID     => $merchantId,
                    Constants::MOBILE          => $sanitizedUpdatedData[Constants::MOBILE],
                    Constants::CUSTOMER_NAME   => $accountHolderName,
                    'stored_vpa'               => $sanitizedExistingData[Entity::VPA],
                    'updated_vpa'              => $sanitizedUpdatedData[Entity::VPA],
                    'event_name'               => self::PAYOUTS_TO_PHONE_NUMBER_VPA_UPDATED,
                    'fund_account_id'          => $fundAccountId,
                ]
            );
        }catch (\Throwable $e){
            $this->trace->error(TraceCode::PAYOUT_TO_PHONE_NUMBER_EVENT_TRACKING_FAILED, [
                'error_message'     => $e->getMessage(),
                'merchant_id'       => $merchantId,
                'context'           => self::PAYOUTS_TO_PHONE_NUMBER_VPA_UPDATED
            ]);
        }
    }

    public function trackPayoutsToPhoneNumberMobileNumberInvalidEvent(
        string $mobileNumber
    ): void
    {
        $app = App::getFacadeRoot();
        try {
            if (isset($app['basicauth']) && $app['basicauth']->getMerchant()) {
                $merchantId = $app['basicauth']->getMerchant()->getId();
                $sanitizedData = $this->sanitizeDataForTracking([
                    Constants::MOBILE => $mobileNumber
                ]);
                $this->trackPhoneNumberPayoutEvents(
                    self::PAYOUTS_TO_PHONE_NUMBER_MOBILE_NUMBER_FORMAT_INVALID,
                    [
                        Constants::MOBILE           => $sanitizedData[Constants::MOBILE],
                        Constants::MERCHANT_ID      => $merchantId,
                        'failure_reason'            => self::PAYOUTS_TO_PHONE_NUMBER_MOBILE_NUMBER_FORMAT_INVALID
                    ]
                );
            }
        } catch (\Throwable $e) {
            $app['trace']->error(TraceCode::PAYOUT_TO_PHONE_NUMBER_EVENT_TRACKING_FAILED, [
                'error_message'         => $e->getMessage(),
                'context'               => self::PAYOUTS_TO_PHONE_NUMBER_MOBILE_NUMBER_FORMAT_INVALID
            ]);
        }
    }

    public function trackPayoutsToPhoneNumberVpaNotFoundEvent(
        string $merchantId,
        string $linkedNumber,
        string $accountHolderName
    ): void
    {
        try {
            $sanitizedData = $this->sanitizeDataForTracking([
                Constants::MOBILE => $linkedNumber
            ]);
            $this->trackPhoneNumberPayoutEvents(
                self::PAYOUTS_TO_PHONE_NUMBER_VPA_NOT_FOUND,
                [
                    Constants::MERCHANT_ID     => $merchantId,
                    Constants::MOBILE          => $sanitizedData[Constants::MOBILE],
                    Constants::CUSTOMER_NAME   => $accountHolderName,
                    'failure_reason'           => self::PAYOUTS_TO_PHONE_NUMBER_VPA_NOT_FOUND
                ]
            );
        } catch (\Throwable $e) {
            $this->trace->error(TraceCode::PAYOUT_TO_PHONE_NUMBER_EVENT_TRACKING_FAILED, [
                'error_message'     => $e->getMessage(),
                'merchant_id'       => $merchantId,
                'context'           => self::PAYOUTS_TO_PHONE_NUMBER_VPA_NOT_FOUND
            ]);
        }
    }

    public function trackPayoutsToPhoneNumberNameMatchingBelowThresholdEvent(
        string $merchantId,
        string $linkedNumber,
        string $accountHolderName,
        string $customerName,
        string $vpaID,
        int $matchScore,
        int $threshold
    ): void
    {
        try {
            $sanitizedData = $this->sanitizeDataForTracking([
                Constants::MOBILE  => $linkedNumber,
                Constants::VPA     => $vpaID
            ]);

            $this->trackPhoneNumberPayoutEvents(
                self::PAYOUTS_TO_PHONE_NUMBER_NAME_MATCHING_BELOW_THRESHOLD,
                [
                    Constants::MERCHANT_ID     => $merchantId,
                    Constants::MOBILE          => $sanitizedData[Constants::MOBILE],
                    Constants::CUSTOMER_NAME   => $accountHolderName,
                    Constants::VPA             => $sanitizedData[Constants::VPA],
                    'bank_customer_name'       => $customerName,
                    'match_score'              => $matchScore,
                    'threshold'                => $threshold,
                    'failure_reason'           => self::PAYOUTS_TO_PHONE_NUMBER_NAME_MATCHING_BELOW_THRESHOLD
                ]
            );
        } catch (\Throwable $e) {
            $this->trace->error(TraceCode::PAYOUT_TO_PHONE_NUMBER_EVENT_TRACKING_FAILED, [
                'error_message'         => $e->getMessage(),
                'merchant_id'           => $merchantId,
                'context'               => self::PAYOUTS_TO_PHONE_NUMBER_NAME_MATCHING_BELOW_THRESHOLD
            ]);
        }
    }

    public function trackFundAccountCreatedEvent(
        string $merchantId,
        string $fundAccountId
    ): void
    {
        try {
            $this->trackPhoneNumberPayoutEvents(
                self::PAYOUTS_TO_PHONE_NUMBER_FUND_ACCOUNT_CREATED,
                [
                    Constants::MERCHANT_ID     => $merchantId,
                    "fund_account_id"          => $fundAccountId,
                    'event_name'               => self::PAYOUTS_TO_PHONE_NUMBER_FUND_ACCOUNT_CREATED
                ]
            );
        } catch (\Throwable $e) {
            $this->trace->error(TraceCode::PAYOUT_TO_PHONE_NUMBER_EVENT_TRACKING_FAILED, [
                'error_message'         => $e->getMessage(),
                'merchant_id'           => $merchantId,
                'context'               => self::PAYOUTS_TO_PHONE_NUMBER_FUND_ACCOUNT_CREATED
            ]);
        }
    }

}
