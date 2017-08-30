<?php

namespace RZP\Gateway\Blade;

class Url
{
    // VISA Directory Server
    const LIVE_VISA_DS = 'https://dcwc.visa3dsecure.com/DSMsgServlet';

    // MasterCard Directory Server
    const LIVE_MC_DS   = 'https://mcdirectory.securecode.com';

    // For PIT testing and CTH testing
    const PIT_DS = 'https://dropit.3dsecure.net:9443/PIT/DS';
    const CTH_DS = 'https://3dsecuretestfacility.com:9660/cth/md/Razorpay+Blade+v1.0';
}