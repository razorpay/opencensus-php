<?php

namespace RZP\Models\FundTransfer\Attempt;

use RZP\Models\Card\Issuer;
use RZP\Models\Card\Network;

final class Constants
{
    // *** FTA related constants *** //

    const REMARKS               = 'remarks';

    const FTA_STATUS            = 'fta_status';

    const FAILURE_REASON        = 'failure_reason';

    const VPA_ID                = 'vpa_id';

    const MODE                  = 'mode';

    const UTR                   = 'utr';

    const FTA_ID                = 'fta_id';

    const DEFAULT_NETWORK       = 'default_network';

    const BANK_PROCESSED_TIME   = 'bank_processed_time';

    const INTERNAL_ERROR        = 'internal_error';

    const IGNORE_TIME_LIMIT     = 'ignore_time_limit';

    const CHANNEL               = 'channel';

    const BENEFICIARY_NAME      = 'beneficiary_name';

    const MAX_UPI_AMOUNT        = 100000;

    const MAX_IMPS_AMOUNT       = 50000000;

    const NEFT_END_HOUR = 18;

    const NEFT_START_HOUR = 8;

    Const NEFT_END_MINUTE = 15;

    const RTGS_REVISED_END_HOUR = 17;

    const RTGS_REVISED_START_HOUR = 8;

    const RTGS_REVISED_END_MINUTE = 30;

    const IMPS_STATUS_CHECK_DISPATCH_TIME = 10;

    const MAX_AGE_ATTEMPT_STATUS_DISPATCH_AGE = 1800;

    const DEFAULT_STATUS_CHECK_DISPATCH_TIME = 180;

    const DEFAULT_ISSUER = 'default_issuer';

    const PROCESSED_BY_TIME = 'processed_by_time';

    const FTS_FUND_ACCOUNT_ID   = 'fts_fund_account_id';

    const FTS_ACCOUNT_TYPE      = 'fts_account_type';

    //Card Issuer bank IFSC Code mapping
    const BANK_IFSC = [
        Issuer::UTIB => [
            self::DEFAULT_NETWORK => 'UTIB0000400',
            ],
        Issuer::HDFC => [
            self::DEFAULT_NETWORK => 'HDFC0000128',
            ],
        Issuer::INDB => [
            self::DEFAULT_NETWORK => 'INDB0000018',
            ],
        Issuer::KKBK => [
            self::DEFAULT_NETWORK => 'KKBK0000958',
            ],
        Issuer::ANDB => [
            self::DEFAULT_NETWORK => 'ANDB0000782',
            ],
        Issuer::ICIC => [
            self::DEFAULT_NETWORK => 'ICIC0000103',
            ],
        Issuer::CITI => [
            self::DEFAULT_NETWORK => 'CITI0000003',
            ],
        Issuer::HSBC => [
            self::DEFAULT_NETWORK => 'HSBC0400002',
            ],
        Issuer::PUNB => [
            self::DEFAULT_NETWORK => 'PUNB0645400',
            ],
        Issuer::CNRB => [
            self::DEFAULT_NETWORK => 'CNRB0001912',
            ],
        Issuer::UBIN => [
            self::DEFAULT_NETWORK => 'UBIN0580104',
            ],
        Issuer::BKID => [
            self::DEFAULT_NETWORK => 'BKID0000101',
            ],
        Issuer::CORP => [
            self::DEFAULT_NETWORK => 'CORP0008954',
            ],
        Issuer::SYNB => [
            self::DEFAULT_NETWORK => 'SYNB0002915',
            ],
        Issuer::IOBA => [
            self::DEFAULT_NETWORK => 'IOBA0009043',
            ],
        Issuer::BOFA => [
            self::DEFAULT_NETWORK => 'BOFA0MM6205',
            ],
        Issuer::IBKL => [
            self::DEFAULT_NETWORK => 'IBKL0NEFT01',
            ],
        Issuer::BARB => [
            self::DEFAULT_NETWORK => 'BARB0COLABA',
            ],
        Issuer::YESB => [
            self::DEFAULT_NETWORK => 'YESB0CMSNOC',
            ],
        Issuer::SBIN => [
            self::DEFAULT_NETWORK => 'SBIN00CARDS',
            ],
        Issuer::RATN => [
            self::DEFAULT_NETWORK => 'RATN0CRCARD',
            ],
        //Adding Networkcode as a key since Amex card network uses SCBL issuer internally
        //and It can have other issuers as well. Also by this we distinguish with other cards issued by SCBL
        Issuer::SCBL => [
            Network::AMEX         => 'SCBL0036020',
            self::DEFAULT_NETWORK => 'SCBL0036001'
            ],
    ];

    const DISABLE              = 'disable';
}
