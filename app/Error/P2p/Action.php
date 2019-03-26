<?php

namespace RZP\Error\P2p;

use RZP\Models\P2p\Vpa;
use RZP\Models\P2p\Device;

class Action
{
    const MAP = [
        ErrorCode::BAD_REQUEST_DEVICE_NOT_ATTACHED_TO_HANDLE        => Device\Action::INITIATE_VERIFICATION,
        ErrorCode::GATEWAY_ERROR_DEVICE_INVALID_TOKEN               => Device\Action::INITIATE_GET_TOKEN,

        ErrorCode::BAD_REQUEST_DUPLICATE_VPA                        => Vpa\Action::INITIATE_CHECK_AVAILABILITY,
    ];
}
