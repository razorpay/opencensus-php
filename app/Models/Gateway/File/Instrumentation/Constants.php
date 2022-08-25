<?php

namespace RZP\Models\Gateway\File\Instrumentation;

class Constants
{
    // File Generation Instrumentation Parameters
    const BATCH_ID           =  'batch_id';
    const METHOD             =  'method';
    const PAYMENT_ID         =  'payment_id';
    const PAYMENT_STATUS     =  'payment_status';
    const FILE_NAME          =  'input_file_name';
    const FILE_ID            =  'file_id';
    const FILE_SIZE          =  'file_size';
    const TOTAL_RECORDS      =  'total_records';
    const GATEWAY            =  'gateway';
    const TARGET             =  'target';
    const TYPE               =  'type';
    const OFFSET             =  'offset';
    const UTILITY_CODE       =  'utility_code';
    const AMOUNT             =  'amount';
    const CREATED_AT         =  'created_at';
    const UPDATED_AT         =  'updated_at';
    const GENERATED          =  'generated_at';
    const BEGIN              =  'begin';
    const END                =  'end';
    const EVENT_NAME         =  'event_name';
    const EVENT_TYPE         =  'event_type';
    const VERSION            =  'version';
    const EVENT_TIMESTAMP    =  'event_timestamp';
    const PRODUCER_TIMESTAMP =  'producer_timestamp';
    const SOURCE             =  'source';
    const MODE               =  'mode';
    const PROPERTIES         =  'properties';
    const CONTEXT            =  'context';
    const REQUEST_ID         =  'request_id';
    const KAFKA_TOPIC        =  'events.emandate-file-generation-debit.v1.';
}
