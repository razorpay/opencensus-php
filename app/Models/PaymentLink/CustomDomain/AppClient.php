<?php

namespace RZP\Models\PaymentLink\CustomDomain;

use Twirp\Error;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Exception\IntegrationException;
use Rzp\CustomDomainService\App\V1 as AppV1;

final class AppClient extends AbstractCDSClient implements IAppClient
{
    const ID_KEY            = 'id';
    const APP_NAME_KEY      = 'app_name';
    const CALLBACK_URL_KEY  = 'callback_url';

    /**
     * @var \Rzp\CustomDomainService\App\V1\AppAPIClient
     */
    private $api;

    public function __construct()
    {
        parent::__construct();

        $this->setApi(new AppV1\AppAPIClient($this->host, $this->httpClient));
    }

    /**
     * @param array $data
     *
     * @return array
     * @throws \RZP\Exception\IntegrationException
     */
    public function createApp(array $data): array
    {
        $this->trace->info(TraceCode::CDS_APP_CREATE_RECIEVED, $data);

        try
        {
            $response = $this->api->Create($this->apiClientCtx, $this->buildAppCreateRequest($data));

            $serialized = $this->serializeResponse($response);

            $this->trace->info(TraceCode::CDS_APP_CREATE_RESPONSE_RECIEVED, $serialized);

            return $serialized;
        }
        catch (Error $e)
        {
            $this->trace->traceException($e, null, TraceCode::CDS_INTEGRATION_ERROR, $e->getMetaMap());

            throw new IntegrationException('Could not receive proper response from custom domain service', $e->getErrorCode()==='not_found'?ErrorCode::BAD_REQUEST_NO_RECORDS_FOUND:null);
        }
    }

    /**
     * @param array $data
     *
     * @return \Rzp\CustomDomainService\App\V1\AppRequest
     */
    private function buildAppCreateRequest(array $data): AppV1\AppRequest
    {
        $req = new AppV1\AppRequest();

        $req->setAppName($data[self::APP_NAME_KEY]);
        $req->setCallbackUrl($data[self::CALLBACK_URL_KEY]);

        return $req;
    }

    /**
     * @param \Rzp\CustomDomainService\App\V1\AppResponse $response
     *
     * @return array
     */
    private function serializeResponse(AppV1\AppResponse $response): array
    {
        return [
            self::ID_KEY            => $response->getId(),
            self::APP_NAME_KEY      => $response->getAppName(),
            self::CALLBACK_URL_KEY  => $response->getCallbackUrl(),
        ];
    }

    public function setApi($client)
    {
        $this->api = $client;
    }

    public function getApi()
    {
        return $this->api;
    }
}
