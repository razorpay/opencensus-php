<?php


namespace RZP\Models\BankingAccount;


class Constants
{
    const BANKING_ACCOUNT_RESET_WEBHOOK_COMMENT = 'System Comment: Admin is reseting the webhook data and reverting back to the previous state. Bank has to trigger the account opening webhook again.';

    const X_OPS_TEAM = 'ops';

    const BANKING_ACCOUNT_SOURCE_TEAM_OR_TYPE_AS_INTERNAL = 'internal';

    const STATUS_MESSAGE = 'StatusMessage';

    const SUCCESS_MESSAGE = 'Data Successfully Inserted';

    const ERROR_DESC = 'ErrorDesc';

    const ERROR_MESSAGE = 'A schema validation error has occurred while validating the message tree,6008,1,1,213,cvc-minLength-valid: The length of value \"\" is \"0\" which is not valid with respect to the minLength facet with value \"1\" for type \"#Anonymous\".,/Root/XMLNSC/NeoBankingLeadReq/Body/%s';
}
