<?php

namespace RZP\Reconciliator\Base\SubReconciliator\Upi;


final class Constants
{
    // Metro topic names
    const RECON_ENTITY_UPDATE = 'recon-entity-update';
    const ART_RECON_ENTITY_UPDATE = 'art-recon-entity-update';

    // Metro push request parameters
    const GATEWAY_DATA = 'gateway_data';
    const PAYMENT_ID = 'payment_id';
    const GATEWAY = 'gateway';
    const BATCH_ID = 'batch_id';

    // UPS authorize entity columns
    const NPCI_TXN_ID = 'npci_txn_id';
    const CUSTOMER_REFERENCE = 'customer_reference';
    const GATEWAY_REFERENCE = 'gateway_reference';
    const RECONCILED_AT = 'reconciled_at';

    // Models in UPS
    const AUTHORIZE = 'authorize';

    // Entity fetch request paramenters
    const MODEL = 'model';
    const REQUIRED_FIELDS = 'required_fields';
    const COLUMN_NAME = 'column_name';
    const VALUE = 'value';

    // Actions
    const ENTITY_FETCH      = 'entity_fetch';
    const RECON_ENTITY_SYNC_UPDATE = 'recon_entity_update';
}
