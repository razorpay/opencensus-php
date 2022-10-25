<?php


namespace RZP\Models\Merchant\Acs\AsvClient;

use Twirp\Error;
use Twirp\Context;
use RZP\Trace\TraceCode;
use RZP\Constants\Metric;
use Google\ApiCore\ValidationException;
use RZP\Exception\IntegrationException;
use \Rzp\Accounts\SyncDeviation\V1\Metadata;
use Rzp\Accounts\SyncDeviation\V1 as syncDeviationV1;


class SyncAccountDeviationAsvClient extends BaseClient
{
    private $SyncAccountDeviationAsvClient;

    /**
     * SyncAccountDeviationAsvClient Constructor
     */
    function __construct(syncDeviationV1\SyncDeviationAPIClient $syncDeviationAsvClient = null)
    {
        parent::__construct();
        if ($syncDeviationAsvClient === null) {
            $this->SyncAccountDeviationAsvClient = new syncDeviationV1\SyncDeviationAPIClient($this->host, $this->httpClient);
        } else {
            $this->SyncAccountDeviationAsvClient = $syncDeviationAsvClient;
        }
    }

    /**
     * @param array $payload
     * @return syncDeviationV1\SyncAccountDeviationResponse
     * @throws Error
     * @throws ValidationException
     */
    public function syncAccountDeviation(array $payload = [])
    {
        // Set Timeout for Sync Deviation
        $syncDeviationTimeout = floatval($this->asvConfig[Constant::SYNC_DEVIATION_ROUTE_HTTP_TIMEOUT_SEC]);
        $this->SyncAccountDeviationAsvClient->setTimeout($syncDeviationTimeout);

        $this->trace->info(TraceCode::ASV_SYNC_ACCOUNT_DEVIATION_REQUEST, $payload);

        if (!$this->isInputPayloadValid($payload)) {
            throw new ValidationException(' some of account_id, mode, mock or metadata fields are not present or contains invalid values');
        }

        $accountId = $payload[Constant::ACCOUNT_ID];
        $mock = $payload[Constant::MOCK];
        $mode = $payload[Constant::MODE];
        $metadata = $payload[Constant::METADATA];

        $taskId = $metadata[Constant::TASK_ID] ?? gen_uuid();
        $this->headers[Constant::X_TASK_ID] = $taskId;
        $this->asvClientCtx = Context::withHttpRequestHeaders([], $this->headers);

        $requestSuccess = 'false';
        $startTime = 0;
        $endTime = 0;


        try {
            $metadataProto = new Metadata($metadata);
            $syncAccountDeviationRequest = new syncDeviationV1\SyncAccountDeviationRequest();

            $syncAccountDeviationRequest->setAccountId($accountId);
            $syncAccountDeviationRequest->setMock($mock);
            $syncAccountDeviationRequest->setMode($mode);
            $syncAccountDeviationRequest->setMetadata($metadataProto);

            $startTime = millitime();
            $response = $this->SyncAccountDeviationAsvClient->SyncAccountDeviation($this->asvClientCtx, $syncAccountDeviationRequest);
            $endTime = millitime();

            $requestSuccess = 'true';

            $this->trace->count(Metric:: ASV_SYNC_ACCOUNT_DEVIATION_TOTAL,
                [
                    Metric::LABEL_IS_SUCCESS => $requestSuccess,
                    Metric::LABEL_ERROR_CODE => '',
                ]
            );

            $this->trace->histogram(Metric::ASV_SYNC_ACCOUNT_DEVIATION_DURATION_MS, $endTime - $startTime,
                [
                    Metric::LABEL_IS_SUCCESS => $requestSuccess,
                    Metric::LABEL_ERROR_CODE => '',
                ]
            );


            $this->trace->info(
                TraceCode::ASV_SYNC_ACCOUNT_DEVIATION_RESPONSE,
                [
                    Constant::RESPONSE => $response->serializeToJsonString()
                ]
            );

            return $response;

        } catch (Error $e) {
            $endTime = millitime();

            $this->trace->traceException($e, null, TraceCode::ASV_SYNC_ACCOUNT_DEVIATION_ERROR);

            $this->trace->count(
                Metric:: ASV_SYNC_ACCOUNT_DEVIATION_TOTAL,
                [
                    Metric::LABEL_IS_SUCCESS => $requestSuccess,
                    Metric::LABEL_ERROR_CODE => $e->getErrorCode(),
                ]
            );

            $this->trace->histogram(Metric::ASV_SYNC_ACCOUNT_DEVIATION_DURATION_MS, $endTime - $startTime,
                [
                    Metric::LABEL_IS_SUCCESS => $requestSuccess,
                    Metric::LABEL_ERROR_CODE => $e->getErrorCode(),
                ]
            );

            throw new IntegrationException('Could not receive proper response from Account service');

        }
    }

    /**
     * @param array $input
     * @return bool
     */
    public function isInputPayloadValid(array $input)
    {
        if (array_key_exists(Constant::ACCOUNT_ID, $input) && array_key_exists(Constant::MODE, $input) &&
            array_key_exists(Constant::MOCK, $input) && array_key_exists(Constant::METADATA, $input) &&
            is_array($input[Constant::METADATA]) && strlen($input[Constant::ACCOUNT_ID]) === 14) {
            return true;
        } else {
            return false;
        }
    }
}
