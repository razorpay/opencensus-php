<?php

namespace RZP\Error\P2p;

use RZP\Models\P2p\Vpa;
use RZP\Models\P2p\Device;
use RZP\Models\P2p\BankAccount;

class Action
{
    const MAP = [
        ErrorCode::BAD_REQUEST_UNAUTHORIZED_INVALID_API_SECRET      => Device\Action::INITIATE_VERIFICATION,
        ErrorCode::BAD_REQUEST_DEVICE_NOT_ATTACHED_TO_HANDLE        => Device\Action::INITIATE_VERIFICATION,
        ErrorCode::GATEWAY_ERROR_DEVICE_INVALID_TOKEN               => Device\Action::INITIATE_GET_TOKEN,
        ErrorCode::BAD_REQUEST_TOKEN_EXPIRED_NOT_VALID              => Device\Action::INITIATE_GET_TOKEN,

        ErrorCode::BAD_REQUEST_NO_BANK_ACCOUNT_FOUND                => BankAccount\Action::INITIATE_RETRIEVE,

        ErrorCode::BAD_REQUEST_VPA_NOT_AVAILABLE                    => Vpa\Action::INITIATE_CHECK_AVAILABILITY,
        ErrorCode::BAD_REQUEST_DUPLICATE_VPA                        => Vpa\Action::INITIATE_CHECK_AVAILABILITY,
    ];
}
