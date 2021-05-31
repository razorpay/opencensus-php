<?php

namespace RZP\Base;

class ConnectionType
{
    const MASTER         = 'master';
    const SLAVE          = 'slave';
    const REPLICA        = 'replica';
    const DATA_WAREHOUSE = 'data-warehouse';
    const DATA_WAREHOUSE_NO_FALLBACK = 'data-warehouse-no-fallback';

}
