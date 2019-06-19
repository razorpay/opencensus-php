<?php

namespace RZP\Models\FundTransfer\Yesbank\Reconciliation;

use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Models\Settlement\Holidays;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\FundTransfer\Yesbank\Mode;
use RZP\Models\FundTransfer\Base\Reconciliation\Status as BaseStatus;

class Status extends BaseStatus
{
    /**
     * Bank Status Codes
     */

    // Request error because of appId or customerId being wrong
    const NA                            = 'NA';

    // same transaction was attempted again
    const AD                            = 'AD';

    // Transaction accepted
    const AS                            = 'AS';

    const FAILED                        = 'FAILED';
    const IN_PROCESS1                   = 'IN PROGRESS';
    const PENDING                       = 'Pending';
    const IN_PROCESS2                   = 'In process';
    const SENT_TO_BENEFICIARY           = 'SENT_TO_BENEFICIARY';
    const SCHEDULED_FOR_NEXT_WORKDDAY   = 'SCHEDULED_FOR_NEXT_WORKDDAY';
    const RETURNED_FROM_BENEFICIARY     = 'RETURNED_FROM_BENEFICIARY';
    const COMPLETED                     = 'COMPLETED';
    const ON_HOLD                       = 'ON_HOLD';

    // *** Custom Status codes *** //

    //
    // This will be occur in case of IMPS.
    // meaning: wait for 2 days in worst case before reporting regardless of holidays
    //
    const WAIT_FOR_TWO_DAYS             = 'WAIT_FOR_TWO_DAYS';

    //
    // This will be occur in case of NEFT.
    // This is except the bank holidays and working hours.
    //
    const WAIT_FOR_THREE_HOURS          = 'WAIT_FOR_THREE_HOURS';

    //
    // Will occur only in case of Adhaar payments
    // Should act on status after 1 day
    //
    const WAIT_FOR_ONE_DAY              = 'WAIT_FOR_ONE_DAY';

    const INSUFFICIENT_FUND             = 'INSUFFICIENT_FUND';

    const INVALID_TRANSFER_TYPE         = 'INVALID_TRANSFER_TYPE';

    const REQUEST_LIMIT_REACHED         = 'REQUEST_LIMIT_REACHED';

    const BENEFICIARY_NOT_ACCEPTED      = 'BENEFICIARY_NOT_ACCEPTED';

    const INVALID_BENEFICIARY_DETAILS   = 'INVALID_BENEFICIARY_DETAILS';

    const TRANSFER_TIMEOUT              = 'TRANSFER_TIMEOUT';

    const BENE_ACCOUNT_BLOCKED          = 'BENE_ACCOUNT_BLOCKED';

    const BENE_NOT_REGISTERED           = 'BENE_NOT_REGISTERED';

    const IMPS_NOT_ENABLED_FOR_REMITTER = 'IMPS_NOT_ENABLED_FOR_REMITTER';

    const BAD_GATEWAY                   = 'BAD_GATEWAY';

    const INVALID_ACCOUNT_DETAILS       = 'INVALID_ACCOUNT_DETAILS';

    const ACQUIRING_BANK_CBS_OFFLINE    = 'ACQUIRING_BANK_CBS_OFFLINE';

    const INVALID_REQUEST               = 'INVALID_REQUEST';

    const TECHNICAL_ERROR               = 'TECHNICAL_ERROR';

    const IMPS_NOT_ENABLED_FOR_BENE     = 'IMPS_NOT_ENABLED_FOR_BENE';

    const FUNDS_ON_HOLD                 = 'FUNDS_ON_HOLD';

    const INTERNAL_SERVER_ERROR         = 'INTERNAL_SERVER_FAILURE';

    // Map to the derived state
    const STATUS_MAP = [
        self::SENT_TO_BENEFICIARY => [
            Mode::NEFT => self::WAIT_FOR_THREE_HOURS,
            Mode::IMPS => self::WAIT_FOR_TWO_DAYS,
        ],
        self::ON_HOLD             => self::WAIT_FOR_ONE_DAY,
        self::FAILED              => [
            'ns:E500'    => self::INTERNAL_SERVER_ERROR,
            'ns:E402'    => self::INSUFFICIENT_FUND,
            'ns:E405'    => self::INVALID_TRANSFER_TYPE,
            'ns:E429'    => self::REQUEST_LIMIT_REACHED,
            'ns:502'     => self::BAD_GATEWAY,
            'ns:504'     => self::BAD_GATEWAY,
            'atom:E404'  => self::INVALID_ACCOUNT_DETAILS,
            'sfms:E99'   => self::BAD_GATEWAY,
            'sfms:E70'   => self::BAD_GATEWAY,
            'sfms:E18'   => self::BAD_GATEWAY,
            'ns:E6001'   => self::BENE_NOT_REGISTERED,
            'ns:E2005'   => self::BENE_NOT_REGISTERED,
            'ns:E400'    => self::INVALID_REQUEST,
            'ns:E2000'   => self::INVALID_REQUEST,
            'ns:E6000'   => self::INVALID_REQUEST,
            'ns:E6002'   => self::INVALID_REQUEST,
            'ns:E6003'   => self::INVALID_REQUEST,
            'ns:E6005'   => self::INVALID_REQUEST,
            'ns:E6006'   => self::INVALID_REQUEST,
            'ns:E6007'   => self::INVALID_REQUEST,
            'ns:E6008'   => self::INVALID_REQUEST,
            'ns:E502'    => self::TRANSFER_TIMEOUT,
            'ns:E1001'   => self::TRANSFER_TIMEOUT,
            'ns:E1002'   => self::TRANSFER_TIMEOUT,
            'ns:E8000'   => self::TRANSFER_TIMEOUT,
            'flex:E404'  => self::TRANSFER_TIMEOUT,
            'ns:E504'    => self::TECHNICAL_ERROR,
            'ns:E1004'   => self::INVALID_REQUEST,
            'ns:E1005'   => self::INVALID_REQUEST,
            'ns:E1006'   => self::INVALID_REQUEST,
            'ns:E406'    => self::BENEFICIARY_NOT_ACCEPTED,
            'flex:E449'  => self::BENEFICIARY_NOT_ACCEPTED,
            'flex:E8087' => self::INVALID_BENEFICIARY_DETAILS,
            'flex:E9072' => self::INVALID_BENEFICIARY_DETAILS,
            'npci:E449'  => self::BENEFICIARY_NOT_ACCEPTED,
            'npci:E08'   => self::ACQUIRING_BANK_CBS_OFFLINE,
            'npci:EM1'   => self::BENEFICIARY_NOT_ACCEPTED,
            'npci:EM2'   => self::BENEFICIARY_NOT_ACCEPTED,
            'npci:EM4'   => self::BENEFICIARY_NOT_ACCEPTED,
            'npci:EM5'   => self::BENEFICIARY_NOT_ACCEPTED,
            'npci:E307'  => self::BENEFICIARY_NOT_ACCEPTED,
            'npci:E308'  => self::BENEFICIARY_NOT_ACCEPTED,
            'npci:EM3'   => self::BENE_ACCOUNT_BLOCKED,
            'ns:E1029'   => self::IMPS_NOT_ENABLED_FOR_REMITTER,
            'atom:E449'  => self::INVALID_BENEFICIARY_DETAILS,
            'ns:E1028'   => self::IMPS_NOT_ENABLED_FOR_BENE,
            'flex:E18'   => self::FUNDS_ON_HOLD,
            'flex:E307'  => self::TECHNICAL_ERROR,
            'flex:E8036' => self::INVALID_REQUEST,
            'atom:E307'  => self::TECHNICAL_ERROR,
        ],
    ];

    // max wait time in hours to determine the final state
    const MAX_WAIT_TIME_IN_HOURS_FOR_STATUS = [
        self::WAIT_FOR_TWO_DAYS    => 48,
        self::WAIT_FOR_THREE_HOURS => 3,
        self::WAIT_FOR_ONE_DAY     => 24,
    ];

    //
    // We are maintaining the map of sub code to remark because status response will only have the sub status code
    // So this mapping will give the corresponding remark based on the sub status code
    //
    const FAILURE_CODE_INTERNAL_MAPPING = [
        'ns:E500'    => 'Unhandled exception has occurred',
        'ns:E400'    => 'Invalid request sent to bank',
        'ns:E402'    => 'Insufficient Balance in debit account, payment required',
        'ns:E405'    => 'Invalid Transfer Type',
        'ns:E429'    => '(Limit Daily/transaction/rate) exceeded',
        'ns:E406'    => 'Beneficiary not acceptable',
        'ns:E502'    => 'Bad Gateway',
        'ns:E504'    => 'Technical Error',
        'ns:E1001'   => 'The transaction amount exceeds the maximum amount for IMPS:',
        'ns:E1002'   => 'The transfer currency is not supported. Supported currency is INR.',
        'ns:E1004'   => 'Transfer Amount is less than minimum amount for RTGS',
        'ns:E1005'   => 'For APBS transferType, specify the Aadhaar no and mobile no in the beneficiary Detail',
        'ns:E1006'   => 'Use APBS transferType, when transferring funds to an Aadhaar No',
        'ns:E1028'   => 'IMPS is not enabled for the beneficiary IFSC',
        'ns:E2000'   => 'Either customer does not exist or Customer/Account combination is invalid or '
                        . 'Customer/Account Relationship is invalid',
        'ns:E6000'   => 'Purpose Code not found:',
        'ns:E6001'   => 'Only registered beneficiaries are allowed for this purpose code:',
        'ns:E6002'   => 'Purpose Code is required for this customer:',
        'ns:E6003'   => 'Invalid Debit Account for Customer',
        'ns:E6005'   => 'Either Beneficiary Name or beneficiary IFSC code is not valid for GST payment.'
                        . 'Valid value is for beneficiary name: < GST> and ifsc code is: <RBIS0GSTPMT >',
        'ns:E6006'   => 'Beneficiary account no length is not valid for GST payment allowed length is 14',
        'ns:E6007'   => 'The specified purpose code is not allowed for the chosen transferType',
        'ns:E6008'   => 'Unique request no length is not valid for APBS transfer type, max allowed length is 13',
        'flex:E18'   => 'Hold Funds Present - Refer to Drawer ( Account would Overdraw )',
        'flex:E307'  => 'Rejected/Failed at upstream CBS Service. Retry after 30 min',
        'flex:E404'  => 'No Relationship Exists with the debit Account {AccountNo} and partner',
        'flex:E449'  => 'Rejected by upstream CBS Service for the request parameters passed',
        'flex:E8036' => 'NEFT - Both Customer Mobile and Email is not valid.',
        'flex:E8087' => 'To Account Number is Invalid.',
        'flex:E9072' => 'Destination Bank and Branch could not be resolved.',
        'npci:E08'   => 'Acquiring Bank CBS or node offline',
        'npci:EM1'   => 'Invalid Beneficiary MMID/Mobile Number',
        'npci:EM2'   => 'Amount limit exceeded',
        'npci:EM3'   => 'Account blocked/frozen',
        'npci:EM4'   => 'Beneficiary Bank is not enabled for Foreign Inward Remittance',
        'npci:EM5'   => 'Account closed',
        'npci:E307'  => 'Rejected/Failed at beneficiary bank',
        'npci:E308'  => 'Rejected by beneficiary bank in reconciliation',
        'npci:E449'  => 'Rejected by beneficiary bank for request parameters passed',
        'atom:E307'  => 'Rejected/Failed at upstream IMPS Service',
        'atom:E404'  => 'No Relationship Exists with the debit Account {AccountNo} and partner',
        'atom:E449'  => 'Rejected by upstream IMPS Service for the request parameters passed',
        'sfms:E99'   => 'Manually Marked in Error',
        'sfms:E70'   => 'Outward Transaction Rejected',
        'sfms:E18'   => 'Rejected by SFMS',
        'ns:E1029'   => 'IMPS is not enabled for the remitter',
    ];

    const FAILURE_CODE_PUBLIC_MAPPING = [
        'ns:E400'    => 'Payout failed. Contact support for help.',
        'ns:E402'    => 'Payout failed. Contact support for help.',
        'ns:E405'    => 'Payout failed. Contact support for help.',
        'ns:E429'    => 'Payout failed. Contact support for help.',
        'ns:E406'    => 'Payout failed. Contact support for help.',
        'ns:E502'    => 'Payout failed. Contact support for help.',
        'ns:E504'    => 'Payout failed. Contact support for help.',
        'ns:E1001'   => 'Payout failed. Contact support for help.',
        'ns:E1002'   => 'Payout failed. Contact support for help.',
        'ns:E1004'   => 'Payout failed. Contact support for help.',
        'ns:E1005'   => 'Payout failed. Contact support for help.',
        'ns:E1006'   => 'Payout failed. Contact support for help.',
        'ns:E1028'   => 'IMPS is not enabled on this fund account.',
        'ns:E2000'   => 'Payout failed. Contact support for help.',
        'ns:E6000'   => 'Payout failed. Contact support for help.',
        'ns:E6001'   => 'Payout failed. Contact support for help.',
        'ns:E6002'   => 'Payout failed. Contact support for help.',
        'ns:E6003'   => 'Payout failed. Contact support for help.',
        'ns:E6005'   => 'Payout failed. Contact support for help.',
        'ns:E6006'   => 'Payout failed. Contact support for help.',
        'ns:E6007'   => 'Payout failed. Contact support for help.',
        'ns:E6008'   => 'Payout failed. Contact support for help.',
        'flex:E18'   => 'Payout failed. Contact support for help.',
        'flex:E307'  => 'Partner bank systems are down. Try again later.',
        'flex:E404'  => 'Payout failed. Contact support for help.',
        'flex:E449'  => 'Payout failed. Contact support for help.',
        'flex:E8036' => 'Payout failed. Contact support for help.',
        'flex:E8087' => 'Account number is not valid.',
        'flex:E9072' => "Fund account's bank and branch could not be resolved.",
        'npci:E08'   => 'Partner bank systems are down. Try again later.',
        'npci:EM1'   => 'Payout failed. Contact support for help.',
        'npci:EM2'   => 'Payout failed. Contact support for help.',
        'npci:EM3'   => 'Fund account is blocked or frozen.',
        'npci:EM4'   => 'Payout failed. Contact support for help.',
        'npci:EM5'   => 'Fund account is closed.',
        'npci:E307'  => "Contact's bank rejected/failed the payout.",
        'npci:E308'  => "Contact's bank rejected/failed the payout.",
        'npci:E449'  => "Contact's bank rejected/failed the payout due to invalid parameters.",
        'atom:E307'  => 'Payout failed. Contact support for help.',
        'atom:E404'  => 'Payout failed. Contact support for help.',
        'atom:E449'  => 'Payout failed. Contact support for help.',
        'sfms:E99'   => 'Payout failed. Contact support for help.',
        'sfms:E70'   => 'Payout failed. Contact support for help.',
        'sfms:E18'   => 'Payout failed. Contact support for help.',
        'ns:E1029'   => 'Payout failed. Contact support for help.',
        'ns:E500'    => 'Payout failed. Contact support for help.',
    ];

    /**
     * {{@inheritdoc}}
     */
    public static function getSuccessfulStatus(): array
    {
        return [
            self::COMPLETED => [],
        ];
    }

    /**
     * {{@inheritdoc}}
     */
    public static function getFailureStatus(): array
    {
        return [
            self::NA                            => [],
            self::AD                            => [],
            self::FAILED                        => [],
            self::RETURNED_FROM_BENEFICIARY     => [],
            self::INVALID_BENEFICIARY_DETAILS   => [],
            self::BENEFICIARY_NOT_ACCEPTED      => [],
            self::INSUFFICIENT_FUND             => [],
            self::INVALID_TRANSFER_TYPE         => [],
            self::REQUEST_LIMIT_REACHED         => [],
            self::BENE_ACCOUNT_BLOCKED          => [],
            self::BENE_NOT_REGISTERED           => [],
            self::IMPS_NOT_ENABLED_FOR_REMITTER => [],
            self::INVALID_ACCOUNT_DETAILS       => [],
            self::BAD_GATEWAY                   => [],
            self::ACQUIRING_BANK_CBS_OFFLINE    => [],
            self::TRANSFER_TIMEOUT              => [],
            self::INVALID_REQUEST               => [],
            self::TECHNICAL_ERROR               => [],
            self::IMPS_NOT_ENABLED_FOR_BENE     => [],
            self::FUNDS_ON_HOLD                 => [],
            self::INTERNAL_SERVER_ERROR         => [],
        ];
    }

    /**
     * {{@inheritdoc}}
     */
    public static function getCriticalErrorStatus(): array
    {
        return [
            self::NA                            => [],
            self::AD                            => [],
            self::BAD_GATEWAY                   => [],
            self::INSUFFICIENT_FUND             => [],
            self::INVALID_TRANSFER_TYPE         => [],
            self::REQUEST_LIMIT_REACHED         => [],
            self::BENE_NOT_REGISTERED           => [],
            self::INVALID_ACCOUNT_DETAILS       => [],
            self::IMPS_NOT_ENABLED_FOR_REMITTER => [],
            self::ACQUIRING_BANK_CBS_OFFLINE    => [],
            self::INVALID_REQUEST               => [],
            self::FUNDS_ON_HOLD                 => [],
            self::TECHNICAL_ERROR               => [],
            self::TRANSFER_TIMEOUT              => [],
            self::INTERNAL_SERVER_ERROR         => [],
        ];
    }

    /**
     * {{@inheritdoc}}
     */
    public static function getInternalErrorStatus(): array
    {
        return [
            self::TECHNICAL_ERROR,
            self::INVALID_REQUEST,
            self::INTERNAL_SERVER_ERROR,
        ];
    }

    /**
     * {{@inheritdoc}}
     */
    public static function getCriticalErrorRemarks(): array
    {
        return [];
    }

    public static function getMerchantFailures(): array
    {
        return [
            self::BENEFICIARY_NOT_ACCEPTED      => [],
            self::INVALID_BENEFICIARY_DETAILS   => [],
            self::BENEFICIARY_NOT_ACCEPTED      => [],
            self::BENE_ACCOUNT_BLOCKED          => [],
            self::IMPS_NOT_ENABLED_FOR_BENE     => [],
        ];
    }

    /**
     * Provide the derived status if defined else gives the same status as passed
     * This is dont to identify the final status of the transaction for few cases
     *
     * @param string      $status
     * @param string|null $mode
     * @param string|null $subStatus
     * @return mixed|string
     */
    public static function getStatus(string $status, string $mode = null, string $subStatus = null)
    {
        if (isset(self::STATUS_MAP[$status]) === false)
        {
            return $status;
        }

        $identifier = $subStatus;

        if ($status === self::SENT_TO_BENEFICIARY)
        {
            $identifier = $mode;
        }

        if ($identifier === null)
        {
            return self::STATUS_MAP[$status] ?? $status;
        }

        return self::STATUS_MAP[$status][$identifier] ?? $status;
    }

    public static function isInternalError(Attempt\Entity $entity): bool
    {
        $bankStatusCode = $entity->getBankStatusCode();

        if((self::isCriticalError($entity) === true) or
            (in_array($bankStatusCode, self::getInternalErrorStatus(), true) === true))
        {
            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     *
     * @param Attempt\Entity $entity
     *
     * @return bool
     */
    public static function isCriticalError(Attempt\Entity $entity): bool
    {
        $bankStatusCode = $entity->getBankStatusCode();

        $bankResponseCode = $entity->getBankResponseCode();

        $status = self::isCriticalStatus($bankStatusCode, $bankResponseCode);

        if ($status === true)
        {
            return true;
        }

        $potentialErrorsStatus = self::getPotentialErrorStatus();

        $status = in_array($bankStatusCode, $potentialErrorsStatus, true);

        if ($status === true)
        {
            $status = self::isFailureCondition($entity, $bankStatusCode);
        }

        return (bool) $status;
    }

    public static function getPotentialErrorStatus(): array
    {
        return [
            self::WAIT_FOR_TWO_DAYS,
            self::WAIT_FOR_THREE_HOURS,
            self::WAIT_FOR_ONE_DAY,
        ];
    }

    /**
     * Checks if the attempts wait time is over
     * if not then returns false else true
     *
     * @param Attempt\Entity $entity
     * @param string         $status
     *
     * @return bool
     */
    public static function isFailureCondition(Attempt\Entity $entity, string $status): bool
    {
        $initiatedTime = $entity->getInitiateAt();

        $offsetInHours = self::MAX_WAIT_TIME_IN_HOURS_FOR_STATUS[$status] ?? 0;

        $finalTimestamp = Holidays::addOffsetedWorkingTime($initiatedTime, $offsetInHours);

        $currentTimestamp = Carbon::now(Timezone::IST)->getTimestamp();

        if ($finalTimestamp < $currentTimestamp)
        {
            return true;
        }

        return false;
    }

    /**
     * If the code exist in remark map then returns the
     * remark corresponding to the code else returns null
     *
     * @param string|null $code
     *
     * @return mixed|null
     */
    public static function getRemark(string $code = null)
    {
        return (isset(self::FAILURE_CODE_INTERNAL_MAPPING[$code]) === true) ?
            self::FAILURE_CODE_INTERNAL_MAPPING[$code] :
            null;
    }

    public static function getPublicFailureReason($bankStatusCode, $bankResponseCode = null)
    {
        $successfulStatus =  self::getSuccessfulStatus();

        $isSuccessful = self::inStatus($successfulStatus, $bankStatusCode, $bankResponseCode);
        // sub status code will be empty if the request was complete
        if ($isSuccessful === true)
        {
            return null;
        }
        else if (in_array($bankResponseCode, array_keys(self::FAILURE_CODE_PUBLIC_MAPPING), true) === true)
        {
            return self::FAILURE_CODE_PUBLIC_MAPPING[$bankResponseCode];
        }

        return 'transfer not completed';
    }
}
