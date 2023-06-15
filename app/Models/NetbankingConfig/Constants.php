<?php

namespace RZP\Models\NetbankingConfig;

class Constants {

    const KEY = "rzp/pg/merchant/netbanking/banking_program/NetBankingConfiguration";

    const MERCHANT_ID = 'merchant_id';

    const ENTITY_ID = 'entity_id';

    const FIELDS = "fields";

    const AUTO_REFUND_OFFSET = 'auto_refund_offset';

    const NETBANKING_CONFIGS = [
        self::AUTO_REFUND_OFFSET,
    ];
}
