<?php

namespace RZP\Services\PayoutService;

use RZP\Http\Request\Requests;

use RZP\Trace\TraceCode;

class BankingAccountStatement extends Base
{
    const BANKING_ACCOUNT_STATEMENT_PROCESS_POST_RECON_URI = '/banking_account_statement/process/batch';
    const BANKING_ACCOUNT_STATEMENT_PAYOUT_UPDATE = '/payouts/banking_account_statement/payout_update';
    const BANKING_ACCOUNT_STATEMENT_DEV_ADMIN_LINK_URI = '/dev_admin/banking_account_statement/link';

    const PAYOUT_SERVICE_BANKING_ACCOUNT_STATEMENT = 'payout_service_banking_account_statement';

    public function triggerStatementProcessingPostReconViaMicroService($input)
    {
        $response = $this->makeRequestAndGetContent(
            $input,
            self::BANKING_ACCOUNT_STATEMENT_PROCESS_POST_RECON_URI,
            Requests::POST
        );

        $this->trace->info(
            TraceCode::PAYOUTS_SERVICE_BAS_PROCESS_POST_RECON_RESPONSE,
            [
                'response' => $response,
            ]);
    }

    public function updatePayoutAfterBASRecon($input)
    {
        $response = $this->makeRequestAndGetContent(
            $input,
            self::BANKING_ACCOUNT_STATEMENT_PAYOUT_UPDATE,
            Requests::POST
        );

        $this->trace->info(
            TraceCode::PAYOUTS_SERVICE_PAYOUT_UPDATE_POST_BAS_RECON_RESPONSE,
            [
                'response' => $response,
            ]
        );

        return $response;
    }

    public function devAdminTriggerLinkViaMicroService($input)
    {
        $response = $this->makeRequestAndGetContent(
            $input,
            self::BANKING_ACCOUNT_STATEMENT_DEV_ADMIN_LINK_URI,
            Requests::POST
        );

        $this->trace->info(
            TraceCode::PAYOUTS_SERVICE_BAS_LINK_RESPONSE,
            [
                'response' => $response,
            ]
        );
    }
}
