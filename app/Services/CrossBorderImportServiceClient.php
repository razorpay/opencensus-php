<?php

namespace RZP\Services;
use GuzzleHttp\Exception\RequestException;
use Request;
use GuzzleHttp\RequestOptions;
use RZP\Constants\Mode;
use RZP\Http\RequestHeader;
use RZP\Trace\TraceCode;
use RZP\Http\Request\Requests;
use GuzzleHttp\Client as Guzzle;
use Illuminate\Support\Facades\App;
use Razorpay\Trace\Logger as Trace;
use RZP\Exception;
use RZP\Models\Payment\Service as PaymentService;
use RZP\Error\ErrorCode;
use RZP\Models\Payout\Entity;
use RZP\Models\Payout\PayoutError;
use RZP\Models\PayoutsStatusDetails as PayoutsStatusDetails;

class CrossBorderImportServiceClient
{
    const CONTENT_TYPE        = 'content-type';
    const CONTENT_TYPE_JSON   = 'application/json';
    const X_TASK_ID           = 'X-Razorpay-TaskId';
    const X_MERCHANT_ID       = 'X-Merchant-ID';
    const X_INTERNAL_APP      = 'X-Internal-App';
    const X_RAZORPAY_MODE     = 'X-Razorpay-Mode';
    const CROSS_BORDER_IMPORT = 'CrossBorderImport';

    //validate payment url
    const VALIDATE_PAYMENT = 'twirp/rzp.cross_border_import.import_payments.v1.ImportPaymentService/ValidateAndSavePayment';

    // Update Payout Status
    const STATUS_UPDATE_PAYOUT = 'twirp/rzp.cross_border_import.internal_transfer.v1.InternalTransferService/StatusUpdatePayout';

    const GET = 'GET';
    const POST = 'POST';

    protected $client;

    protected $options = [];

    protected $trace;

    protected $config;

    protected $mode;

    protected $app;

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->app = $app;

        $this->mode = $this->app['rzp.mode'] ?? Mode::LIVE;

        $this->trace = $app['trace'];

        $this->config = $app['config']->get('applications.cross_border_import_service');

        $this->client = new Guzzle([
            'base_uri' => $this->config['url'][$this->mode],
            'auth'     => [
                $this->config['username'],
                $this->config['password'],
            ]]);
    }

    /**
     *
     * @return \WpOrg\Requests\Response
     * @throws \WpOrg\Requests\Exception
     */
    public function makeRequest($path, $method, $payload=[], $headers = [])
    {
        $url = $this->config['url'][$this->mode] . $path;

        $headers = array_merge($headers, $this->getRequestHeaders());

        $this->options = [
            'headers' => $headers,
        ];
        if (isset($payload))
        {
            if ($method === Requests::GET)
            {
                $url = $url . '?' . http_build_query($payload);
            }
            else
            {
                $this->options[RequestOptions::JSON] = $payload;
            }
        }


        $this->trace->info(TraceCode::DOWNSTREAM_SERVICE_REQUEST, [
            'url'           => $url,
            'service'       => self::CROSS_BORDER_IMPORT,
            'payload'       => $payload,
            'headers'       => $this->options['headers'],
        ]);

        try
        {
            $response = $this->client->request($method, $url, $this->options);

            return $this->formatResponse($response);
        }
        catch (RequestException $e) {

            if ($e->hasResponse())
            {
                if ($e->getResponse()->getStatusCode() == '400')
                {
                    $resp = $this->formatResponse($e->getResponse());
                    $this->trace->error(TraceCode::CROSS_BORDER_IMPORT_INTEGRATION_ERROR, [
                        'error_message' => $resp,
                        'url'           => $url,
                    ]);

                    throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR, null, null,
                        $resp['meta']['description']
                    );
                }
            }

            throw $e;
        }
    }

    private function getRequestHeaders()
    {
        $headers = [
            self::CONTENT_TYPE      => self::CONTENT_TYPE_JSON,
            self::X_RAZORPAY_MODE   => $this->mode
        ];

        if(!empty(Request::header(RequestHeader::DEV_SERVE_USER))){
            $headers[RequestHeader::DEV_SERVE_USER] = Request::header(RequestHeader::DEV_SERVE_USER);
        }

        return $headers;
    }

    private function formatResponse($response)
    {
        $responseArray = json_decode($response->getBody(), true);

        $this->trace->info(TraceCode::DOWNSTREAM_SERVICE_RESPONSE, [
            'response'  => $responseArray,
            'service'   => self::CROSS_BORDER_IMPORT,
        ]);

        return $responseArray;
    }

    public function validateImportPayment($input)
    {
        $url = self::VALIDATE_PAYMENT;

        $params = [
            'amount' => $input['amount'],
            'customer_id' => $input['customer_id'],
            'currency' => $input['currency'],
            'international' => $input['international'],
            'method' => $input['method'],
            'notes' => $input['notes'],
            'order_id' => $input['order_id'],
            'merchant_id' => $input['merchant_id'],
            'payment_id' => $input['id'],
            'recurring' => $input['recurring'],
            'library' => (new PaymentService)->getLibraryFromPayment($input)
        ];

        try {
            return $this->makeRequest($url, self::POST, $params);
        } catch (\Throwable $e) {
            $this->trace->info(TraceCode::CROSS_BORDER_IMPORT_VALIDATE_PAYMENT_ERROR,[
                'error' => $e,
            ]);

            throw $e;
        }
    }

    public function pushPayoutStatusUpdate(Entity $payout, string $mode)
    {
        $dataToSend = $this->getDataFromPayout($payout);

        $this->sendStatusUpdate($dataToSend, $mode);
    }

    protected function getDataFromPayout(Entity $payout): array
    {
        $payoutID = $payout->getId();

        // before this call; if PAYOUTS_STATUS_DETAILS_ENTITY_CREATED is
        // performed; there will be an updated status details ID; otherwise
        // existing data OR null structure will be returned
        $statusDetailsID = $payout->getStatusDetailsId();

        if ($statusDetailsID !== null)
        {
            $statusDetails = $this->preparePayoutStatusDetails($statusDetailsID, $payout);
        }
        else
        {
            $statusDetails = [
                'reason'      => null,
                'description' => null,
                'source'      => null,
            ];
            $this->trace->debug(TraceCode::CROSS_BORDER_IMPORT_PAYOUT_UPDATE_WEBHOOK_DEBUG, [
                'payout_id' => $payoutID,
                'message'   => 'payout status details ID is NULL; so status details will be NULL'
            ]);
        }
        // this debug is needed to analyse the data provided to webhook via sumo logs
        $this->trace->info(TraceCode::CROSS_BORDER_IMPORT_PAYOUT_UPDATE_WEBHOOK_RESPONSE, [
                'payout_id'             => $payoutID,
                'payout_status'         => $payout->getStatus(),
                'status_detail_id'      => $statusDetailsID,
                'payout_status_details' => $statusDetails,
                'failure_reason'        => $payout->getFailureReason(),
                'remark'                => $payout->getRemarks(),
        ]);

        return [
            'id'                     => $payoutID,
            'entity'                 => $payout->getEntity(),
            'fund_account_id'        => $payout->getFundAccountId(),
            'amount'                 => $payout->getAmount(),
            'currency'               => $payout->getCurrency(),
            'notes'                  => $payout->getNotes(),
            'fees'                   => $payout->getFees(),
            'tax'                    => $payout->getTax(),
            'status'                 => $payout->getStatus(),
            'purpose'                => $payout->getPurpose(),
            'utr'                    => $payout->getUtr(),
            'mode'                   => $payout->getMode(),
            'channel'                => $payout->getChannel(),
            'remark'                 => $payout->getRemarks(),
            'reference_id'           => $payout->getReferenceId(),
            'narration'              => $payout->getNarration(),
            'batch_id'               => $payout->getBatchId(),
            'failure_reason'         => $payout->getFailureReason(),
            'created_at'             => $payout->getCreatedAt(),
            'payout_status_details'  => $statusDetails,
        ];
    }

        /**
     * @param string $statusDetailsID
     * @return array
     */
    protected function preparePayoutStatusDetails(string $statusDetailsID, Entity $payout): array
    {
        $statusDetails = (new PayoutsStatusDetails\Repository())->fetchStatusDetailsFromStatusDetailsId($statusDetailsID);

        if ($statusDetails !== null)
        {
            $this->trace->debug(TraceCode::CROSS_BORDER_IMPORT_PAYOUT_UPDATE_WEBHOOK_DEBUG, [
                'status_details_id' => $statusDetailsID,
                'message'           => 'status details found in DB'
            ]);

            $source = $this->getSourceForStatusDetails($statusDetails, $payout);

            // if status details exist
            return [
                'reason'        => $statusDetails['reason'],
                'description'   => $statusDetails['description'],
                'source'        => $source,
            ];
        }

        $this->trace->debug(TraceCode::CROSS_BORDER_IMPORT_PAYOUT_UPDATE_WEBHOOK_DEBUG, [
            'status_details_id' => $statusDetailsID,
            'message'           => 'status details not present for this ID'
        ]);

        // if status details not found
        return [
            'reason'        => null,
            'description'   => null,
            'source'        => null,
        ];
    }

    /*
    * @param PayoutsStatusDetails\Entity $statusDetails
    * @return mixed|string|null
    * Mapping of source based on reason; either pre-defined from map otherwise from json config file
    */
   protected function getSourceForStatusDetails(PayoutsStatusDetails\Entity $statusDetails, Entity $payout)
   {
       $source = PayoutsStatusDetails\ReasonSourceMap::$statusDetailsReasonToSourceMap[$statusDetails['reason']] ?? null;

       // if source mapping is not present; then use the json file to get source
       if($source === null)
       {
           $error        = new PayoutError($payout);
           $errorDetails = $error->getErrorDetails();
           $source       = $errorDetails['source'] ?? null;
           $this->trace->debug(TraceCode::CROSS_BORDER_IMPORT_PAYOUT_UPDATE_WEBHOOK_DEBUG, [
               'message'   => 'payout status details source will be read from JSON file'
           ]);
       }

       $this->trace->debug(TraceCode::CROSS_BORDER_IMPORT_PAYOUT_UPDATE_WEBHOOK_DEBUG, [
           'message'   => 'payout status details source will be read from reason v/s source map'
       ]);

       return $source;
   }

        /**
     * Status update upon receiving webhook from payout
     * @param array $input
     * @param string $mode
     *
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function sendStatusUpdate(array $input) : array
    {
        return $this->makeRequest(self::STATUS_UPDATE_PAYOUT, Requests::POST, $input, []);
    }

}


