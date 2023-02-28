<?php

namespace RZP\Models\Payout\TdsProcessor;

class Constants
{
    const TDS_PROCESSOR_KAFKA_TOPIC = 'add-tds-entry';

    const ENTITY_TYPE               = 'payout';

    const EXPERIMENT_KEY        = "app.vendor_payment_metro_to_kafka_exp_id";
    const VARIANT               = "main";
}
