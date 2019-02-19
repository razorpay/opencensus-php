<?php

namespace RZP\Gateway\Enach\Npci\Netbanking;

class NpciXmlHeaderTags
{
    const MANDATE_ROOT_HEADER      = 'MndtAuthReq';
    const GROUP_HEADER             = 'GrpHdr';
    const REQUEST_INITIATING_PARTY = 'ReqInitPty';
    const INFO                     = 'Info';
    const MANDATE                  = 'Mndt';
    const OCCURENCE                = 'Ocrncs';
    const DEBTOR                   = 'Dbtr';
    const CREDITOR                 = 'CrAccDtl';
}
