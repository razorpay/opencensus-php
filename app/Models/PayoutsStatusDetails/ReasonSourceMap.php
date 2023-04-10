<?php

namespace RZP\Models\PayoutsStatusDetails;

use RZP\Models\PayoutsStatusDetails\StatusReasonMap as Reason;

class ReasonSourceMap
{
 const BENEFICIARY_BANK         = 'beneficiary_bank';

 const INTERNAL                 = 'internal';

 const BUSINESS                 = 'business';

 const GATEWAY                  = 'gateway';

 const GATEWAY_DEGRADED         = 'gateway_degraded';

 public static $statusDetailsReasonToSourceMap = [

     Reason::BENEFICIARY_BANK_CONFIRMATION_PENDING => self::BENEFICIARY_BANK,

     Reason::BANK_WINDOW_CLOSED                    => self::INTERNAL,

     Reason::PAYOUT_BANK_PROCESSING                => self::INTERNAL,

     Reason::PARTNER_BANK_PENDING                  => self::INTERNAL,

     Reason::PAYOUT_PROCESSING                     => self::INTERNAL,

     Reason::PENDING_APPROVAl                      => self::BUSINESS,

     Reason::BENEFICIARY_BANK_DOWN                 => self::GATEWAY,

     Reason::NPCI_SYSTEM_DOWN                      => self::GATEWAY,

     Reason::LOW_BALANCE                           => self::BUSINESS,

     Reason::GATEWAY_DEGRADED                      => self::GATEWAY,

     Reason::PAYOUT_PROCESSED                      => self::BENEFICIARY_BANK,

     Reason::GATEWAY_TECHNICAL_ERROR               => self::GATEWAY,

     Reason::BENEFICIARY_BANK_OFFLINE              => self::BENEFICIARY_BANK,

     Reason::BENEFICIARY_PSP_OFFLINE               => self::BENEFICIARY_BANK,

     Reason::BANK_ACCOUNT_CLOSED                   => self::BENEFICIARY_BANK,

     Reason::TRANSACTION_LIMIT_EXCEEDED            => self::BENEFICIARY_BANK,

     Reason::BENEFICIARY_BANK_FAILURE              => self::BENEFICIARY_BANK,

     Reason::BENEFICIARY_BANK_REJECTED             => self::BENEFICIARY_BANK,

     Reason::BANK_ACCOUNT_FROZEN                   => self::BENEFICIARY_BANK,

     Reason::GATEWAY_TIMEOUT                       => self::GATEWAY,

     Reason::IMPS_NOT_ALLOWED                      => self::BENEFICIARY_BANK,

     /**
      * Commenting this mapping as for this reason multiple source is present .
      * For this reason we will use source from json file depending on bank status code.
      */
     //Reason::BANK_ACCOUNT_INVALID                  => self::BUSINESS,

     Reason::INVALID_IFSC_CODE                     => self::BENEFICIARY_BANK,

     Reason::BENEFICIARY_VPA_INVALID               => self::BUSINESS,

     Reason::NRE_BANK_ACCOUNT                      => self::BENEFICIARY_BANK,

     Reason::SERVER_ERROR                          => self::INTERNAL,

     Reason::BANK_ACCOUNT_DORMANT                  => self::BUSINESS,

     Reason::AMOUNT_LIMIT_EXHAUSTED                => self::BUSINESS,

     Reason::GATEWAY_DOWN                          => self::GATEWAY,
 ];
}
