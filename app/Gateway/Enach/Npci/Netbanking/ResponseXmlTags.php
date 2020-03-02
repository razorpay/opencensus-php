<?php

namespace RZP\Gateway\Enach\Npci\Netbanking;

// All Fields that are present in the xml response
class ResponseXmlTags
{
    // tags
    const MESSAGE_ID                         = 'MsgId';
    const CREATION_DATE_TIME                 = 'CreDtTm';
    const RESPONSE_PARTY                     = 'ReqInitPty';
    const MANDATE_REQUEST_ID                 = 'MndtReqId';
    const ORIGINGAL_MSG_ID                   = 'NPCI_RefMsgId';
    const MANDATE_REQUEST_CREATION_DATE_TIME = 'CreDtTm';
    const ACCEPTED                           = 'Accptd';
    const ACCEPT_REF_NO                      = 'AccptRefNo';
    const REJECTION_CODE                     = 'ReasonCode';
    const REJECT_DESCRIPTION                 = 'ReasonDesc';
    const REJECTION_BY                       = 'RejectBy';
    const DEBTOR_IFSC                        = 'IFSC';
    const MANDATE_ID                         = 'MndtId';

    //headers
    const MANDATE_ACCEPT_RESPONSE  = 'MndtAccptResp';
    const GROUP_HEADER             = 'GrpHdr';
    const ACCEPT_DETAILS           = 'UndrlygAccptncDtls';
    const ORIGINAL_MSG_INFO        = 'OrgnlMsgInf';
    const ACCEPT_RESULT            = 'AccptncRslt';
    const REJECT_REASON            = 'RjctRsn';
    const DEBTOR                   = 'DBTR';

    //error response tags
    const ERROR_CODE        = 'ErrorCode';
    const ERROR_DESCRIPTION = 'ErrorDesc';


    //error response headers
    const MANDATE_REJECT_RESPONSE = 'MndtRejResp';
    const ORIGINIAL_REQUEST_INFO  = 'OrigReqInfo';
    const MANDATE_ERROR_DETAILS   = 'MndtErrorDtls';

    //user defined tag : this is not sent by NPCI. Creating this for readability and used in callback code
    const REQUEST_DATE_TIME = 'Mandate_Request_Creation_Date_Time';

    // verify response
    const MERCHANT_ID     = 'MerchantID';
    const REQ_INIT_DATE   = 'ReqInitDate';
    const VER_NPCI_REF_ID = 'npcirefmsgID';
}
