<?php

namespace RZP\Models\Batch;

class Constants
{
    const PROCESSABLE_COUNT              = 'processable_count';
    const ERROR_COUNT                    = 'error_count';
    const PARSED_ENTRIES                 = 'parsed_entries';
    const ROW_LEVEL_VALIDATION_THRESHOLD = 5000;
    const TO_UPPER_CASE                  = 1;
    const TO_LOWER_CASE                  = 2;
    const BATCH_SERVICE                  = 'batch_service';
    const DATA                           = 'data';
    const BANK_DETAILS                   = 'bank_details';
    const CATEGORY_DETAILS               = 'category_details';
    const CONFIG_PARAMS                  = 'config_params';
    const DEFAULT                        = 'default';
    const BATCH_ACTION                   = 'batch_action';
    const ENTITY                         = 'entity';
    const ACTION                         = 'action';
    const NON_MIGRATED_BATCH             = 'non_migrated_batch';

    // Validate Batch filename
    const BATCH_TYPE_ID                  = 'batch_type_id';
    const VALIDATE_FILE_NAME_URL         = 'validateFileName';
    const FILENAME                       = 'filename';
    const TALLY_PAYOUT_BATCH             = 'tally_payout';

    const IDEMPOTENCY_KEY                = 'idempotency_key';
    const TYPE                           = 'type';
    const SCHEDULE                       = 'schedule';

    // Batch targets that are not actual gateways
    const ENACH_NB_ICICI                 = 'enach_nb_icici';
    const ENACH_RBL                      = 'enach_rbl';

    //actions
    const CANCEL                         = 'cancel';
}
