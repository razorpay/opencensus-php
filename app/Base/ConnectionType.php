<?php

namespace RZP\Base;

class ConnectionType
{
    const MASTER         = 'master';
    const SLAVE          = 'slave';
    const REPLICA        = 'replica';
    const DATA_WAREHOUSE_ADMIN = 'data-warehouse-admin';
    const DATA_WAREHOUSE_MERCHANT = 'data-warehouse-merchant';
    const DATA_WAREHOUSE_ADMIN_REPLICA = 'data-warehouse-admin-replica';
}
