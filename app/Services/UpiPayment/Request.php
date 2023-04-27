<?php

namespace RZP\Services\UpiPayment;

class Request
{
    // request constants
    const URL       = 'url';
    const METHOD    = 'method';
    const CONTENT   = 'content';
    const HEADERS   = 'headers';
    const OPTIONS   = 'options';

    // http Methods
    const POST = 'POST';

    // header parameters
    const CONTENT_TYPE_HEADER       = 'Content-Type';
    const ACCEPT_HEADER             = 'Accept';
    const X_RAZORPAY_APP_HEADER     = 'Grpc-Metadata-X-Razorpay-App';
    const X_RAZORPAY_TASKID_HEADER  = 'Grpc-Metadata-X-Razorpay-TaskId';
    const X_REQUEST_ID              = 'Grpc-Metadata-X-Request-ID';
    const X_RAZORPAY_TRACKID        = 'Grpc-Metadata-X-Razorpay-TrackId';
    const AUTH_HEADER               = 'Authorization';
    const X_RZP_TESTCASE_ID         = 'Grpc-Metadata-X-RZP-TESTCASE-ID';

    //Content Type
    const APPLICATION_JSON          = 'application/json';


    const MODEL             = 'model';
    const COLUMN_NAME       = 'column_name';
    const VALUE             = 'value';
    const PAYMENT_ID        = 'payment_id';
    const GATEWAY           = 'gateway';
    const REQUIRED_FIELDS   = 'required_fields';

    // Dashboard Entity Fetch Fields
    const ENTITY_NAME       = 'entity_name';
    const ID                = 'id';
    const COUNT             = 'count';
    const SKIP              = 'skip';
    const FROM              = 'from';
    const TO                = 'to';
    const INCLUDE_DELETED   = 'include_deleted';
}
