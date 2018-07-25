<?php

namespace RZP\Gateway\Mpi\Blade;

class Url
{
    // VISA Directory Server
    const LIVE_VISA_DS       = 'https://dswc.visa3dsecure.com/DSMsgServlet';

    // MasterCard Directory Server for SHA2 Client Certificate
    const LIVE_MASTERCARD_DS = 'https://mcdirectory.securecode.com';
    const LIVE_MAESTRO_DS    = 'https://mcdirectory.securecode.com';

    const TEST_VISA_DS       = 'https://pit-wsi.3dsecure.net:5443/ds';
    const TEST_MASTERCARD_DS = 'https://mcdirectory.securecode.com';
    const TEST_MAESTRO_DS    = 'https://mcdirectory.securecode.com';
}
