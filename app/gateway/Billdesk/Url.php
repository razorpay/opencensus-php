<?php

namespace Gateway\Billdesk;

class Url
{
    const TEST_DOMAIN   = 'https://pgi.billdesk.com';
    const LIVE_DOMAIN   = 'https://secure.paytm.in';

    const AUTHORIZE     = '/pgidsk/pgmerc/RZRPYRedirect.jsp';
    const QUERY         = '/oltp/HANDLER_INTERNAL/TXNSTATUS';
    const VERIFY        = '/oltp/HANDLER_INTERNAL/REFUND';
}