<?php

namespace RZP\Gateway\Enach\Npci\Netbanking;

class CancelRequestTags
{
    // cancel xml tags/fields
    const MANDATE_CANCEL_REQUEST    = 'MndtCxlReq';
    const GROUP_HEADER              = 'GrpHdr';
    const MSG_ID                    = 'MsgId';
    const CREATION_DATE_TIME        = 'CreDtTm';
    const INSTG_AGT                 = 'InstgAgt';
    const FINANCIAL_INST_ID         = 'FinInstnId';
    const CLR_SYS_MEMBER_ID         = 'ClrSysMmbId';
    const MEMBER_ID                 = 'MmbId';
    const INSTD_AGT                 = 'InstdAgt';
    const NM                        = 'Nm';
    const UNDERLYING_CANCEL_DETAILS = 'UndrlygCxlDtls';
    const CANCEL_RSN                = 'CxlRsn';
    const RSN                       = 'Rsn';
    const PRTRY                     = 'Prtry';
    const ORIGINAL_MANDATE          = 'OrgnlMndt';
    const ORIGINAL_MANDATE_ID       = 'OrgnlMndtId';
}
