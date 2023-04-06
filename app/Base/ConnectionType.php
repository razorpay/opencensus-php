<?php

namespace RZP\Base;

class ConnectionType
{
    const MASTER                        = 'master';
    const SLAVE                         = 'slave';
    const REPLICA                       = 'replica';
    const ARCHIVED_DATA_REPLICA         = 'archived-data-replica';
    const PAYMENT_FETCH_REPLICA         = 'payment-fetch-replica';
    const DATA_WAREHOUSE_ADMIN          = 'data-warehouse-admin';
    const DATA_WAREHOUSE_MERCHANT       = 'data-warehouse-merchant';
    const RX_DATA_WAREHOUSE_MERCHANT    = 'rx-data-warehouse-merchant';
    const RX_ACCOUNT_STATEMENTS         = 'rx-account-statements';
    const RX_WHATSAPP_LIVE              = 'rx_whatsapp_live';
}
