<?php

namespace RZP\Models\Terminal;

use Illuminate\Auth\Access\Gate;
use RZP\Models\Merchant\Account;
use RZP\Models\Payment\Gateway;
use RZP\Models\Payment\AuthType;
use RZP\Models\Terminal\Capability;

class AuthenticationTerminals
{
    const AUTHENTICATION_GATEWAY = 'authentication_gateway';

    const MERCHANT_ID            = 'merchant_id';

    const AUTH_TYPE              = 'auth_type';

    const GATEWAY_AUTH_TYPE      = 'gateway_auth_type';

    const GATEWAY                = 'gateway';

    const GATEWAY_ACQUIRERS      = 'gateway_acquirers';

    const CAPABILITY             = 'capability';

    const GATEWAY_AUTH_VERSION = 'gateway_auth_version';

    const AUTHENTICATION_TERMINALS = [

        [
            self::MERCHANT_ID               => Account::SHARED_ACCOUNT,
            self::GATEWAY                   => Gateway::HITACHI,
            self::AUTHENTICATION_GATEWAY    => Gateway::PAYSECURE,
            self::AUTH_TYPE                 => AuthType::HEADLESS_OTP,
            self::GATEWAY_AUTH_TYPE         => AuthType::_3DS,
        ],
        [
            self::MERCHANT_ID               => Account::SHARED_ACCOUNT,
            self::GATEWAY                   => Gateway::PAYSECURE,
            self::AUTHENTICATION_GATEWAY    => null,
            self::AUTH_TYPE                 => AuthType::HEADLESS_OTP,
            self::GATEWAY_AUTH_TYPE         => AuthType::_3DS,
        ],
        [
            self::MERCHANT_ID               => Account::SHARED_ACCOUNT,
            self::GATEWAY                   => Gateway::HITACHI,
            self::AUTHENTICATION_GATEWAY    => Gateway::MPI_BLADE,
            self::AUTH_TYPE                 => AuthType::_3DS,
            self::GATEWAY_AUTH_TYPE         => AuthType::_3DS,
        ],
        [
            self::MERCHANT_ID               => Account::SHARED_ACCOUNT,
            self::GATEWAY                   => Gateway::HITACHI,
            self::AUTHENTICATION_GATEWAY    => Gateway::MPI_BLADE,
            self::AUTH_TYPE                 => AuthType::IVR,
            self::GATEWAY_AUTH_TYPE         => AuthType::OTP,
        ],
        [
            self::MERCHANT_ID               => Account::SHARED_ACCOUNT,
            self::GATEWAY                   => Gateway::HITACHI,
            self::AUTHENTICATION_GATEWAY    => Gateway::MPI_BLADE,
            self::AUTH_TYPE                 => AuthType::_3DS,
            self::GATEWAY_AUTH_TYPE         => AuthType::_3DS,
            self::GATEWAY_AUTH_VERSION         => "v2",
        ],
        [
            self::MERCHANT_ID               => Account::SHARED_ACCOUNT,
            self::GATEWAY                   => Gateway::HITACHI,
            self::AUTHENTICATION_GATEWAY    => Gateway::MPI_ENSTAGE,
            self::AUTH_TYPE                 => AuthType::OTP,
            self::GATEWAY_AUTH_TYPE         => AuthType::OTP,
        ],
        [
            self::MERCHANT_ID               => Account::SHARED_ACCOUNT,
            self::GATEWAY                   => Gateway::HITACHI,
            self::AUTHENTICATION_GATEWAY    => Gateway::MPI_BLADE,
            self::AUTH_TYPE                 => AuthType::HEADLESS_OTP,
            self::GATEWAY_AUTH_TYPE         => AuthType::_3DS,
        ],
        [
            self::MERCHANT_ID               => Account::SHARED_ACCOUNT,
            self::GATEWAY                   => Gateway::HITACHI,
            self::AUTHENTICATION_GATEWAY    => Gateway::MPI_BLADE,
            self::AUTH_TYPE                 => AuthType::HEADLESS_OTP,
            self::GATEWAY_AUTH_TYPE         => AuthType::_3DS,
            self::GATEWAY_AUTH_VERSION         => "v2",
        ],
        [
            self::MERCHANT_ID               => Account::SHARED_ACCOUNT,
            self::GATEWAY                   => Gateway::HITACHI,
            self::AUTHENTICATION_GATEWAY    => null,
            self::AUTH_TYPE                 => AuthType::SKIP,
            self::GATEWAY_AUTH_TYPE         => null,
        ],
        [
            self::MERCHANT_ID               => Account::SHARED_ACCOUNT,
            self::GATEWAY                   => Gateway::FULCRUM,
            self::AUTHENTICATION_GATEWAY    => Gateway::MPI_BLADE,
            self::AUTH_TYPE                 => AuthType::HEADLESS_OTP,
            self::GATEWAY_AUTH_TYPE         => AuthType::_3DS,
        ],
        [
            self::MERCHANT_ID               => Account::SHARED_ACCOUNT,
            self::GATEWAY                   => Gateway::FULCRUM,
            self::AUTHENTICATION_GATEWAY    => Gateway::MPI_BLADE,
            self::AUTH_TYPE                 => AuthType::_3DS,
            self::GATEWAY_AUTH_TYPE         => AuthType::_3DS,
        ],
        [
            self::MERCHANT_ID               => Account::SHARED_ACCOUNT,
            self::GATEWAY                   => Gateway::HDFC,
            self::CAPABILITY                => Capability::AUTHORIZE,
            self::AUTHENTICATION_GATEWAY    => Gateway::MPI_BLADE,
            self::AUTH_TYPE                 => AuthType::_3DS,
            self::GATEWAY_AUTH_TYPE         => AuthType::_3DS,
        ],
        [
            self::MERCHANT_ID               => Account::SHARED_ACCOUNT,
            self::GATEWAY                   => Gateway::HDFC,
            self::CAPABILITY                => Capability::AUTHORIZE,
            self::AUTHENTICATION_GATEWAY    => Gateway::MPI_BLADE,
            self::AUTH_TYPE                 => AuthType::HEADLESS_OTP,
            self::GATEWAY_AUTH_TYPE         => AuthType::_3DS,
        ],
        [
            self::MERCHANT_ID               => Account::SHARED_ACCOUNT,
            self::GATEWAY                   => Gateway::HDFC,
            self::AUTHENTICATION_GATEWAY    => null,
            self::AUTH_TYPE                 => AuthType::_3DS,
            self::GATEWAY_AUTH_TYPE         => null,
        ],
        [
            self::MERCHANT_ID               => Account::SHARED_ACCOUNT,
            self::GATEWAY                   => Gateway::HDFC,
            self::AUTHENTICATION_GATEWAY    => null,
            self::AUTH_TYPE                 => AuthType::HEADLESS_OTP,
            self::GATEWAY_AUTH_TYPE         => null,
        ],
        [
            self::MERCHANT_ID               => Account::SHARED_ACCOUNT,
            self::GATEWAY                   => Gateway::HDFC,
            self::AUTHENTICATION_GATEWAY    => null,
            self::AUTH_TYPE                 => AuthType::_3DS,
            self::GATEWAY_AUTH_TYPE         => null,
            self::GATEWAY_AUTH_VERSION      => "v2",
        ],
        [
            self::MERCHANT_ID               => Account::SHARED_ACCOUNT,
            self::GATEWAY                   => Gateway::HDFC,
            self::AUTHENTICATION_GATEWAY    => null,
            self::AUTH_TYPE                 => AuthType::HEADLESS_OTP,
            self::GATEWAY_AUTH_TYPE         => null,
            self::GATEWAY_AUTH_VERSION      => "v2",
        ],
        [
            self::MERCHANT_ID               => Account::SHARED_ACCOUNT,
            self::GATEWAY                   => Gateway::CYBERSOURCE,
            self::AUTHENTICATION_GATEWAY    => null,
            self::AUTH_TYPE                 => AuthType::_3DS,
            self::GATEWAY_AUTH_TYPE         => null,
        ],
        [
            self::MERCHANT_ID               => Account::SHARED_ACCOUNT,
            self::GATEWAY                   => Gateway::CYBERSOURCE,
            self::AUTHENTICATION_GATEWAY    => null,
            self::AUTH_TYPE                 => AuthType::_3DS,
            self::GATEWAY_AUTH_TYPE         => null,
            self::GATEWAY_AUTH_VERSION      => "v2"
        ],
        [
            self::MERCHANT_ID               => Account::SHARED_ACCOUNT,
            self::GATEWAY                   => Gateway::CYBERSOURCE,
            self::AUTHENTICATION_GATEWAY    => Gateway::GOOGLE_PAY,
            self::AUTH_TYPE                 => AuthType::_3DS,
            self::GATEWAY_AUTH_TYPE         => null,
        ],
        [
            self::MERCHANT_ID               => Account::SHARED_ACCOUNT,
            self::GATEWAY                   => Gateway::CYBERSOURCE,
            self::AUTHENTICATION_GATEWAY    => Gateway::VISA_SAFE_CLICK,
            self::AUTH_TYPE                 => AuthType::_3DS,
            self::GATEWAY_AUTH_TYPE         => null,
        ],
        [
            self::MERCHANT_ID               => Account::SHARED_ACCOUNT,
            self::GATEWAY                   => Gateway::CYBERSOURCE,
            self::AUTHENTICATION_GATEWAY    => null,
            self::AUTH_TYPE                 => AuthType::HEADLESS_OTP,
            self::GATEWAY_AUTH_TYPE         => null,
        ],
        [
            self::MERCHANT_ID               => Account::SHARED_ACCOUNT,
            self::GATEWAY                   => Gateway::CYBERSOURCE,
            self::AUTHENTICATION_GATEWAY    => null,
            self::AUTH_TYPE                 => AuthType::HEADLESS_OTP,
            self::GATEWAY_AUTH_TYPE         => null,
            self::GATEWAY_AUTH_VERSION      => "v2"
        ],
        [
            self::MERCHANT_ID               => Account::SHARED_ACCOUNT,
            self::GATEWAY                   => Gateway::FIRST_DATA,
            self::AUTHENTICATION_GATEWAY    => null,
            self::AUTH_TYPE                 => AuthType::_3DS,
            self::GATEWAY_AUTH_TYPE         => null,
        ],
        [
            self::MERCHANT_ID               => Account::SHARED_ACCOUNT,
            self::GATEWAY                   => Gateway::FIRST_DATA,
            self::AUTHENTICATION_GATEWAY    => null,
            self::AUTH_TYPE                 => AuthType::HEADLESS_OTP,
            self::GATEWAY_AUTH_TYPE         => null,
        ],
        [
            self::MERCHANT_ID               => Account::SHARED_ACCOUNT,
            self::GATEWAY                   => Gateway::AXIS_MIGS,
            self::AUTHENTICATION_GATEWAY    => null,
            self::AUTH_TYPE                 => AuthType::_3DS,
            self::GATEWAY_AUTH_TYPE         => null,
        ],
        [
            self::MERCHANT_ID               => Account::SHARED_ACCOUNT,
            self::GATEWAY                   => Gateway::CARD_FSS,
            self::AUTHENTICATION_GATEWAY    => null,
            self::AUTH_TYPE                 => AuthType::HEADLESS_OTP,
            self::GATEWAY_AUTH_TYPE         => null,
        ],
        [
            self::MERCHANT_ID               => Account::SHARED_ACCOUNT,
            self::GATEWAY                   => Gateway::CARD_FSS,
            self::AUTHENTICATION_GATEWAY    => null,
            self::AUTH_TYPE                 => AuthType::_3DS,
            self::GATEWAY_AUTH_TYPE         => null,
        ],
        [
            self::MERCHANT_ID               => Account::SHARED_ACCOUNT,
            self::GATEWAY                   => Gateway::CARD_FSS,
            self::AUTHENTICATION_GATEWAY    => null,
            self::AUTH_TYPE                 => AuthType::PIN,
            self::GATEWAY_AUTH_TYPE         => null,
        ],
        [
            self::MERCHANT_ID               => Account::SHARED_ACCOUNT,
            self::GATEWAY                   => Gateway::AMEX,
            self::AUTHENTICATION_GATEWAY    => null,
            self::AUTH_TYPE                 => AuthType::_3DS,
            self::GATEWAY_AUTH_TYPE         => null,
        ],
        [
            self::MERCHANT_ID               => Account::SHARED_ACCOUNT,
            self::GATEWAY                   => Gateway::CYBERSOURCE,
            self::AUTHENTICATION_GATEWAY    => Gateway::MPI_BLADE,
            self::AUTH_TYPE                 => AuthType::_3DS,
            self::GATEWAY_AUTH_TYPE         => AuthType::_3DS,
        ],
        [
            self::MERCHANT_ID               => Account::SHARED_ACCOUNT,
            self::GATEWAY                   => Gateway::CYBERSOURCE,
            self::AUTHENTICATION_GATEWAY    => Gateway::MPI_BLADE,
            self::AUTH_TYPE                 => AuthType::IVR,
            self::GATEWAY_AUTH_TYPE         => AuthType::OTP,
        ],
        [
            self::MERCHANT_ID               => Account::SHARED_ACCOUNT,
            self::GATEWAY                   => Gateway::CYBERSOURCE,
            self::AUTHENTICATION_GATEWAY    => Gateway::MPI_ENSTAGE,
            self::AUTH_TYPE                 => AuthType::OTP,
            self::GATEWAY_AUTH_TYPE         => AuthType::OTP,
        ],
        [
            self::MERCHANT_ID               => Account::SHARED_ACCOUNT,
            self::GATEWAY                   => Gateway::CYBERSOURCE,
            self::AUTHENTICATION_GATEWAY    => Gateway::MPI_BLADE,
            self::AUTH_TYPE                 => AuthType::HEADLESS_OTP,
            self::GATEWAY_AUTH_TYPE         => AuthType::_3DS,
        ],
        [
            self::MERCHANT_ID               => Account::SHARED_ACCOUNT,
            self::GATEWAY                   => Gateway::AXIS_MIGS,
            self::CAPABILITY                => Capability::AUTHORIZE,
            self::AUTHENTICATION_GATEWAY    => Gateway::MPI_BLADE,
            self::AUTH_TYPE                 => AuthType::_3DS,
            self::GATEWAY_AUTH_TYPE         => AuthType::_3DS,
        ],
        [
            self::MERCHANT_ID               => Account::SHARED_ACCOUNT,
            self::GATEWAY                   => Gateway::AXIS_MIGS,
            self::CAPABILITY                => Capability::AUTHORIZE,
            self::AUTHENTICATION_GATEWAY    => Gateway::MPI_BLADE,
            self::AUTH_TYPE                 => AuthType::IVR,
            self::GATEWAY_AUTH_TYPE         => AuthType::OTP,
        ],
        [
            self::MERCHANT_ID               => Account::SHARED_ACCOUNT,
            self::GATEWAY                   => Gateway::AXIS_MIGS,
            self::CAPABILITY                => Capability::AUTHORIZE,
            self::AUTHENTICATION_GATEWAY    => Gateway::MPI_ENSTAGE,
            self::AUTH_TYPE                 => AuthType::OTP,
            self::GATEWAY_AUTH_TYPE         => AuthType::OTP,
        ],
        [
            self::MERCHANT_ID               => Account::SHARED_ACCOUNT,
            self::GATEWAY                   => Gateway::AXIS_MIGS,
            self::CAPABILITY                => Capability::AUTHORIZE,
            self::AUTHENTICATION_GATEWAY    => Gateway::MPI_BLADE,
            self::AUTH_TYPE                 => AuthType::HEADLESS_OTP,
            self::GATEWAY_AUTH_TYPE         => AuthType::_3DS,
        ],
        [
            self::MERCHANT_ID               => Account::SHARED_ACCOUNT,
            self::GATEWAY                   => Gateway::FIRST_DATA,
            self::AUTHENTICATION_GATEWAY    => Gateway::MPI_BLADE,
            self::AUTH_TYPE                 => AuthType::_3DS,
            self::GATEWAY_AUTH_TYPE         => AuthType::_3DS,
        ],
        [
            self::MERCHANT_ID               => Account::SHARED_ACCOUNT,
            self::GATEWAY                   => Gateway::FIRST_DATA,
            self::AUTHENTICATION_GATEWAY    => Gateway::MPI_BLADE,
            self::AUTH_TYPE                 => AuthType::IVR,
            self::GATEWAY_AUTH_TYPE         => AuthType::OTP,
        ],
        [
            self::MERCHANT_ID               => Account::SHARED_ACCOUNT,
            self::GATEWAY                   => Gateway::FIRST_DATA,
            self::AUTHENTICATION_GATEWAY    => Gateway::MPI_BLADE,
            self::AUTH_TYPE                 => AuthType::HEADLESS_OTP,
            self::GATEWAY_AUTH_TYPE         => AuthType::_3DS,
        ],
        [
            self::MERCHANT_ID               => Account::SHARED_ACCOUNT,
            self::GATEWAY                   => Gateway::HITACHI,
            self::AUTHENTICATION_GATEWAY    => Gateway::PAYSECURE,
            self::AUTH_TYPE                 => AuthType::_3DS,
            self::GATEWAY_AUTH_TYPE         => AuthType::_3DS,
        ],
        [
            self::MERCHANT_ID               => Account::SHARED_ACCOUNT,
            self::GATEWAY                   => Gateway::PAYSECURE,
            self::AUTHENTICATION_GATEWAY    => null,
            self::AUTH_TYPE                 => AuthType::_3DS,
            self::GATEWAY_AUTH_TYPE         => AuthType::_3DS,
        ],
        [
            self::MERCHANT_ID               => Account::SHARED_ACCOUNT,
            self::GATEWAY                   => Gateway::CARD_FSS,
            self::AUTHENTICATION_GATEWAY    => null,
            self::AUTH_TYPE                 => AuthType::_3DS,
            self::GATEWAY_AUTH_TYPE         => null,
        ],
        [
            self::MERCHANT_ID               => Account::SHARED_ACCOUNT,
            self::GATEWAY                   => Gateway::AMEX,
            self::AUTHENTICATION_GATEWAY    => null,
            self::AUTH_TYPE                 => AuthType::_3DS,
            self::GATEWAY_AUTH_TYPE         => null,
        ],
        [
            self::MERCHANT_ID               => Account::SHARED_ACCOUNT,
            self::GATEWAY                   => Gateway::SHARP,
            self::AUTHENTICATION_GATEWAY    => null,
            self::AUTH_TYPE                 => AuthType::_3DS,
            self::GATEWAY_AUTH_TYPE         => null,
        ],
        [
            self::MERCHANT_ID               => Account::SHARED_ACCOUNT,
            self::GATEWAY                   => Gateway::SHARP,
            self::AUTHENTICATION_GATEWAY    => Gateway::GOOGLE_PAY,
            self::AUTH_TYPE                 => AuthType::_3DS,
            self::GATEWAY_AUTH_TYPE         => null,
        ],
        [
            self::MERCHANT_ID               => Account::SHARED_ACCOUNT,
            self::GATEWAY                   => Gateway::SHARP,
            self::AUTHENTICATION_GATEWAY    => null,
            self::AUTH_TYPE                 => AuthType::HEADLESS_OTP,
            self::GATEWAY_AUTH_TYPE         => null,
        ],
        [
            self::MERCHANT_ID               => Account::SHARED_ACCOUNT,
            self::GATEWAY                   => Gateway::SHARP,
            self::AUTHENTICATION_GATEWAY    => null,
            self::AUTH_TYPE                 => AuthType::OTP,
            self::GATEWAY_AUTH_TYPE         => null,
        ],
        [
            self::MERCHANT_ID               => Account::SHARED_ACCOUNT,
            self::GATEWAY                   => Gateway::SHARP,
            self::AUTHENTICATION_GATEWAY    => null,
            self::AUTH_TYPE                 => AuthType::IVR,
            self::GATEWAY_AUTH_TYPE         => null,
        ],
        [
            self::MERCHANT_ID               => Account::SHARED_ACCOUNT,
            self::GATEWAY                   => Gateway::SHARP,
            self::AUTHENTICATION_GATEWAY    => Gateway::VISA_SAFE_CLICK,
            self::AUTH_TYPE                 => AuthType::_3DS,
            self::GATEWAY_AUTH_TYPE         => null,
        ],
        [
            self::MERCHANT_ID               => Account::SHARED_ACCOUNT,
            self::GATEWAY                   => Gateway::AXIS_TOKENHQ,
            self::AUTHENTICATION_GATEWAY    => Gateway::AXIS_TOKENHQ,
            self::AUTH_TYPE                 => AuthType::OTP,
            self::GATEWAY_AUTH_TYPE         => AuthType::OTP,
        ],
        [
            self::MERCHANT_ID               => Account::SHARED_ACCOUNT,
            self::GATEWAY                   => Gateway::MPGS,
            self::AUTHENTICATION_GATEWAY    => Gateway::MPGS,
            self::AUTH_TYPE                 => AuthType::_3DS,
            self::GATEWAY_AUTH_TYPE         => AuthType::_3DS,
        ],
        [
            self::MERCHANT_ID               => Account::SHARED_ACCOUNT,
            self::GATEWAY                   => Gateway::MPGS,
            self::AUTHENTICATION_GATEWAY    => Gateway::MPGS,
            self::AUTH_TYPE                 => AuthType::HEADLESS_OTP,
            self::GATEWAY_AUTH_TYPE         => AuthType::_3DS,
        ],
        [
            self::MERCHANT_ID               => Account::SHARED_ACCOUNT,
            self::GATEWAY                   => Gateway::MPGS,
            self::AUTHENTICATION_GATEWAY    => null,
            self::AUTH_TYPE                 => AuthType::SKIP,
            self::GATEWAY_AUTH_TYPE         => null,
        ],
        [
            self::MERCHANT_ID               => Account::SHARED_ACCOUNT,
            self::GATEWAY                   => Gateway::MPGS,
            self::AUTHENTICATION_GATEWAY    => Gateway::MPGS,
            self::AUTH_TYPE                 => AuthType::_3DS,
            self::GATEWAY_AUTH_TYPE         => AuthType::_3DS,
            self::GATEWAY_AUTH_VERSION      => "v2",
        ],
        [
            self::MERCHANT_ID               => Account::SHARED_ACCOUNT,
            self::GATEWAY                   => Gateway::MPGS,
            self::AUTHENTICATION_GATEWAY    => Gateway::MPGS,
            self::AUTH_TYPE                 => AuthType::HEADLESS_OTP,
            self::GATEWAY_AUTH_TYPE         => AuthType::_3DS,
            self::GATEWAY_AUTH_VERSION      => "v2",
        ],
        [
            self::MERCHANT_ID               => Account::SHARED_ACCOUNT,
            self::GATEWAY                   => Gateway::ICICI,
            self::AUTHENTICATION_GATEWAY    => Gateway::ICICI,
            self::AUTH_TYPE                 => AuthType::OTP,
            self::GATEWAY_AUTH_TYPE         => AuthType::OTP,
        ],

    ];
}
