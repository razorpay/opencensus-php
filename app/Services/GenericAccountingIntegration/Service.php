<?php

namespace RZP\Services\GenericAccountingIntegration;

use Illuminate\Http\Request;
use Illuminate\Routing\Route as IlluminateRoute;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Exception\ServerErrorException;
use RZP\Http\Controllers\EdgeProxyController;
use RZP\Http\Response\StatusCode;
use RZP\Models\Payout\Entity as PayoutEntity;
use RZP\Models\PayoutsDetails\Entity as PayoutsDetailsEntity;
use RZP\Trace\TraceCode;

class Service {
    protected $app;

    protected $trace;

    const BASE_PATH = 'v1/accounting-integrations';
    const CREATE_INVITATION = 'invitations';
    const PUSH_PAYOUTS_STATUS_UPDATE_EVENT = 'workflows/trigger_payouts_sync';

    public function __construct($app) {

        $this->app = $app;

        $this->trace = $app['trace'];
    }

    public function createOrUpdateInvitation(array $input)
    {
        $url = sprintf('%s/%s', self::BASE_PATH, self::CREATE_INVITATION);

        return $this->makeRequest($url,'POST', $input);
    }

    public function pushPayoutStatusUpdate(PayoutEntity $payout, string $mode)
    {
        $trace = $this->app['trace'];

        $trace->info(TraceCode::GENERIC_ACCOUNTING_SERVICE_PAYOUT_PUSH, [
            "payout_request" => $payout,
        ]);

        $url = sprintf('%s/%s', self::BASE_PATH, self::PUSH_PAYOUTS_STATUS_UPDATE_EVENT);

        $input = $this->makePayoutRequestBody($payout);

        $meta = $payout->getPayoutMeta();

        $input["attachments"] = $meta[PayoutsDetailsEntity::ATTACHMENTS] ? $meta[PayoutsDetailsEntity::ATTACHMENTS] :[];

        $trace->info(TraceCode::GENERIC_ACCOUNTING_SERVICE_PAYOUT_REQUEST, [
            "payout" => $input,
        ]);

        return $this->makeRequest($url,'POST', $input);
    }

    function makePayoutRequestBody(PayoutEntity $payout)
    {
        return [
            "id"                     => $payout->getPublicId(),
            "reference_id"           => $payout->getReferenceId(),
            "merchant_id"            => $payout->getMerchantId(),
            "status"                 => $payout->getStatus(),
            "banking_account_number" => $payout->balance->getAccountNumber(),
            "fund_account_id"        => $payout->fundAccount->getPublicId(),
            "contact_id"             => $payout->fundAccount->contact->getPublicId(),
            "utr"                    => $payout->getUtr(),
            "amount"                 => $payout->getAmount(),
            "user_id"                => $payout->getUserId(),
            "mode"                   => $payout->getMode(),
            "purpose"                => $payout->getPurpose(),
            "created_at"             => $payout->getCreatedAt(),
            "currency"               => $payout->getCurrency(),
            "processed_at"           => $payout->getProcessedAt(),
            "reversed_at"            => $payout->getReversedAt(),
            "narration"              => $payout->getNarration(),
            "notes"                  => $payout->getNotes(),
        ];
    }

    /**
     * @param string $url
     * @param string $method
     * @param bool $content
     * @return mixed
     *
     * @throws BadRequestException
     * @throws ServerErrorException
     * @throws \RZP\Exception\IntegrationException
     * @throws \Throwable
     */

    protected function makeRequest(string $url, string $method = 'POST', $content = null): mixed
    {
        $req = Request::create($url, 'POST', [], [], [], [], json_encode($content));

        $params = array($method, self::BASE_PATH);
        $name = "accounting_integrations_proxy_routes";
        $req->setRouteResolver(function () use ($req, $name, $params)
        {
            return (new IlluminateRoute($params[0], $params[1], ['as' => $name]))->bind($req);
        });

        // add try catch
        // if exceptions then log, throw new ServerErrorException($description, ErrorCode::SERVER_ERROR);

        $this->trace->info(TraceCode::INTEGRATION_INVITATION_REQUEST,
            [
                'url'     => $url,
                'content' => $content,
            ]);

        try {

            $response = (new EdgeProxyController)->proxy($req);

        }catch (\Throwable $e)
        {
            $this->trace->info(TraceCode::INTEGRATION_INVITATION_REQUEST,
                [
                    'message'             => 'exception',
                    'error'               => $e->getMessage(),
                ]);

            //throw new ServerErrorException($e->getMessage(), ErrorCode::SERVER_ERROR);
            throw $e;
        }

        $responseBody = json_decode($response->getContent(), true);

        if ($response->getStatusCode() !== StatusCode::SUCCESS)
        {
            if ($responseBody !== null)
            {
                $description = array_pull($responseBody, 'message', "Error occurred");
            }
            else
            {
                $description = 'received empty response';
            }

            throw new BadRequestException(ErrorCode::BAD_REQUEST_ACCOUNTING_PAYOUTS_SERVICE_FAILED,
                null,
                $description,
                $description);
        }

        return $responseBody;
    }
}

