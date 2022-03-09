<?php

namespace RZP\Models\Payment\Refund;

use Config;
use ApiResponse;
use Carbon\Carbon;

use RZP\Exception;
use RZP\Constants;
use RZP\Models\Base;
use RZP\Error\Error;
use RZP\Models\Batch;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Models\Feature;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Bank\IFSC;
use RZP\Http\RequestHeader;
use RZP\Constants\Timezone;
use RZP\Models\BankTransfer;
use RZP\Base\RuntimeManager;
use RZP\Models\Card\IIN\IIN;
use RZP\Models\Bank\BankCodes;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Refund;
use RZP\Models\Admin\ConfigKey;
use RZP\Jobs\ScroogeRefundUpdate;
use Razorpay\Trace\Logger as Trace;
use RZP\Error\PublicErrorDescription;
use RZP\Jobs\BulkScroogeVerifyRefund;
use RZP\Exception\BadRequestException;
use RZP\Jobs\BulkRefund as BulkRefundJob;
use RZP\Models\FundTransfer\Attempt as FTA;
use RZP\Models\Payment\Processor\Netbanking;
use RZP\Models\Admin\Service as AdminService;
use RZP\Models\Payment\Service as PaymentService;
use RZP\Models\FundTransfer\Mode as TransferMode;
use RZP\Models\Reversal\Entity as ReversalEntity;
use RZP\Models\Payment\Refund\Core as RefundCore;
use RZP\Models\Payment\Refund\Speed as RefundSpeed;
use RZP\Models\Payment\Refund\Entity as RefundEntity;
use RZP\Models\Payment\Refund\Helpers as RefundHelpers;
use RZP\Models\Merchant\Email\Type as MerchantEmailType;
use RZP\Models\Merchant\Email\Core as MerchantEmailCore;
use RZP\Models\Payment\Refund\Constants as RefundConstants;
use RZP\Models\Payment\Processor\Processor as PaymentProcessor;

class Service extends Base\Service
{
    protected $mutex;
    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Refund\Core;

        $this->mutex = $this->app['api.mutex'];
    }

    public function createBatchRefund(array  $input)
    {
       $tracePayload = [];

        try
        {
            $tracePayload =[
                Entity::PAYMENT_ID => $input[Entity::PAYMENT_ID],
                Entity::AMOUNT     => $input[Entity::AMOUNT],
            ];

            $this->trace->debug(TraceCode::BATCH_PROCESSING_ENTRY, $tracePayload);

            $batchId = $this->app['request']->header(RequestHeader::X_Batch_Id) ?? null;

            $merchantId = $this->app['request']->header(RequestHeader::X_ENTITY_ID) ?? null;

            $paymentId = trim($input[Refund\Constants::PAYMENT_ID]);

            $this->merchant = $this->repo->merchant->findOrFail($merchantId);

            /** @var Payment\Entity $payment */
            $payment = $this->repo->payment->findByPublicIdAndMerchant(
                $paymentId,
                $this->merchant);

            if (($this->merchant->isFeatureEnabled(\RZP\Models\Feature\Constants::DISABLE_CARD_REFUNDS) === true) and
                ($payment->getMethod() === Method::CARD))
            {
                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_CARD_REFUND_NOT_ALLOWED,
                    Payment\Entity::METHOD,
                    [
                        Payment\Entity::MERCHANT_ID => $this->merchant->getId(),
                        Refund\Entity::PAYMENT_ID    => $paymentId,
                    ]);
            }

            unset($input[Batch\Constants::TYPE]);
            unset($input[RefundConstants::PAYMENT_ID]);

            $paymentProcessor = (new PaymentProcessor($this->merchant));

            $refund = $paymentProcessor->refundPaymentViaBatchEntry($payment, $input, null, $batchId);

            $input[RefundConstants::PAYMENT_ID]        = $paymentId;
            $input[RefundConstants::REFUND_ID ]        = $refund->getPublicId();
            $input[RefundConstants::REFUNDED_AMOUNT]   = $refund->getAmount();
            $input[RefundConstants::STATUS]            = Batch\Status::SUCCESS;
            $input[RefundConstants::ERROR_CODE]        = null;
            $input[RefundConstants::ERROR_DESCRIPTION] = null;
            $input[Entity::SPEED_REQUESTED]            = $refund->getSpeedRequested();

        }
        catch (\Exception $e)
        {
            // RZP Exceptions have public error code & description which can be exposed in the output file
            $this->trace->traceException($e, null, TraceCode::BATCH_PROCESSING_ERROR, $tracePayload);

            $error = $e->getError();

            $input[RefundConstants::PAYMENT_ID]        = $paymentId;
            $input[RefundConstants::STATUS]            = RefundConstants::FAILURE;
            $input[RefundConstants::REFUND_ID]         = $input[RefundConstants::REFUND_ID] ?? null;
            $input[RefundConstants::REFUNDED_AMOUNT]   = $input[RefundConstants::REFUNDED_AMOUNT] ?? null;
            $input[RefundConstants::ERROR_CODE]        = $error->getPublicErrorCode();
            $input[RefundConstants::ERROR_DESCRIPTION] = $error->getDescription();
            $input[Entity::SPEED_REQUESTED]            = $input[Entity::SPEED] ?? null;
        }
        finally
        {
            return $input;
        }
    }

    public function create(array $input)
    {
        $inputForValidation = [Entity::PAYMENT_ID => $input[Entity::PAYMENT_ID] ?? null];

        (new Validator)->validateInput('direct', $inputForValidation);

        $paymentId = $input[Entity::PAYMENT_ID];

        unset($input[Entity::PAYMENT_ID]);

        return (new Payment\Service)->refund($paymentId, $input);
    }

    /**
     * Call create new refund v2 route on scrooge. Refund creation completly occurs on scrooge
     *
     * @param string $paymentId: public payment id
     * @param array $input: input params based on public RZP refund API doc
     *
     * @return array: returns successful response based on public RZP refund API doc
     **/
    public function scroogeRefundCreate(string $paymentId, array $input)
    {
        $input[RefundConstants::PAYMENT_ID] = $paymentId;

        // call to scrooge
        $response = $this->app['scrooge']->createNewRefundV2($input);

        // response handling
        if (in_array($response['code'], [200, 201, "200", "201"]) == false)
        {
            $publicErrorCode = $response['body']['public_error']['code'] ?? ErrorCode::SERVER_ERROR;

            $publicErrorMessage = $response['body']['public_error']['message'] ?? PublicErrorDescription::SERVER_ERROR;

            $internalErrorCode = $response['body']['internal_error']['code'] ?? ErrorCode::SERVER_ERROR;

            // If errorcode is undefined, will fallback to server_error
            if (defined(ErrorCode::class . '::' . $publicErrorCode) === false)
            {
                $publicErrorCode = ErrorCode::SERVER_ERROR;

                $publicErrorMessage = PublicErrorDescription::SERVER_ERROR;
            }

            if (defined(ErrorCode::class . '::' . $internalErrorCode) === false)
            {
                $internalErrorCode = ErrorCode::SERVER_ERROR;
            }

            $exceptionType = str_replace(' ', '', ucwords(strtolower(str_replace('_', ' ', $publicErrorCode))));

            if ($publicErrorCode != ErrorCode::SERVER_ERROR)
            {
                $exceptionType = str_replace('Error', '', $exceptionType);
            }

            switch ($publicErrorCode)
            {
                case ErrorCode::BAD_REQUEST_ERROR:
                    $args = [constant(ErrorCode::class . '::' . $internalErrorCode)];
                    break;

                case ErrorCode::SERVER_ERROR:
                    $args = [$publicErrorMessage, constant(ErrorCode::class . '::' . $internalErrorCode)];
                    break;

                default:
                    $args = [$publicErrorMessage];
                    break;
            }

            $class = 'RZP\Exception' . '\\' . $exceptionType . 'Exception';

            throw new $class(...$args);
        }

        // body has the actual scrooge response
        return $response['body'];
    }

    public function getRefundsFile(array $input = [])
    {
        list($from, $to) = $this->getTimestamps($input);

        $returnValue = [];

        $gatewayCode = null;

        $method = $input[Payment\Entity::METHOD];

        $email = $input['email'] ?? null;

        switch ($method)
        {
            case Payment\Method::NETBANKING:
                $gateways = Payment\Gateway::$refundFileNetbankingGateways;

                $type = Payment\Entity::BANK;

                if (isset($input['bank']))
                {
                    $gatewayCode = $input['bank'];

                    $gateway = $gateways[$gatewayCode];
                }

                // Removing kotak, axis and federal from gateways list
                // These gateways go through a reconciliation process
                // Please refer POST /reconciliate
                unset($gateways[IFSC::KKBK]);
                unset($gateways[IFSC::CORP]);
                unset($gateways[IFSC::RATN]);

                // These banks refund files have been moved to gateway_file, so
                // unsetting it here
                unset($gateways[IFSC::HDFC]);
                unset($gateways[IFSC::ICIC]);
                unset($gateways[IFSC::FDRL]);
                unset($gateways[IFSC::INDB]);
                unset($gateways[IFSC::IDFB]);
                unset($gateways[IFSC::UTIB]);
                unset($gateways[IFSC::ESFB]);
                unset($gateways[IFSC::CSBK]);
                unset($gateways[IFSC::VIJB]);
                unset($gateways[IFSC::CNRB]);
                unset($gateways[IFSC::SBIN]);
                unset($gateways[Netbanking::PUNB_R]);
                unset($gateways[Netbanking::BARB_R]);
                unset($gateways[IFSC::ALLA]);

                break;

            case Payment\Method::WALLET:
                $gateways = Payment\Gateway::$walletToGatewayMap;

                $type = Payment\Entity::WALLET;

                if (isset($input['wallet']))
                {
                    $gatewayCode = $input['wallet'];

                    $gateway = $gateways[$gatewayCode];
                }
                break;

            case Payment\Method::UPI:
                $gateways = Payment\Gateway::$upiToGatewayMap;

                $type = Payment\Entity::METHOD;
                $gatewayCode = Payment\Method::UPI;

                if (isset($input['bank']))
                {
                    $bank = $input['bank'];

                    $gateway = $gateways[$bank];
                }
                break;

            default:
                throw new Exception\LogicException(
                    'Invalid method provided for generating refunds file.',
                    null,
                    [
                        'input'     => $input,
                        'method'    => $method,
                    ]);
        }

        if ($gatewayCode === null)
        {
            foreach ($gateways as $gatewayCode => $gateway)
            {
                $returnValue[$gateway] = $this->generateRefundFileForGateway(
                    $type,
                    $gatewayCode,
                    $from,
                    $to,
                    $gateway,
                    $email
                );
            }
        }
        else
        {
            $returnValue[$gateway] = $this->generateRefundFileForGateway(
                $type,
                $gatewayCode,
                $from,
                $to,
                $gateway,
                $email
            );
        }

        $this->trace->info(
            TraceCode::REFUND_FILE_GENERATE_REQUEST,
            [
                'input'       => $input,
                'from'        => $from,
                'to'          => $to,
                'gateways'    => $gateways,
                'returnValue' => $returnValue,
            ]
        );

        return $returnValue;
    }

    protected function generateRefundFileForGateway($type, $gatewayCode, $from, $to, $gateway, $email = null)
    {
        // Handling claims file netbanking banks using daily files.
        if ((in_array($gatewayCode, Payment\Gateway::$claimsFileToBank)) and
            ($type === Payment\Entity::BANK))
        {
            $class = $this->getDailyFilesNamespace($gatewayCode);

            $result = (new $class($gatewayCode))->generate($from, $to, $email);

            return $result;
        }
        else
        {
            // TODO : Implement send email feature for other netbanking gateways.
            // Implemented for Daily file gateways.
            $refunds = $this->repo->refund->fetchRefundsForGatewayBetweenTimestamps(
                                                $type, $gatewayCode, $from, $to, $gateway);

            return $this->generateRefundFile($refunds, $email);
        }
    }

    protected function getDailyFilesNamespace($gatewayCode)
    {
        $entity = Payment\Gateway::$netbankingToGatewayMap[$gatewayCode];

        return Constants\Entity::$namespace[$entity] . '\\DailyFiles';
    }

    protected function generateRefundFile($refunds, $email = null)
    {
        $count = $refunds->count();

        if ($count === 0)
        {
            return ['count' => $count];
        }

        $data = [];

        foreach ($refunds as $refund)
        {
            $payment = $refund->payment;
            $terminal = $payment->terminal;

            $col['refund'] = $refund->toArray();
            $col['payment'] = $refund->payment->toArray();
            $col['terminal'] = $refund->payment->terminal->toArray();

            $data[] = $col;
        }

        $input['data'] = $data;
        $input['email'] = $email;

        $gateway = $terminal->getGateway();

        $file = $this->app['gateway']->call($gateway, Payment\Action::GENERATE_REFUNDS, $input, $this->mode);

        $this->core->reconcileNetbankingRefunds($data);

        return ['file' => $file, 'count' => $count];
    }

    protected function getTimestamps($input)
    {
        $from = Carbon::yesterday(Timezone::IST)->getTimestamp();
        $to = Carbon::today(Timezone::IST)->getTimestamp() - 1;

        $frequency = 'daily';

        if (isset($input['frequency']))
        {
            $frequency = $input['frequency'];
        }

        if ($frequency === 'monthly')
        {
            if (isset($input['on']))
            {
                $dt = Carbon::createFromFormat('Y-m-d', $input['on'], Timezone::IST);

                $from = $dt->startOfMonth()->getTimestamp();

                $to   = $dt->endOfMonth()->addDay()->getTimestamp() - 1;
            }
            else
            {
                $dt = Carbon::yesterday(Timezone::IST);

                $from = $dt->startOfMonth()->getTimestamp();
                $to   = $dt->endOfMonth()->addDay()->getTimestamp() - 1;
            }
        }
        else
        {
            if (isset($input['on']))
            {
                $from = Carbon::createFromFormat('Y-m-d', $input['on'], Timezone::IST)->setTime(0,0,0);

                $fromTimeStamp = $from->getTimestamp();

                $to = $from->addDay()->getTimestamp() - 1;

                $from = $fromTimeStamp;
            }
        }

        if (isset($input['from']))
        {
            $from = $input['from'];
        }

        if (isset($input['to']))
        {
            $to = $input['to'];
        }

        return array($from, $to);
    }

    public function fetch($id, array $input = [])
    {
        $scroogeRefundArray = [];
        $experiment = false;

        // Route only private auth and not proxy auth requests to scrooge
        if ($this->app['basicauth']->isStrictPrivateAuth() === true)
        {
            // Expands is not supported in strict private auth, but requests could still come at the moment
            // Not moving them to scrooge right away. Need to handle validation part on scrooge for such additional params
            if (empty($input) === true)
            {
                $variant = $this->app->razorx->getTreatment($id,
                    RefundConstants::RAZORX_KEY_REFUND_FETCH_BY_ID_FROM_SCROOGE,
                    $this->mode
                );

                if ($variant === RefundConstants::RAZORX_VARIANT_ON)
                {
                    $experiment = true;

                    $scroogeResponse = $this->app['scrooge']->refundsFetchById($id, $input);

                    $scroogeRefundArray = $scroogeResponse['body'];
                }
            }
        }

        $refundArray = $this->repo->refund->fetchAndReturnPublicArrayWithExpand($id, $this->merchant, $input);

        // Adding `processed_at`, `failed_at`, `speed_change_time`, `gateway_refund_support` params only for dashboard
        if ($this->app['basicauth']->isProxyAuth() === true)
        {
            $this->addParamsForDashboard($refundArray);
        }

        if ($experiment === true)
        {
            $this->compareAndLogRefundResponses($refundArray, $scroogeRefundArray);
        }

        return $refundArray;
    }

    public function compareAndLogRefundResponses($refundArray, $scroogeRefundArray)
    {
        // Compare scrooge and api response
        $responseDiff = [];

        foreach ($refundArray as $key => $value)
        {
            if ($key === RefundEntity::ACQUIRER_DATA)
            {
                // casting this to array as acquirer_data is a spine dictionary object, compare would fail
                $value = $value->toArray();
            }

            if ($scroogeRefundArray[$key] !== $value)
            {
                $responseDiff[$key] = $value;
            }
        }

        if (empty($responseDiff) === false)
        {
            $this->trace->info(TraceCode::SCROOGE_REFUND_FETCH_BY_ID_INCONSISTENCY, [
                'diff_keys'        => array_keys($responseDiff),
                'api_response'     => $refundArray,
                'scrooge_response' => $scroogeRefundArray,
            ]);
        }
    }

    public function fetchEntity($id)
    {
        $refund = $this->repo->refund->findOrFailPublic($id);

        $response = $refund->toArray();

        return $response;
    }

    public function fetchEntityOrNull($id)
    {
        Entity::stripSignWithoutValidation($refundId);

        $refund = $this->repo->refund->find($refundId);

        if (empty($refund) === true)
        {
            return null;
        }

        return $refund;
    }

    /*
     * Sample request:
     * {
     *   "refund":["amount"],
     *   "payment":["reference2", "is_dcc"],
     *   "entities":{
     *       "card":["iin", "last4"],
     *       "terminal":["gateway_terminal_id", "gateway_merchant_id"],
     *       "upi_metadata":["type"]
     *       "gateway_entity":{
     *           "axis_migs":{
     *               "authorize":["vpc_ReceiptNo"]
     *           },
     *           "mozart":{
     *               "capture":["order_id", "data.txn.id"]
     *           }
     *       },
     *   "extra_data":[
     *       "ifsc_code",
     *       "is_fta_only_refund",
     *       "fta_data"
     *   ]
     *   "refund_ids":["C6rXXXXXXXX43","C6rQQL1KTvb43"]
     * }
     *
     * Sample response:
     * {
     *    "C6rXXXXXXXX43": {
     *        "entities": {
     *            "refund": {
     *                "amount": 100
     *            },
     *            "payment": {
     *                "reference2": "54543",
     *                "is_dcc": false
     *            },
     *            "card": {
     *                "iin": "401200",
     *                "last4": "3335"
     *            },
     *            "terminal": {
     *                "gateway_terminal_id": "test_terminal",
     *                "gateway_merchant_id": "test_merchant"
     *            },
     *            "upi_metadata": {
     *                "type": "otm"
     *            },
     *            "gateway_entity": {
     *                "axis_migs": {
     *                    "authorize": {
     *                        "vpc_ReceiptNo": "492348230fd"
     *                    }
     *                },
     *                "mozart":{
     *                    "capture":{
     *                        "order_id": "21311",
     *                        "data.txn.id": "33190-11121"
     *                    }
     *                }
     *            }
     *        }
     *       "extra_data": {
     *           "ifsc_code": "HDFC0000001",
     *           "is_fta_only_refund": true,
     *           "fta_data" : {
     *               "error" : nil,
     *               "fta_data" : {
     *                    "bank_account": {
     *                        "account_number": "12231223122312",
     *                        "beneficiary_name": "ABCD",
     *                        "ifsc_code": "HDFC0000001"
     *                    }
     *               }
     *           }
     *       }
     *    }
     * }
     */
    public function scroogeFetchEntities($input)
    {
        $responseArray = [];

        $skippedRefunds = [];

        if (isset($input[RefundConstants::REFUND_IDS]) === true)
        {
            foreach ($input[RefundConstants::REFUND_IDS] as $id)
            {
                try
                {
                    $refund = $this->repo->refund->findOrFailPublic($id);

                    $refundEntity = $refund->toArrayGateway();

                    $payment = $refund->payment;

                    $paymentEntity = $payment->toArrayGateway();

                    $response = [];

                    if (isset($input[Constants\Entity::REFUND]) === true)
                    {
                        $map = [];

                        foreach ($input[Constants\Entity::REFUND] as $value)
                        {
                            $map[$value] = $refundEntity[$value];
                        }

                        $response[RefundConstants::ENTITIES][Constants\Entity::REFUND] = $map;
                    }

                    if (isset($input[Constants\Entity::PAYMENT]) === true)
                    {
                        $map = [];

                        foreach ($input[Constants\Entity::PAYMENT] as $value)
                        {
                            if ($value === RefundConstants::IS_DCC)
                            {
                                $map[$value] = $payment->isDCC();

                                continue;
                            }

                            $map[$value] = $paymentEntity[$value];
                        }

                        $response[RefundConstants::ENTITIES][Constants\Entity::PAYMENT] = $map;
                    }

                    if (isset($input[RefundConstants::ENTITIES]) === true)
                    {
                        foreach ($input[RefundConstants::ENTITIES] as $key => $values)
                        {
                            /*
                             * Nested fetch is supported in Mozart entity since we search in json data
                             * This is the structure of gateway_entity
                             * "gateway_entity":{
                             *      "axis_migs":{
                             *          "authorize":["vpc_ReceiptNo"]
                             *       },
                             *       "mozart":{
                             *          "capture":["order_id", "data.txn.id"]
                             *       }
                             *  },
                             */
                            if ($key === RefundConstants::GATEWAY_ENTITY)
                            {
                                foreach ($values as $gatewayEntity => $action)
                                {
                                    foreach ($action as $gatewayAction => $columns)
                                    {
                                        if (method_exists($this->repo->$gatewayEntity, 'findByPaymentIdAndActionorFail') === true)
                                        {
                                            $entity = [];

                                            try
                                            {
                                                $entity = $this->repo
                                                               ->$gatewayEntity
                                                               ->findByPaymentIdAndActionorFail($paymentEntity['id'], $gatewayAction)
                                                               ->toArray();
                                            }
                                            catch (\Exception $ex)
                                            {
                                                // Sometimes we try to fetch some entries generically and that may not applicable for a particular refund
                                                // In such cases we do not want this exception to fail returning other necessary data
                                                // Hence catching and silently ignoring. Logging is also redundant here as this noice is expected
                                            }

                                            $map = [];

                                            if (($gatewayEntity === RefundConstants::MOZART) and
                                                (isset($entity['raw']) === true))
                                            {
                                                $entity = json_decode($entity['raw'], true);
                                            }

                                            foreach ($columns as $column)
                                            {
                                                // Some values have to be fetched from keys in deeper levels
                                                // Such keys are accepted as `.` appended string
                                                // We then split input string and recursively pick value of key
                                                // If at any level key is not found we return '' empty string
                                                $value = $entity;

                                                $picker = explode('.', $column);

                                                foreach ($picker as $pick)
                                                {
                                                    if (isset($value[$pick]) === true)
                                                    {
                                                        $value = $value[$pick];
                                                    }
                                                    else
                                                    {
                                                        $value = '';

                                                        break;
                                                    }
                                                }

                                                $map[$column] = $value;
                                            }

                                            $response[RefundConstants::ENTITIES][$key][$gatewayEntity][$gatewayAction] = $map;
                                        }
                                    }
                                }
                            }
                            else if ($key === Constants\Entity::UPI_METADATA)
                            {
                                $map = [];

                                $upiMetadataEntity = $this->repo->upi_metadata->fetchByPaymentId($payment->getId());

                                foreach ($values as $value)
                                {
                                    $map[$value] = $upiMetadataEntity[$value];
                                }

                                $response[RefundConstants::ENTITIES][Constants\Entity::UPI_METADATA] = $map;
                            }
                            else
                            {
                                $entity = $payment->$key;

                                if ($key === Constants\Entity::IIN)
                                {
                                    $entity = $payment->card->iinRelation;
                                }

                                $map = [];

                                foreach ($values as $value)
                                {
                                    $map[$value] = $entity[$value];

                                    $getter = 'get' . studly_case($value);

                                    if ((empty($map[$value]) === true) and
                                        (method_exists($entity, $getter) === true))
                                    {
                                        $map[$value] = $entity->{$getter}();
                                    }
                                }

                                $response[RefundConstants::ENTITIES][$key] = $map;
                            }
                        }
                    }

                    if (isset($input[RefundConstants::EXTRA_DATA]) === true)
                    {
                        $res = [];

                        foreach ($input[RefundConstants::EXTRA_DATA] as $paramKey)
                        {
                            $func = 'getExtraData' . studly_case($paramKey);

                            $res[$paramKey] = (method_exists($this, $func)) ? $this->$func($refund) : null;
                        }

                        $response[RefundConstants::EXTRA_DATA] = $res;
                    }

                    $responseArray[$id] = $response;
                }
                catch (\Exception $ex)
                {
                    array_push($skippedRefunds, [
                        $id =>
                            [
                                'code'    => $ex->getCode(),
                                'message' => $ex->getMessage()
                            ]
                    ]);
                }
            }
        }

        $traceData = [
            'skipped_refunds'   => $skippedRefunds,
            'success_count'     => count($responseArray),
            'failure_count'     => count($skippedRefunds),
            'request_count'     => count($input[RefundConstants::REFUND_IDS] ?? []),
        ];

        $this->trace->info(TraceCode::SCROOGE_FETCH_ENTITIES, $traceData);

        return $responseArray;
    }

/*
  Sample request body:
 {
	"entities": [
		"iin",
		"payment",
		"upi_metadata"
	],
	"extra_data": [
		"is_fta_only_refund",
		"merchant_features"
        "card_has_supported_issuer",
        "payer_bank_account",
        "count_of_open_non_fraud_disputes",
        "ifsc_code",
        "is_iin_prepaid"
	],
	"payment_ids": ["HSmPekI1ye7RL5"]
 }

 Sample response:
 {
	"HSmPekI1ye7RL5": {
		"entities": {
			"payment": {
				"data": {
					"id": "HSmPekI1ye7RL5",
					"merchant_id": "10000000000000",
					 .
					 .
					 .
				},
				"error": null
			},
			"iin": {
				"data": {
					"iin": "401200",
					 .
					 .
					 .
				},
				"error": null
			},
			"upi_metadata": {
				"data": null,
				"error": "NO_DATA_FOUND"
			}
		},
		"extra_data": {
			"is_fta_only_refund": {
				"data": true,
				"error": null
			},
			"merchant_features": {
				"data": ["charge_at_will", "subscriptions", "payout"],
				"error": null
			},
			"card_has_supported_issuer": {
				"data": true,
				"error": null
			},
			"payer_bank_account": {
				"data": {
                        .
                        .
                        },
				"error": null
			},
			"count_of_open_non_fraud_disputes": {
				"data": 1,
				"error": null
			},
			"ifsc_code": {
			    "data": 'HDFC0000011',
				"error": null
			},
			"is_iin_prepaid": {
				"data": true,
				"error": null
			},
		}
	}
 }
*/
    public function scroogeFetchEntitiesV2($input)
    {
        (new Validator)->validateInput('fetch_entities_v2', $input);

        $responseArray = [];

        $skippedPayments = [];

        $this->trace->info(TraceCode::SCROOGE_FETCH_ENTITIES_V2_REQUEST,
            [
                RefundConstants::PAYMENT_IDS => $input[RefundConstants::PAYMENT_IDS]
            ]);

        foreach ($input[RefundConstants::PAYMENT_IDS] as $id)
        {
            $paymentError = NULL;

            try
            {
                $payment = $this->repo->payment->findByPublicId(Payment\Entity::getSignedId($id));

                if (empty($payment) === true)
                {
                    $paymentError = RefundConstants::PAYMENT_NOT_FOUND;
                }

                $response = [];

                if ((isset($input[RefundConstants::ENTITIES]) === true) &&
                    (in_array(Constants\Entity::PAYMENT, $input[RefundConstants::ENTITIES]) === true))
                {
                    $data = NULL;

                    if (empty($paymentError) === true)
                    {
                        $data = $payment->toArrayGateway();

                        $data[RefundConstants::AMOUNT_UNREFUNDED]=$payment->getAmountUnrefunded();

                        $data[RefundConstants::BASE_AMOUNT_UNREFUNDED]=$payment->getBaseAmountUnrefunded();

                        $data[RefundConstants::CURRENCY_CONVERSION_RATE] = $payment->getCurrencyConversionRate();

                        $data[RefundConstants::IS_UPI_OTM] = $payment->isUpiOtm();

                        $data[RefundConstants::IS_DCC] = $payment->isDCC();
                    }

                    $response[RefundConstants::ENTITIES][Constants\Entity::PAYMENT][RefundConstants::DATA] = $data;

                    $response[RefundConstants::ENTITIES][Constants\Entity::PAYMENT][RefundConstants::ERROR] = $paymentError;

                    $key = array_search(Constants\Entity::PAYMENT, $input[RefundConstants::ENTITIES]);

                    unset($input[RefundConstants::ENTITIES][$key]);
                }

                if (isset($input[RefundConstants::ENTITIES]) === true)
                {
                    foreach ($input[RefundConstants::ENTITIES] as $key)
                    {
                        $data = null;

                        $error = $paymentError;

                        if (empty($paymentError) === true)
                        {
                            if ($key === Constants\Entity::UPI_METADATA)
                            {
                                try
                                {
                                    $upiMetadataEntity = $this->repo->upi_metadata->fetchByPaymentId($payment->getId());

                                    if (empty($upiMetadataEntity) === false)
                                    {
                                        $data = $upiMetadataEntity->toArray();
                                    }
                                }
                                catch (\Exception $ex)
                                {
                                    $error = RefundConstants::FETCH_ENTITIES_ERROR;
                                }
                            }

                            else if (strpos($key, RefundConstants::GATEWAY_ENTITY) === 0)
                            {
                                $gatewayEntitySplitString = explode (".", $key);

                                if (isset($gatewayEntitySplitString[1]) === true)
                                {
                                    $entity = $gatewayEntitySplitString[1];

                                    $action = (isset($gatewayEntitySplitString[2]) === true) ? $gatewayEntitySplitString[2] : null;

                                    if (method_exists($this->repo->$entity, 'findByPaymentIdAndActionorFail') === true) {
                                        try {
                                            $data = $this->repo
                                                ->$entity
                                                ->findByPaymentIdAndActionorFail($id, $action)
                                                ->toArray();

                                            if (($entity === RefundConstants::MOZART) and
                                                (isset($data['raw']) === true)) {
                                                $data = json_decode($data['raw'], true);
                                            }
                                        }
                                        catch (\Exception $ex)
                                        {
                                            // Sometimes we try to fetch some entries generically and that may not applicable for a particular refund
                                            // In such cases we do not want this exception to fail returning other necessary data
                                            // Hence catching and silently ignoring. Logging is also redundant here as this noice is expected
                                            $error = RefundConstants::FETCH_ENTITIES_ERROR;
                                        }
                                    }
                                }
                            }
                            else if ($key !== Constants\Entity::PAYMENT)
                            {
                                if ($key === Constants\Entity::IIN)
                                {
                                    $entity = (empty($payment->card) === false) ? $payment->card->iinRelation : null;
                                }
                                else
                                {
                                    $entity = $payment->$key;
                                }

                                if (empty($entity) === false)
                                {
                                    $data = $entity->toArray();
                                }
                            }
                        }

                        $response[RefundConstants::ENTITIES][$key][RefundConstants::DATA] = $data;
                        $response[RefundConstants::ENTITIES][$key][RefundConstants::ERROR] = $error;
                    }
                }

                if (isset($input[RefundConstants::EXTRA_DATA]) === true)
                {
                    $res = [];

                    foreach ($input[RefundConstants::EXTRA_DATA] as $paramKey)
                    {
                        $data = null;
                        $error = null;

                        if (empty($paymentError) === true)
                        {
                            $func = 'getPaymentExtraData' . studly_case($paramKey);

                            try
                            {
                                if (method_exists($this, $func))
                                {
                                    $data = $this->$func($payment);
                                }
                                else
                                {
                                 $error = RefundConstants::FETCH_ENTITIES_ERROR;
                                }
                            }
                            catch(\Exception $ex)
                            {
                                $error = RefundConstants::FETCH_ENTITIES_ERROR;
                            }

                        }
                        else
                        {
                            $data = null;
                            $error = $paymentError;
                        }

                        $res[$paramKey][RefundConstants::DATA] = $data;
                        $res[$paramKey][RefundConstants::ERROR] = $error;
                    }

                    $response[RefundConstants::EXTRA_DATA] = $res;
                }

                $responseArray[$id] = $response;
                $responseArray= $this->convertNumericFieldsToString($responseArray);

            }
            catch (\Exception $ex)
            {
                array_push($skippedPayments, [
                    $id =>
                        [
                            RefundConstants::CODE    => $ex->getCode(),
                            RefundConstants::MESSAGE => $ex->getMessage()
                        ]
                ]);
            }
        }

        $traceData = [
            RefundConstants::SKIPPED_PAYMENT_IDS   => $skippedPayments,
            RefundConstants::SUCCESS_COUNT         => count($responseArray),
            RefundConstants::FAILURE_COUNT         => count($skippedPayments),
            RefundConstants::REQUEST_COUNT         => count($input[RefundConstants::PAYMENT_IDS] ?? []),
        ];

        $this->trace->info(TraceCode::SCROOGE_FETCH_ENTITIES_V2_SUMMARY, $traceData);

        return $responseArray;
    }

    protected function convertNumericFieldsToString($array)
    {
        if (is_array($array) === true)
        {
            foreach ($array as $key => $val)
            {
                if (is_array($array[$key]) === true || is_object($array[$key]) === true)
                {
                    $array[$key] = $this->convertNumericFieldsToString($val);
                }
                else
                {
                    if (is_numeric($array[$key]) === true)
                    {
                        $array[$key] = strval($val);
                    }
                }
            }
        }

        return $array;
    }

    protected function getExtraDataIfscCode(Entity $refund)
    {
        $bank = (empty($refund->payment->getBank()) === false) ? $refund->payment->getBank() : '';

        return BankCodes::getIfscForBankCode($bank);
    }

    protected function getExtraDataIsFtaOnlyRefund(Entity $refund) : bool
    {
        return $this->getNewProcessor($refund->merchant)->refundViaFtaOnly($refund->payment);
    }

    protected function getExtraDataFtaData(Entity $refund) : array
    {
        $ftaData = [
            RefundConstants::ERROR    => null,
            RefundConstants::FTA_DATA => null,
        ];

        try {
            $this->getNewProcessor($refund->merchant)->loadFTADataForScroogeRefund($ftaData, $refund, $refund->payment);
        }
        catch (\Exception $ex)
        {
            $ftaData[RefundConstants::ERROR] = $ex->getCode();
        }

        return $ftaData;
    }

    protected function getPaymentExtraDataIfscCode(Payment\Entity $payment)
    {
        $bank = (empty($payment->getBank()) === false) ? $payment->getBank() : '';

        return BankCodes::getIfscForBankCode($bank);
    }

    protected function getPaymentExtraDataIsFtaOnlyRefund(Payment\Entity $payment) : bool
    {
        return $this->getNewProcessor($payment->merchant)->refundViaFtaOnly($payment);
    }

    protected function getPaymentExtraDataMerchantFeatures(Payment\Entity $payment)
    {
        return $payment->merchant->getEnabledFeatures();
    }

    protected function getPaymentExtraDataPayerBankAccount(Payment\Entity $payment)
    {
        if ($payment->isBankTransfer() === true)
        {
            $paymentId = $payment->getId();

            $bankTransfer = $this->repo->bank_transfer->findByPaymentId($paymentId);

            $payerAccount = $bankTransfer->payerBankAccount;

            if (empty($payerAccount) === true)
            {
                return null;
            }

            return $payerAccount->toArray();
        }

        return null;
    }

    protected function getPaymentExtraDataCountOfOpenNonFraudDisputes(Payment\Entity $payment)
    {
        $openNonFraudDisputes = $this->repo->dispute->getOpenNonFraudDisputes($payment);

        return count($openNonFraudDisputes);
    }

    protected function getPaymentExtraDataIsIinPrepaid(Payment\Entity $payment)
    {
        if ((empty($payment->card) === true) or (empty($payment->card->iinRelation) === true))
        {
            return null;
        }

        return IIN::isIinPrepaid($payment->card->iinRelation->getIin());
    }

    protected function getPaymentExtraDataCardHasSupportedIssuer(Payment\Entity $payment)
    {
        if ((empty($payment->card) === true) or (empty($payment->card->iinRelation) === true))
        {
            return null;
        }

        $cardIssuer = $payment->card->iinRelation->getIssuer();

        return in_array($cardIssuer, TransferMode::getSupportedIssuers());
    }

    protected function getPaymentExtraDataFtaData(Payment\Entity $payment) : array
    {
        $ftaData=[];

        if ($payment->isDirectSettlementRefund() !== true)
        {
                $this->getNewProcessor($payment->merchant)->loadFTADataWithoutRefundEntity($ftaData, $payment);
        }

        return $ftaData;
    }

    protected function getPaymentExtraDataPaymentUtr(Payment\Entity $payment)
    {
        if ($payment->isBankTransfer() === true)
        {
            $paymentId = $payment->getId();

            $bankTransfer = $this->repo->bank_transfer->findByPaymentId($paymentId);

            if (empty($bankTransfer) === false)
            {
                return $bankTransfer->getUtr();
            }

        }

        return null;
    }

    public function fetchMultiple($input)
    {
        // We are masking status for merchants
        if ((($this->app['basicauth']->isProxyAuth() === true) or
             ($this->app['basicauth']->isPrivateAuth() === true)) and
            (isset($input[Entity::STATUS]) === true))
        {
            $input[Entity::PUBLIC_STATUS] = $input[Entity::STATUS];

            unset($input[Entity::STATUS]);
        }

        $refunds = $this->repo->refund->fetch($input, $this->merchant->getId());

        $refundsArray = $refunds->toArrayPublic();

        // Showing public_status for all dashboard merchants
        if ($this->app['basicauth']->isProxyAuth() === true)
        {
            $this->addPublicStatus($refundsArray, $input);
        }

        return $refundsArray;
    }

    public function fetchRefundFee(array $input)
    {
        (new Validator)->validateInput('get_fee', $input);

        $paymentId = $input[Entity::PAYMENT_ID];

        unset($input[Entity::PAYMENT_ID]);

        Payment\Entity::verifyIdAndStripSign($paymentId);

        $payment = $this->repo->payment->findOrFailPublic($paymentId);

        return $this->getNewProcessor($this->merchant)->fetchFeeForRefundAmount($payment, $input);
    }

    public function scroogeFetchRefundFee(array $input)
    {
        (new Validator)->validateInput('scrooge_fetch_fee', $input);

        $paymentId = $input[Entity::PAYMENT_ID];

        unset($input[Entity::PAYMENT_ID]);

        $payment = $this->repo->payment->findOrFail($paymentId);

        $merchant = $this->repo->merchant->fetchMerchantFromEntity($payment);

        return $this->getNewProcessor($merchant)->fetchFeeForRefundAmount($payment, $input);
    }

    // updates payment for a refund creation/reversal
    // bulk route. consumed by scrooge for re arch refunds
    public function scroogeRefundsPaymentUpdate(array $input)
    {
        (new Validator)->validateInput('refunds_payment_update', $input);

        $this->trace->info(TraceCode::REFUNDS_PAYMENT_UPDATE_INITIATED, $input);

        $result = [];

        foreach ($input[RefundConstants::REFUNDS] as $refundInput)
        {
            $result[$refundInput[RefundConstants::ID]] = [
                RefundConstants::ERROR => NULL,
            ];

            try
            {
                $paymentId = $refundInput[Entity::PAYMENT_ID];

                $payment = $this->repo->payment->findOrFailPublic($paymentId);

                $merchant = $this->repo->merchant->fetchMerchantFromEntity($payment);

                $this->getNewProcessor($merchant)->refundPaymentUpdate($payment, $refundInput);
            }
            catch (\Exception $ex)
            {
                $result[$refundInput[RefundConstants::ID]] = [
                    RefundConstants::ERROR => [
                        RefundConstants::CODE => $ex->getCode(),
                        RefundConstants::MESSAGE => $ex->getMessage(),
                    ]
                ];
            }
        }

        $this->trace->info(TraceCode::REFUNDS_PAYMENT_UPDATE_SUMMARY, $result);

        return $result;
    }

    // creates transaction and updates payment for refunds created on scrooge
    public function scroogeRefundsTransactionCreate(array $refundInput)
    {
        (new Validator)->validateInput('refunds_transaction_create', $refundInput);

        // mode must be set for instant refunds as its needed for pricing calculation. throws exception otherwise
        if ((empty($refundInput[RefundEntity::MODE]) === true) and
            (in_array($refundInput[RefundEntity::SPEED_DECISIONED], Speed::REFUND_INSTANT_SPEEDS, true) === true))
        {
            throw new Exception\BadRequestValidationFailureException('mode must be set for instant refund speeds');
        }

        $this->trace->info(TraceCode::SCROOGE_REFUND_TRANSACTION_CREATE_INITIATED, $refundInput);

        $result = [];

        try
        {
            $paymentId = $refundInput[Entity::PAYMENT_ID];

            $payment = $this->repo->payment->findOrFailPublic($paymentId);

            $merchant = $this->repo->merchant->fetchMerchantFromEntity($payment);

            $result = $this->getNewProcessor($merchant)->scroogeRefundTransactionCreate($payment, $refundInput);
        }
        catch (\Exception $ex)
        {
            $result = RefundHelpers::getScroogeRefundTransactionCreateResponse($ex);
        }

        $this->trace->info(TraceCode::SCROOGE_REFUND_TRANSACTION_CREATE_COMPLETE, $result);

        return $result;
    }

    public function fetchRefundCreationData(array $input)
    {
        (new Validator)->validateInput('fetch_refund_creation_data', $input);

        $payment = (new PaymentService)->fetchPaymentEntity($input[Entity::PAYMENT_ID]);

        return $this->getNewProcessor($this->merchant)->fetchRefundCreationData($payment, $input);
    }

    public function verifyMultiple($ids)
    {
        $refundIds = explode(',', $ids);

        $data = [];

        foreach ($refundIds as $refundId)
        {
            Refund\Entity::verifyIdAndStripSign($refundId);

            $refund = $this->repo->refund->findOrFailPublic($refundId);

            $merchant = $this->repo->merchant->fetchMerchantFromEntity($refund);

            $data[] = $this->getNewProcessor($merchant)->verifyInternalRefund($refund);
        }

        return $data;
    }

    protected function getModeForRefunds($refundsArray)
    {
        $refundModes = [];

        foreach ($refundsArray[Base\PublicCollection::ITEMS] as $refundArray)
        {
            if (empty($refundArray[Entity::SPEED_PROCESSED]) === true)
            {
                $refundModes[$refundArray[Entity::ID]] = '';

                continue;
            }

            $speed = ($refundArray[Entity::SPEED_PROCESSED] === Speed::NORMAL) ? Speed::NORMAL : Speed::INSTANT;

            $refundModes[$refundArray[Entity::ID]] = $speed;
        }

        return $refundModes;
    }

    protected function getPublicStatusValueFromStatus($refundArray)
    {
        if (isset($refundArray[Entity::STATUS]) === false)
        {
                return '';
        }

        return ($refundArray[Entity::STATUS] === Status::PENDING) ? Status::PROCESSING : $refundArray[Entity::STATUS];
    }

    protected function getPublicStatusForRefunds($refundsArray)
    {
        $refundStatus = [];

        foreach ($refundsArray[Base\PublicCollection::ITEMS] as $refundArray)
        {
            $refundStatus[$refundArray[Entity::ID]] = $this->getPublicStatusValueFromStatus($refundArray);
        }

        return $refundStatus;
    }

    protected function addPublicStatus(array &$refundsArray, array $input = [])
    {
        if (isset($input[Entity::PUBLIC_STATUS]) === true)
        {
            foreach ($refundsArray[Base\PublicCollection::ITEMS] as $key => $refundArray)
            {
                $refundsArray[Base\PublicCollection::ITEMS][$key][Entity::STATUS] = $input[Entity::PUBLIC_STATUS];
            }
        }
        else
        {
            $refundStatus = $this->getPublicStatusForRefunds($refundsArray);

            foreach ($refundsArray[Base\PublicCollection::ITEMS] as &$refundArray)
            {
                $refundId = $refundArray[Entity::ID];

                $refundArray[Entity::STATUS] = $refundStatus[$refundId];
            }
        }
    }

    public function addModeAndPublicStatus(&$refundsArray)
    {
        $refundModes = $this->getModeForRefunds($refundsArray);

        $refundStatus = $this->getPublicStatusForRefunds($refundsArray);

        foreach ($refundsArray[Base\PublicCollection::ITEMS] as &$refundArray)
        {
            $refundId = $refundArray[Entity::ID];

            $refundArray[Entity::SPEED] = $refundModes[$refundId];

            $refundArray[Entity::STATUS] = $refundStatus[$refundId];
        }
    }

    public function makeGatewayRefundCall(string $refundId, array $input)
    {
        $refund = $this->repo->refund->findOrFail($refundId);

        $merchant = $refund->merchant;

        $response = $this->getNewProcessor($merchant)->scroogeGatewayRefund($refund, $input);

        return $response;
    }

    public function makeGatewayVerifyRefundCall(string $refundId, array $input)
    {
        $refund = $this->repo->refund->findOrFail($refundId);

        $merchant = $refund->merchant;

        $response = $this->getNewProcessor($merchant)->scroogeGatewayVerifyRefund($refund, $input);

        return $response;
    }

    public function makeScroogeVerifyRefundCall(string $refundId, array $input)
    {
        $refund = $this->repo->refund->findOrFail($refundId);

        $merchant = $refund->merchant;

        $response = $this->getNewProcessor($merchant)->scroogeVerifyRefund($refund, $input);

        return $response;
    }

    public function createScroogeRefund(string $refundId)
    {
        $refund = $this->repo->refund->findOrFail($refundId);

        $merchant = $refund->merchant;

        $response = $this->getNewProcessor($merchant)->callRefundFunctionOnScrooge($refund);

        return $response;
    }

    public function createScroogeRefundBulk(array $input)
    {
        (new Validator)->validateInput('create_scrooge_refund_bulk', $input);

        $refundIds = $input[RefundConstants::REFUND_IDS];

        Entity::verifyIdAndSilentlyStripSignMultiple($refundIds);

        $this->trace->info(TraceCode::REFUND_SCROOGE_CREATE_BULK_INITIATED,
            [
                RefundConstants::REFUND_IDS => $refundIds,
            ]);

        $successes = $failures = 0;

        $failureRefunds = [];

        $total = count($refundIds);


        foreach ($refundIds as $refundId)
        {
            try
            {
                $this->createScroogeRefund($refundId);

                $successes++;
            }
            catch (\Exception $ex)
            {
                $failures++;

                $failureRefunds[] = $refundId;

                $this->trace->traceException($ex);
            }
        }

        $this->trace->info(
            TraceCode::REFUND_SCROOGE_CREATE_BULK_DISPATCHED,
            [
                'total_count'       => $total,
                'success_count'     => $successes,
                'failures_count'    => $failures,
                'failed_refunds'    => $failureRefunds
            ]);

        return [
            'total_count'       => $total,
            'success_count'     => $successes,
            'failures_count'    => $failures,
            'failed_refunds'    => $failureRefunds
        ];
    }

    public function createMissingTransactions()
    {
        $refundsWithoutTransaction = $this->repo->refund->fetchRefundsWithoutTransactionsAndWithPaymentTransactions();

        $totalCount = count($refundsWithoutTransaction);

        $this->trace->info(
            TraceCode::REFUNDED_TRANSACTIONS_MISSING,
            [
                'total_count' => $totalCount,
                'refund_ids' => $refundsWithoutTransaction->pluck('id')->toArray(),
            ]);

        $summary = $this->createAllRefundsMissingTransaction($refundsWithoutTransaction);

        return $summary;
    }

    public function createMissingTransactionsForGatewayRefunded()
    {
        $gatewayRefundedWithoutTxns = $this->repo->refund->fetchGatewayRefundedRefundsWithoutTxns();

        $totalCount = count($gatewayRefundedWithoutTxns);

        $this->trace->info(
            TraceCode::GATEWAY_REFUNDED_TXNS_MISSING,
            [
                'total_count' => $totalCount,
                'refund_ids' => $gatewayRefundedWithoutTxns->pluck('id')->toArray(),
            ]);

        $summary = $this->createAllRefundsMissingTransaction($gatewayRefundedWithoutTxns);

        if ($totalCount === 0)
        {
            return $summary;
        }

        $this->trace->info(
            TraceCode::GATEWAY_REFUNDED_TXNS_CREATED_SUMMARY,
            $summary
        );

        return $summary;
    }

    public function createGatewayRefundRecords($gateway)
    {
        // Currently, we are running this for billdesk and freecharge refund timeouts only.
        if (in_array($gateway, Payment\Gateway::REFUND_TIMEOUT_HANDLED_GATEWAYS, true) === false)
        {
            throw new Exception\LogicException(
                'Cannot create a refund record on the gateway entity for the given gateway',
                null,
                [
                    'gateway' => $gateway
                ]);
        }

        $createdAfter = time() - RefundConstants::GATEWAY_REFUND_RECORDS_TIME_LIMIT;

        $refunds = $this->repo->refund->fetchMissingRefundsOfGateway($gateway, $createdAfter);

        $this->trace->info(
            TraceCode::CREATE_GATEWAY_REFUND_RECORD_CRON_REFUNDS,
            [
                'gateway' => $gateway,
                'refunds' => $refunds
            ]);

        $data = [];

        // We get all the gateway refunds. We return back data for applicable and if success.
        foreach ($refunds as $refund)
        {
            if ($refund->isScrooge() === true)
            {
                continue;
            }

            $merchant = $this->repo->merchant->fetchMerchantFromEntity($refund);

            $data[] = $this->getNewProcessor($merchant)->createGatewayRefundRecord($refund);
        }

        $applicable = $success = 0;
        $successRefundData = [];

        foreach ($data as $refundData)
        {
            if ($refundData['applicable'] === true)
            {
                $applicable++;
            }

            if ($refundData['success'] === true)
            {
                $success++;
                $successRefundData[] = $refundData;
            }
        }

        $summary = [
            'total_applicable_refunds'  => $applicable,
            'total_success_refunds'     => $success,
            'success_refund_data'       => $successRefundData,
        ];

        $this->trace->info(
            TraceCode::CREATE_GATEWAY_REFUND_RECORD_SUMMARY,
            $summary);

        return $summary;
    }

    protected function createAllRefundsMissingTransaction(
        Base\PublicCollection $refundsWithoutTxn)
    {
        $totalCount = count($refundsWithoutTxn);

        $successes = $failures = 0;
        $failureRefundIds = [];

        foreach ($refundsWithoutTxn as $refundWithoutTxn)
        {
            $success = $this->createMissingRefundTransaction($refundWithoutTxn);

            if ($success === true)
            {
                $successes++;
            }
            else
            {
                $failures++;
                $failureRefundIds[] = $refundWithoutTxn->getId();
            }
        }

        return [
            'total_count'       => $totalCount,
            'success_count'     => $successes,
            'failures_count'    => $failures,
            'failed_refunds'    => $failureRefundIds
        ];
    }

    protected function createMissingRefundTransaction(Entity $refundWithoutTxn)
    {
        $this->trace->info(
            TraceCode::REFUND_TRANSACTION_CREATE_REQUEST,
            $refundWithoutTxn->toArray());

        try
        {
            $payment = $refundWithoutTxn->payment;

            $this->repo->transaction(
                function()
                use ($refundWithoutTxn, $payment)
                {
                    $transaction = $this->getNewProcessor($refundWithoutTxn->merchant)
                                        ->createTransactionForRefund(
                                            $refundWithoutTxn, $payment);

                    if ($transaction === null)
                    {
                        throw new Exception\LogicException(
                            'Transaction did not get created',
                            null,
                            [
                                'refund_id'     => $refundWithoutTxn->getId(),
                                'payment_id'    => $payment->getId(),
                            ]);
                    }

                    //
                    // This needs to be saved here because of the association with
                    // transaction which is set in the createTransactionForRefund function.
                    //
                    $this->repo->saveOrFail($refundWithoutTxn);
                });

            return true;
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::REFUND_TRANSACTION_CREATE_FAILED,
                $refundWithoutTxn->toArray()
            );

            return false;
        }
    }

    protected function getNewProcessor($merchant)
    {
        $processor = new Payment\Processor\Processor($merchant);

        return $processor;
    }

    public function retryFailedRefunds($input)
    {
        $this->trace->info(
            TraceCode::REFUND_RETRY_INITIATED,
            $input);

        // Adding a lock for 60 minutes to avoid race conditions on the cron.
        $summary = $this->mutex->acquireAndRelease(
            'refund_retry_failed',
            function() use ($input)
            {
                $gateways = (array) ($input['gateways'] ?? Payment\Gateway::REFUND_RETRY_GATEWAYS);

                //
                // Every combination of gateway / refund needs to be processed
                // Get the appropriate refunds and pass them as part of the refund
                // Get refunds that have failed and those that have not been
                // retried more than 3. Post every retry update last retried at.
                //
                $refunds = $this->repo
                                ->refund
                                ->fetchRefundsByGatewayAndAttempts($gateways, RefundConstants::MAX_REFUND_RETRY_ATTEMPTS);

                $status = [];

                $success = $failure = 0;

                foreach ($refunds as $refund)
                {
                    $refundId = $refund->getId();

                    try
                    {
                        $processor = $this->getNewProcessor($refund->merchant);

                        $status[$refundId] = $processor->processRefundRetry($refund);

                        $success++;
                    }
                    catch (\Throwable $e)
                    {
                        $this->trace->traceException(
                            $e,
                            Trace::DEBUG,
                            TraceCode::PAYMENT_VERIFY_REFUND_EXCEPTION,
                            [
                                'refund_id' => $refundId,
                                'refund_attempts' => $refund->getAttempts()
                            ]);

                        $failure++;
                    }
                }

                return [
                    'successful'    => $success,
                    'failure'       => $failure,
                    'status'        => $status,
                ];
            },
            3600,
            ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS);

        $this->trace->info(
            TraceCode::REFUND_RETRY_RESULT,
            [
                'summary' => $summary
            ]);

        return $summary;
    }

    public function retry(string $id, array $input)
    {
        (new Validator)->validateInput('retry', $input);

        $this->trace->info(
            TraceCode::REFUND_RETRY_INITIATED,
            [
                'refund_id' => $id
            ]
        );

        $internalId = $id;

        Entity::verifyIdAndSilentlyStripSign($internalId);

        $refund = $this->repo->refund->findOrFail($internalId);

        $refundStatus = $this->getNewProcessor($refund->merchant)->processRefundRetry($refund, $input);

        return [
            'refund_id' => $id,
            'status'    => $refundStatus
        ];
    }

    //
    // Support admin action for bulk retrying refunds via FTA to custom sources
    //
    public function retryRefundsViaCustomFundTransfersBatch(array  $input)
    {
       $tracePayload = [];

        try
        {
            $batchId = $this->app['request']->header(RequestHeader::X_Batch_Id) ?? null;
            $refundId = array_keys($input['refunds'])[0];
            $bankAccount = $input['refunds'][$refundId]['fta_data']['bank_account'];
            $beneficiaryName = $bankAccount[RefundConstants::BENEFICIARY_NAME];
            $accountNumber = $bankAccount[RefundConstants::ACCOUNT_NUMBER];
            $ifsc = $bankAccount[RefundConstants::IFSC];
            $transferMode = $bankAccount[RefundConstants::TRANSFER_MODE];
            $batchType = $input[Batch\Constants::TYPE];

            $tracePayload =[
                RefundConstants::REFUND_ID           => $refundId,
                RefundConstants::BENEFICIARY_NAME    => $beneficiaryName,
                RefundConstants::ACCOUNT_NUMBER      => $accountNumber,
                RefundConstants::IFSC                => $ifsc,
                RefundConstants::TRANSFER_MODE       => $transferMode,
                RequestHeader::X_Batch_Id            => $batchId,
                Batch\Constants::TYPE                => $batchType,
            ];

            $this->trace->debug(TraceCode::BATCH_PROCESSING_ENTRY, $tracePayload);

            $bankAccountData =
            [
                'type'    => $batchType,
                'refunds' => [
                    $refundId => [
                        'fta_data' => [
                            'bank_account' => [
                                'ifsc_code'        => $ifsc,
                                'account_number'   => $accountNumber,
                                'beneficiary_name' => $beneficiaryName,
                                'transfer_mode'    => $transferMode,
                            ]
                        ]
                    ]
                ]
            ];

            $response = $this->app['scrooge']->retryRefundsViaCustomFundTransfers($bankAccountData)['body'];

            if (empty($response[$refundId]['error']) ==  false)
            {
                $code = $response[$refundId]['error']['code'];
                $message = $response[$refundId]['error']['message'];
                throw new Exception\BadRequestValidationFailureException($message);
            }

            $input[RefundConstants::REFUND_ID]         = $refundId;
            $input[RefundConstants::BENEFICIARY_NAME]  = $beneficiaryName;
            $input[RefundConstants::ACCOUNT_NUMBER]    = $accountNumber;
            $input[RefundConstants::IFSC]              = $ifsc;
            $input[RefundConstants::TRANSFER_MODE]     = $transferMode;
            $input[RefundConstants::ERROR_CODE]        = null;
            $input[RefundConstants::ERROR_DESCRIPTION] = null;

        }
        catch (\Exception $e)
        {
            // RZP Exceptions have public error code & description which can be exposed in the output file
            $this->trace->traceException($e, null, TraceCode::BATCH_PROCESSING_ERROR, $tracePayload);

            $error = $e->getError();

            $input[RefundConstants::REFUND_ID]         = $refundId;
            $input[RefundConstants::BENEFICIARY_NAME]  = $beneficiaryName;
            $input[RefundConstants::ACCOUNT_NUMBER]    = $accountNumber;
            $input[RefundConstants::IFSC]              = $ifsc;
            $input[RefundConstants::TRANSFER_MODE]     = $transferMode;
            $input[RefundConstants::ERROR_CODE]        = $code ?? $error->getPublicErrorCode();
            $input[RefundConstants::ERROR_DESCRIPTION] = $error->getDescription();
        }
        finally
        {
            return $input;
        }
    }

    public function retryBulk(array $input)
    {
        RuntimeManager::setTimeLimit(300);

        (new Validator)->validateInput('retry_bulk', $input);

        $refundIds = $input['refund_ids'];

        Entity::verifyIdAndSilentlyStripSignMultiple($refundIds);

        $this->trace->info(TraceCode::REFUND_RETRY_BULK_INITIATED,
            [
                RefundConstants::REFUND_IDS => $refundIds,
            ]);

        $total = count($refundIds);

        foreach ($refundIds as $refundId)
        {
            $data = [
                'id' => $refundId,
                'mode' => Mode::LIVE,
                'verify' => true,
            ];

            BulkRefundJob::dispatch($data);
        }

        $this->trace->info(
            TraceCode::REFUND_RETRY_BULK_DISPATCHED,
            [
                'total' => $total
            ]);
    }

    public function retryBulkViaFta(array $input)
    {
        (new Validator)->validateInput('retry_bulk_via_fta', $input);

        $this->trace->info(TraceCode::REFUND_RETRY_BULK_VIA_FTA_INITIATED, $input);

        $retryFailures = [];

        foreach ($input[RefundConstants::REFUND_IDS] as $key => $refundId)
        {
            try
            {
                $refund = $this->repo->refund->findOrFail($refundId);

                $ftaData = [];

                switch ($input[RefundConstants::TRANSFER_METHOD])
                {
                    case RefundConstants::SOURCE_VPA :

                        $vpaId = $refund->payment->getVpa();

                        if (empty($vpaId) === false)
                        {
                            $ftaData[RefundConstants::VPA][RefundConstants::VPA_ADDRESS] = $vpaId;
                        }

                        break;
                }

                if (empty($ftaData) === true)
                {
                    throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INSUFFICIENT_DATA_FOR_FTA);
                }

                // Grouping 5 refunds for a second delay. Max. refunds allowed per request is 1000
                // so max delay for last group of refunds will be 199 seconds. Doing this since
                // Max delay supported by SQS is 900 seconds
                $ftaData[RefundConstants::DISPATCH_DELAY_TIME] = floor($key / RefundConstants::DISPATCH_BATCH_SIZE);

                $this->getNewProcessor($refund->merchant)->processRefundRetry($refund, $ftaData);
            }
            catch (\Exception $ex)
            {
                $this->trace->traceException($ex, null, null, [RefundConstants::REFUND_ID => $refundId]);

                $retryFailures[] = $refundId;
            }
        }

        return [
            'success_count' => count($input['refund_ids']) - count($retryFailures),
            'failure_count' => count($retryFailures),
            'failed_ids'    => $retryFailures,
        ];
    }

    public function retryScroogeRefundsWithoutVerify(array $input)
    {
        (new Validator)->validateInput('retry_scrooge_refunds_without_verify', $input);

        $this->trace->info(TraceCode::RETRY_SCROOGE_REFUNDS_WITHOUT_VERIFY, $input);

        $retryFailures = [];

        $refundIds = array_unique($input[RefundConstants::REFUND_IDS]);

        foreach ($refundIds as $key => $refundId)
        {
            try
            {
                $refund = $this->repo->refund->findOrFail($refundId);

                if ($refund->isScrooge() === false)
                {
                    throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_REFUND_NOT_SCROOGE);
                }

                if (($refund->isCreated() === false) and
                    ($refund->isInitiated() === false))
                {
                    throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_REFUND_INVALID_STATE_FOR_RETRY);
                }

                // Will be passing this flag to scrooge for skipping verify before retry
                $input[RefundConstants::SKIP_REFUND_VERIFY] = true;

                $this->getNewProcessor($refund->merchant)->processRefundRetry($refund, $input);
            }
            catch (\Exception $ex)
            {
                $this->trace->traceException($ex, null, null, [RefundConstants::REFUND_ID => $refundId]);

                $retryFailures[] = $refundId;
            }
        }

        return [
            'refunds_successfully_queued_for_retry' => count($refundIds) - count($retryFailures),
            'refund_retry_failure_count'            => count($retryFailures),
            'failed_ids'                            => $retryFailures,
        ];
    }

    public function directRetryBulk(array $input)
    {
        (new Validator)->validateInput('direct_retry_bulk', $input);

        $refundIds = $input['refund_ids'];

        Entity::verifyIdAndSilentlyStripSignMultiple($refundIds);

        $this->trace->info(TraceCode::REFUND_DIRECT_RETRY_BULK_INITIATED,
            [
                RefundConstants::REFUND_IDS => $refundIds,
            ]);

        $total = count($refundIds);

        foreach ($refundIds as $refundId)
        {
            $data = [
                'id' => $refundId,
                'mode' => Mode::LIVE,
                'verify' => false,
            ];

            BulkRefundJob::dispatch($data);
        }

        $this->trace->info(
            TraceCode::REFUND_DIRECT_RETRY_BULK_DISPATCHED,
            [
                'total' => $total
            ]);
    }

    public function verify(string $id)
    {
        $internalId = $id;

        Entity::verifyIdAndSilentlyStripSign($internalId);

        $refund = $this->repo->refund->findOrFailPublic($internalId);

        if ($refund->isScrooge() === true)
        {
            $verifySuccess = $this->app['scrooge']->verifyRefund($refund['id'])['body'];
        }
        else
        {
            $verifySuccess = $this->getNewProcessor($refund->merchant)->verifyRefund($refund);
        }

        return [
            'refund_id'      => $id,
            'verify_success' => $verifySuccess
        ];
    }

    public function editStatus($refundId, array $input)
    {
        Entity::verifyIdAndSilentlyStripSign($refundId);

        $refund = $this->repo->refund->findOrFailPublic($refundId);

        $refund->edit($input, 'editStatus');

        if ($refund->isProcessed() === true)
        {
            $refund->setErrorNull();

            if ($refund->getProcessedAt() === null)
            {
                $refund->setProcessedAt(time());
            }
        }

        if ($refund->isScrooge() === true)
        {
            $this->makeScroogeEditRefundRequest($refund, $input);
        }
        else
        {
            $this->repo->saveOrFail($refund);
        }

        return [
            'status' => $refund->getStatus(),
        ];
    }

    public function update($id, array $input)
    {
        $refundId = Entity::verifyIdAndStripSign($id);

        $refund = $this->mutex->acquireAndRelease($refundId,
            function() use ($refundId, $input)
            {
                $refund = $this->repo->refund->findByIdAndMerchant($refundId, $this->merchant);

                $refund->edit($input);

                $this->repo->saveOrFail($refund);

                return $refund;
            },
            20,
            ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS);

        return $refund->toArrayPublic();
    }

    public function updateScroogeRefundStatus(string $refundId, array $input)
    {
        $this->trace->info(
            TraceCode::REFUND_UPDATE_STATUS_REQUEST,
            [
                'refund_id' => $refundId,
                'event'     => $input['event'] ?? '',
            ]);

        try
        {
            $refund = $this->repo->transaction(
                function()
                use ($refundId, $input)
                {
                    $refund = $this->repo->refund->findOrFailPublic($refundId);

                    if ($refund->isScrooge() === true)
                    {
                        $refund->getValidator()->validateUpdateScroogeRefundStatus($input);

                        $processor = $this->getNewProcessor($refund->merchant);

                        switch ($input['event'])
                        {
                            case Refund\ScroogeEvents::PROCESSED_EVENT:

                                $refund->setStatusProcessed();

                                if ((isset($input[RefundEntity::SPEED_PROCESSED]) === true) and
                                    ($refund->getSpeedProcessed() === null))
                                {
                                    $refund->setSpeedProcessed($input[RefundEntity::SPEED_PROCESSED]);
                                }

                                $refund->setGatewayRefunded(true);

                                if (($refund->merchant->isFeatureRefundPublicStatusOrPendingStatusEnabled() === true) or
                                    ($refund->getSpeedProcessed() !== RefundSpeed::NORMAL))
                                {
                                    $processor->eventRefundProcessed($refund);
                                }

                                $this->updateRefund($refund, $input);

                                break;

                            case Refund\ScroogeEvents::FAILED_EVENT:

                                $processor->reverseRefund($refund);

                                if ($refund->payment->hasBeenCaptured() === true)
                                {
                                    $variant = $this->app->razorx->getTreatment($refundId,
                                        RefundConstants:: RAZORX_KEY_SKIP_PAYMENT_ENTITY_UPDATE_FOR_REVERSAL,
                                        $this->mode
                                    );

                                    $this->trace->info(
                                        TraceCode::PAYMENT_STATUS_UPDATE_REQUEST,
                                        [
                                            'razorx_variant'               => $variant,
                                            'refund_id'                    => $refundId,
                                            'payment_id'                   => $refund->payment->getId(),
                                            'payment_status'               => $refund->payment->getStatus(),
                                            'payment_refund_status'        => $refund->payment->getRefundStatus(),
                                            'payment_amount_refunded'      => $refund->payment->getAmountRefunded(),
                                            'payment_base_amount_refunded' => $refund->payment->getBaseAmountRefunded(),
                                        ]);

                                    if ($variant !== RefundConstants::RAZORX_VARIANT_ON)
                                    {
                                        $processor->revertPaymentToRefundableState($refund);
                                    }
                                }

                                $processor->eventRefundFailed($refund);

                                break;

                            case Refund\ScroogeEvents::FEE_ONLY_REVERSAL_EVENT:

                                //
                                // In optimum flow - we would have debit amount + fees in the transaction,
                                // on failure - we have to reverse the whole amount since, gateway will directly settle
                                // in case of DirectSettlementRefund - but the refund status will remain as is and not change
                                //
                                if ($refund->isDirectSettlementRefund() === true)
                                {
                                    $this->getNewProcessor($refund->merchant)->reverseRefund($refund);
                                }
                                else
                                {
                                    $feeOnlyReversal = true;

                                    $this->getNewProcessor($refund->merchant)->reverseRefund($refund, $feeOnlyReversal);
                                }

                                $refund->setSpeedProcessed(RefundSpeed::NORMAL);

                                $skipMerchantWebhooks = $input['skip_merchant_webhooks'] ?? false;

                                if ($skipMerchantWebhooks === true)
                                {
                                    break;
                                }

                                $processor->eventRefundSpeedChanged($refund);

                                if ($refund->merchant->isFeatureRefundPublicStatusOrPendingStatusEnabled() === false)
                                {
                                    $processor->eventRefundProcessed($refund);
                                }

                                break;

                            case Refund\ScroogeEvents::PROCESSED_TO_FILE_INIT_EVENT:

                                $this->trace->info(
                                    TraceCode::REFUND_PROCESSED_TO_CREATED,
                                    [
                                        'refund_id'        => $refundId,
                                        'status'           => $refund->getStatus(),
                                        'reference1'       => $refund->getReference1(),
                                        'processed_at'     => $refund->getProcessedAt(),
                                        'gateway_refunded' => $refund->getGatewayRefunded(),
                                    ]);

                                if ((isset($input[RefundEntity::STATUS])) and
                                    ($input[RefundEntity::STATUS] === 'file_init') and
                                    ($refund->getStatus() === Refund\Status::PROCESSED))
                                {
                                    $processor->revertProcessedRefundToCreatedState($refund);
                                }

                                break;
                        }

                        $this->repo->saveOrFail($refund);

                        $refund = $refund->toArrayPublic();
                    }
                    else
                    {
                        $this->trace->error(
                            TraceCode::REFUND_UPDATE_STATUS_NON_SCROOGE_GATEWAY,
                            [
                                'refund_id' => $refund->getId(),
                                'status'    => $refund->getStatus(),
                            ]);
                    }

                    return $refund;
                });
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException($ex, null, null, ['refund_id' => $refundId]);

            throw $ex;
        }

        return $refund;
    }

    public function markProcessedBulk(array $input)
    {
        (new Validator)->validateInput('mark_processed_bulk', $input);

        $this->trace->info(TraceCode::REFUND_MARK_PROCESSED_BULK_INITIATED, $input);

        $refundIds = $input['refund_ids'];

        $total = count($refundIds);

        $allRefundsStatuses = [];

        foreach ($refundIds as $refundId)
        {
            try
            {
                $refund = $this->repo->refund->findByPublicId($refundId);

                $this->trace->info(
                    TraceCode::REFUND_MARK_PROCESSED_OLD_STATUS,
                    [
                        'status' => $refund->getStatus()
                    ]);

                if ($refund->isScrooge() === true)
                {
                    $data = [
                        Entity::MODE           => $input[Entity::PROCESSED_SOURCE] ?? '',
                        Payment\Entity::STATUS => Status::PROCESSED,
                    ];

                    $this->makeScroogeEditRefundRequest($refund, $data);
                }
                else
                {
                    $refund->setStatusProcessed();

                    $this->repo->saveOrFail($refund);
                }

                $allRefundsStatuses[Status::PROCESSED][] = $refundId;
            }
            catch (\Exception $ex)
            {
                $this->trace->traceException($ex, null, null, ['refund_id' => $refundId]);

                $allRefundsStatuses['errors'][] = [
                    'refund_id' => $refundId,
                    'message'   => $ex->getMessage(),
                ];
            }
        }

        $summary = [
            'total' => $total,
            'refunds_statuses' => $allRefundsStatuses,
        ];

        $this->trace->info(TraceCode::REFUND_MARK_PROCESSED_BULK_SUMMARY, $summary);

        return $summary;
    }

    /**
     * @param Entity $refund
     * @param array $input
     * @param string $event
     */
    public function makeScroogeEditRefundRequest(Entity $refund, array $input, string $event = Refund\ScroogeEvents::PROCESSED_EVENT)
    {
        $refund->getValidator()->validateScroogeEditRefund($input);

        $refundData = [
            'refund_id'     => $refund->getId(),
            'event'         => $event,
            'gateway_keys'  => [
                Entity::REFERENCE1 => $input[Entity::REFERENCE1] ?? '',
                Entity::REFERENCE2 => $input[Entity::REFERENCE2] ?? '',
            ],
            Entity::PROCESSED_SOURCE    => $input[Entity::MODE] ?? '',
            RefundConstants::FTA_UPDATE => $input[RefundConstants::FTA_UPDATE] ?? false,
        ];

        if (empty($input[FTA\Entity::BANK_RESPONSE_CODE]) === false)
        {
            $refundData[FTA\Entity::BANK_RESPONSE_CODE] = $input[FTA\Entity::BANK_RESPONSE_CODE];
        }

        $data = [
            'refunds' => [
                $refundData
            ],
            'mode' => $this->mode,
        ];

        $this->trace->info(
            TraceCode::REFUND_UPDATE_QUEUE_SCROOGE_DISPATCH,
                     $data
        );

        try
        {
            ScroogeRefundUpdate::dispatch($data);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::REFUND_UPDATE_QUEUE_SCROOGE_DISPATCH_FAILED,
                $data
            );
        }
    }

    public function fetchRefundsDetailsForCustomer(array $input)
    {
        $traceInput = $input;
        unset($traceInput['captcha']);

        $this->trace->info(
            TraceCode::CUSTOMER_TRACK_REFUND_STATUS_V2_INITIATED,
            [
                'input' => $traceInput
            ]
        );

        (new Validator)->validateInput('customer_refunds_details', $input);

        $mode = $input['mode'] ?? Mode::LIVE;

        $this->auth->setModeAndDbConnection($mode);

        // Since this is a direct auth route - and we do not have the merchant ID
        // we need to allow multiple fetch without merchant ID
        $merchantIdRequiredForMultipleFetch = false;

        $this->repo->payment->setMerchantIdRequiredForMultipleFetch($merchantIdRequiredForMultipleFetch);
        $this->repo->refund->setMerchantIdRequiredForMultipleFetch($merchantIdRequiredForMultipleFetch);

        $return = [
            RefundConstants::ID_TYPE => RefundConstants::UNKNOWN,
            RefundConstants::PAYMENTS => [],
        ];

        switch(true)
        {
            case (empty($input[RefundConstants::PAYMENT_ID]) === false):
                // Given RZP public payment_id
                $this->populateDetailsFromPaymentId($input[RefundConstants::PAYMENT_ID], $return);

                break;

            case (empty($input[RefundConstants::REFUND_ID]) === false):
                // Given RZP public refund_id
                $this->populateDetailsFromRefundId($input[RefundConstants::REFUND_ID], $return);

                break;

            case (empty($input[RefundConstants::ORDER_ID]) === false):
                // Given RZP public order_id
                $this->populateDetailsFromOrderId($input[RefundConstants::ORDER_ID], $return);

                break;

            default:
                // Given id - could be RZP internal id, UPI RRN, Merchant reference number (from notes)
                $this->fetchRefundDetailsForCustomerFromId($input, $return);
        }

        $this->populateMerchantSupportDetails($return);

        $this->trace->info(
            TraceCode::CUSTOMER_TRACK_REFUND_STATUS_V2_SERVED,
            [
                'input' => $traceInput
            ] + $return
        );

//      Slicing data for security
        $response = $this->slicingDetailsforSecurity($return);

        return $response;
    }

    protected function slicingDetailsforSecurity(array $payment_array)
    {
        if(empty($payment_array) === false)
        {
            $allowedKeys = [RefundConstants::ID,RefundConstants::AMOUNT,RefundConstants::CURRENCY,RefundConstants::PAYMENT_ID,RefundConstants::SCROOGE_CREATED_AT
                ,RefundConstants::STATUS,RefundConstants::PRIMARY_MESSAGE,RefundConstants::SECONDARY_MESSAGE,RefundConstants::TERTIARY_MESSAGE,
                RefundConstants::ACQUIRER_DATA,RefundConstants::MERCHANT_NAME,RefundConstants::DAYS,RefundConstants::LATE_AUTH];

            if(empty($payment_array["payments"][0]) === false)
            {
                foreach ($payment_array["payments"] as $key=>$payment)
                {
                    if(empty($payment["payment"]) === false)
                    {
                        $updated_payment = array_intersect_key($payment["payment"], array_flip($allowedKeys));
                        $payment_array["payments"][$key]["payment"] = $updated_payment;
                    }

                    if(empty($payment["refunds"]) === false)
                    {
                        foreach ($payment_array["payments"][$key]["refunds"] as $refund_key => $refund)
                        {
                            $updated_refund = array_intersect_key($refund, array_flip($allowedKeys));
                            $payment_array["payments"][$key]["refunds"][$refund_key] = $updated_refund;
                        }
                    }
                }
            }
        }
        return $payment_array;
    }

    public static function verifyUpiRrn($id)
    {
        $rrnCheckRegex = '/^[0-9]{'. '12' .'}$/i';

        // preg_match() returns int 0 when the pattern does not match
        // and int 1 if a match is found. false (boolean) is returned
        // whenever any error happens.
        $res = (bool) preg_match($rrnCheckRegex, $id);

        return $res;
    }

    /**
     * @param array $return
     * @param Payment\Entity $payment
     */
    protected function populateRefundDetailsForCustomer(array &$return, Payment\Entity $payment)
    {
        $refunds = $payment->refunds;

        $populateMessages = true;

        array_push($return[RefundConstants::PAYMENTS], [
            RefundConstants::REFUNDS => isset($refunds) ? $refunds->toArrayPublicCustomer($populateMessages) : [],
            RefundConstants::PAYMENT => isset($payment) ? $payment->toArrayPublicCustomer($populateMessages) : [],
        ]);
    }

    /**
     * @param $id
     * @param array $return
     * @param bool $continueSearch
     */
    protected function populateDetailsFromPaymentId($id, array &$return, bool &$continueSearch = true)
    {
        $payment = $this->getPaymentFromPaymentIdForCustomerDetails($id);

        if (empty($payment) === false)
        {
            $this->populateRefundDetailsForCustomer($return, $payment);

            $return[RefundConstants::ID_TYPE] = RefundConstants::RZP_ID;

            $continueSearch = false;
        }
    }

    /**
     * @param $id
     * @param array $return
     * @param bool $continueSearch
     */
    protected function populateDetailsFromRefundId($id, array &$return, bool &$continueSearch = true)
    {
        $refund = $this->getRefundFromRefundIdForCustomerDetails($id);

        if (empty($refund) === false)
        {
            $payment = $refund->payment;

            $this->populateRefundDetailsForCustomer($return, $payment);

            $return[RefundConstants::ID_TYPE] = RefundConstants::RZP_ID;

            $continueSearch = false;
        }
    }

    /**
     * @param $id
     * @param array $return
     * @param bool $continueSearch
     */
    protected function populateDetailsFromOrderId($id, array &$return, bool &$continueSearch = true)
    {
        $order = $this->getOrderFromOrderIdForCustomerDetails($id);

        if (empty($order) === false)
        {
            $payments = $order->payments;

            foreach ($payments as $payment)
            {
                $this->populateRefundDetailsForCustomer($return, $payment);
            }

            $return[RefundConstants::ID_TYPE] = RefundConstants::RZP_ID;

            $continueSearch = false;
        }
    }

    /**
     * @param array $input
     * @param array $return
     * @throws Exception\BadRequestValidationFailureException
     * @throws Exception\InvalidArgumentException
     */
    protected function fetchRefundDetailsForCustomerFromId(array $input, array &$return)
    {
        $id = $input[RefundConstants::ID];

        $continueSearch = true;

        // check if RRN
        if (self::verifyUpiRrn($id) === true)
        {
            $actions = [Payment\Action::AUTHORIZE, Payment\Action::REFUND];

            // Check upi table - authorize action
            $this->fetchRefundDetailsForCustomerFromUpiRRN($id, $actions, $return, $continueSearch);
        }
        // check if RZP ID
        else if(Base\UniqueIdEntity::verifyUniqueId($id, false) === true)
        {
            // Check payment/refund/order tables
            $this->populateDetailsFromPaymentId($id, $return, $continueSearch);

            if ($continueSearch === true)
            {
                $this->populateDetailsFromRefundId($id, $return, $continueSearch);
            }

            if ($continueSearch === true)
            {
                $this->populateDetailsFromOrderId($id, $return, $continueSearch);
            }
        }

        // Fetch from merchant notes
        if ($continueSearch === true)
        {
            (new Validator)->validateCustomerRefundFetchDetailsFromMerchantNotes($id);

            $this->fetchRefundDetailsForCustomerFromMerchantNotes($id, $return);
        }
    }

    /**
     * @param $id
     * @param $actions
     * @param array $return
     * @param bool $continueSearch
     */
    protected function fetchRefundDetailsForCustomerFromUpiRRN($id, $actions, array &$return, bool &$continueSearch = true)
    {
        $upiEntity = $this->repo->upi->fetchByNpciReferenceIdAndActions($id, $actions);

        if (empty($upiEntity) === false)
        {
            $paymentId = $upiEntity->getPaymentId();

            $payment = $this->repo->payment->find($paymentId);

            if (empty($payment) === false)
            {
                $this->populateRefundDetailsForCustomer($return, $payment);

                $return[RefundConstants::ID_TYPE] = RefundConstants::NPCI_RRN;

                $continueSearch = false;
            }
        }
    }

    /**
     * @param $id
     * @param array $return
     * @throws Exception\BadRequestValidationFailureException
     * @throws Exception\InvalidArgumentException
     */
    protected function fetchRefundDetailsForCustomerFromMerchantNotes($id, array &$return)
    {
        $payment = $this->repo->payment->fetch([Payment\Entity::NOTES => $id]);

        if (empty($payment->toArray()) === false)
        {

            if (count($payment->toArray()) > 1)
            {
                $this->trace->info(
                    TraceCode::CUSTOMER_TRACK_REFUND_STATUS_V2_MULTIPLE_ENTITIES,
                    [
                        'search_id' => $id,
                    ]
                );
            }

            $payment = $this->repo->payment->find($payment->toArray()[0][Payment\Entity::ID]);

            $this->populateRefundDetailsForCustomer($return, $payment);

            $return[RefundConstants::ID_TYPE] = RefundConstants::MERCHANT_REFERENCE;
        }
        else
        {
            $refund = $this->repo->refund->fetch([Entity::NOTES => $id]);

            if (empty($refund->toArray()) === false)
            {
                if (count($refund->toArray()) > 1)
                {
                    $this->trace->info(
                        TraceCode::CUSTOMER_TRACK_REFUND_STATUS_V2_MULTIPLE_ENTITIES,
                        [
                            'search_id' => $id,
                        ]
                    );
                }

                $refund = $this->repo->refund->find($refund->toArray()[0][Entity::ID]);

                $payment = $refund->payment;

                $this->populateRefundDetailsForCustomer($return, $payment);

                $return[RefundConstants::ID_TYPE] = RefundConstants::MERCHANT_REFERENCE;
            }
        }
    }

    /**
     * @param array $input
     * @return array
     */
    public function fetchRefundDetailsForCustomer(array $input)
    {
        $traceInput = $input;
        unset($traceInput['captcha']);

        $this->trace->info(
            TraceCode::CUSTOMER_TRACK_REFUND_STATUS_INITIATED,
            [
                'input' => $traceInput
            ]
        );

        (new Validator)->validateInput('customer_refund_details', $input);

        $mode = $input['mode'] ?? Mode::LIVE;

        $this->auth->setModeAndDbConnection($mode);

        if (empty($input['payment_id']) === false)
        {
            $payment = $this->getPaymentFromPaymentIdForCustomerDetails($input['payment_id']);

            if (empty($payment) === false)
            {
                $refunds = $payment->refunds;
            }
        }
        else if (empty($input['refund_id']) === false)
        {
            $refund = $this->getRefundFromRefundIdForCustomerDetails($input['refund_id']);

            if (empty($refund) === false)
            {
                $payment = $refund->payment;

                $refunds = $payment->refunds;
            }
        }
        else
        {
            $payment = $this->getPaymentFromReservationIdForCustomerDetails($input['reservation_id']);

            if (empty($payment) === false)
            {
                $refunds = $payment->refunds;
            }
        }

        if (empty($refunds) === false)
        {
            if ($refunds->count() > 0)
            {
                // This needs to be set for `toArrayPublicCustomer`. Specifically, for the acquirer data.
                $this->auth->setMerchantById($refunds->first()->getMerchantId());
            }
        }

        $return = [
            'refunds' => isset($refunds) ? $refunds->toArrayPublicCustomer() : [],
            'payment' => isset($payment) ? $payment->toArrayPublicCustomer() : [],
        ];

        $this->trace->info(
            TraceCode::CUSTOMER_TRACK_REFUND_STATUS_SERVED,
            [
                'input' => $traceInput
            ] + $return
        );

        return $return;
    }

    protected function getPaymentFromReservationIdForCustomerDetails($reservationId)
    {
        $featureEntities = $this->repo->feature->findMerchantsHavingFeatures([Feature\Constants::IRCTC_REPORT]);

        $irctcMerchantIds = $featureEntities->pluck(Feature\Entity::ENTITY_ID)->toArray();

        $payment = $this->repo->useSlave(function () use ($reservationId, $irctcMerchantIds)
        {
            return $this->repo->payment->fetchFirstAuthorizedPaymentsForOrderReceiptOfMerchants($reservationId, $irctcMerchantIds);
        });

        if (empty($payment) === true)
        {
            return null;
        }

        return $payment;
    }

    protected function getPaymentFromPaymentIdForCustomerDetails($paymentId)
    {
        Payment\Entity::stripSignWithoutValidation($paymentId);

        $payment = $this->repo->payment->find($paymentId);

        if (empty($payment) === true)
        {
            return null;
        }

        return $payment;
    }

    protected function getRefundFromRefundIdForCustomerDetails($refundId)
    {
        Entity::stripSignWithoutValidation($refundId);

        $refund = $this->repo->refund->find($refundId);

        if (empty($refund) === true)
        {
            return null;
        }

        return $refund;
    }

    protected function getOrderFromOrderIdForCustomerDetails($orderId)
    {
        Entity::stripSignWithoutValidation($orderId);

        $order = null;

        try
        {
            $order = $this->repo->order->findOrFail($orderId);
        }
        catch (\Throwable $e)
        {
            $this->trace->info(
                TraceCode::ORDER_NOT_FOUND,
                [
                    'error' => $e->getMessage()
                ]);
        }

        if (empty($order) === true)
        {
            return null;
        }

        return $order;
    }

    protected function updateRefund($refund, $input)
    {
        $referenceNo = $input[RefundEntity::BANK_REFERENCE_NO] ?? "";

        $processor = $this->getNewProcessor($refund->merchant);

        if ((empty($refund->getReference1()) === true) and
            ($processor->isValidArn($referenceNo) === true))
        {
            $processor->updateReference1AndTriggerEventArnUpdated($refund, $referenceNo);
        }
    }

    public function updateProcessedAt(array $input)
    {
        if (isset($input[RefundConstants::DB_FETCH_LIMIT]) === true)
        {
            $limit = intval($input[RefundConstants::DB_FETCH_LIMIT]);
        }
        else
        {
            $limit = 5000;
        }

        if (isset($input['created_at']) === true)
        {
            $createdAt = $input['created_at'];
        }
        else
        {
            $createdAt = now()->subHour(6)->getTimestamp();
        }

        $start = microtime(true);

        $this->trace->info(
            TraceCode::REFUND_UPDATE_PROCESSED_AT_INITIATED,
            [
                'start_time'                    => $start,
                Entity::CREATED_AT              => $createdAt,
                RefundConstants::DB_FETCH_LIMIT => $limit
            ]);

        $successCount  = $this->repo->refund->updateProcessedAt($limit, $createdAt);

        $end = microtime(true);

        $processingTime = $end - $start;

        $this->trace->info(
            TraceCode::REFUND_UPDATE_PROCESSED_AT_SUMMARY,
            [
                'end_time'      => $end,
                'time_taken'    => $processingTime,
                'success_count' => $successCount
            ]
        );

        return [
                'success_count' => $successCount,
                'time_taken'    => $processingTime,
        ];
    }

    public function bulkUpdateRefundsReference1(array $input)
    {
        $updateFailures = [];

        if (empty($input[RefundConstants::REFUND_REFERENCE1]) === true)
        {
            return [
                'success_count'       => 0,
                'time_taken'          => 0,
                'api_failed_count'    => 0,
                'api_failures'        => $updateFailures,
            ];
        }

        $start = microtime(true);

        foreach ($input[RefundConstants::REFUND_REFERENCE1] as $refund)
        {
            if ((empty($refund[Refund\Entity::ID]) === true) or (empty($refund[Refund\Entity::REFERENCE1]) === true))
            {
                // Format error cases, Adding to failed entities
                $updateFailures[] = $refund;

                continue;
            }

            try
            {
                $internalId = $refund[Refund\Entity::ID];

                Entity::verifyIdAndSilentlyStripSign($internalId);

                $refundEntity = $this->repo->refund->findOrFail($internalId);

                if ($refund[Refund\Entity::REFERENCE1] === 'NA')
                {
                    $refund[Refund\Entity::REFERENCE1] = null;
                }

                $this->trace->info(
                    TraceCode::REFUND_UPDATE_REFERENCE1,
                    [
                        'refund_id'      => $refund[Refund\Entity::ID],
                        'old_reference1' => $refundEntity->getReference1(),
                        'new_reference1' => $refund[Refund\Entity::REFERENCE1],
                    ]
                );

                // Adding to failed entities if reference1 is not as expected and failed to update
                if (($refundEntity->getReference1() !== $refund[Refund\Entity::REFERENCE1]) and
                    ($this->repo->refund->updateRefundReference1($refund) !== 1))
                {
                    $updateFailures[] = $refund;
                }
            }
            catch (\Exception $exception)
            {
                $this->trace->traceException($exception);

                $updateFailures[] = $refund;
            }
        }

        $end = microtime(true);

        $processingTime = $end - $start;

        $failedCount = count($updateFailures);

        // Should be modified here if any new entities are created in future.
        $successCount = count($input[RefundConstants::REFUND_REFERENCE1]) - $failedCount;

        $response = [
            'success_count'    => $successCount,
            'api_failed_count' => $failedCount,
            'time_taken'       => $processingTime,
            'api_failures'     => $updateFailures,
        ];

        $this->trace->info(
            TraceCode::REFUND_UPDATE_REFERENCE1_SUMMARY,
            $response
        );

        return $response;
    }

    public function verifyScroogeRefundsBulk(array $input)
    {
        $gateways = [Payment\Gateway::UPI_MINDGATE, Payment\Gateway::UPI_ICICI];

        $limit = (isset($input[RefundConstants::DB_FETCH_LIMIT]) === true) ? intval($input[RefundConstants::DB_FETCH_LIMIT]) : 500;

        $offset = (isset($input['offset']) === true) ? intval($input['offset']) : 0;

        $from = $input['from'] ?? (now()->subHour(24)->getTimestamp());

        $to = $input['to'] ?? (now()->getTimestamp());

        $gateways = $input['gateways'] ?? $gateways;

        $merchantIds = $input['merchant_id'] ?? [];

        $status = $input['status'] ?? 'file_init';

        $scroogeRefunds = $input['refunds'] ?? [];

        $this->trace->info(
            TraceCode::REFUND_SCROOGE_VERIFY_INITIATED,
            [
                'from'                          => $from,
                'to'                            => $to,
                'gateways'                      => $gateways,
                'refunds'                       => $scroogeRefunds,
                RefundConstants::DB_FETCH_LIMIT => $limit,
            ]);

        if (empty($scroogeRefunds) === true)
        {
            $scroogeRefundsInput = [
                'query' => [
                    'gateway'       => $gateways,
                    'status'        => $status,
                    'created_at'    => [
                        'gte' => (string) $from,
                        'lte' => (string) $to
                    ]
                ],
                'count' => $limit,
                'skip'  => $offset,
            ];

            if (empty($merchantIds) === false)
            {
                $scroogeRefundsInput['query']['merchant_id'] = $merchantIds;
            }

            $response = $this->app['scrooge']->getRefunds($scroogeRefundsInput);

            if (isset($response['body']->data) === true)
            {
                $scroogeRefunds = json_decode(json_encode($response['body']->data), true);
            }
        }

        $failureRefunds = [];

        $total = $success = $failure = 0;

        if (empty($scroogeRefunds) === false)
        {
            foreach ($scroogeRefunds as $scroogeRefund)
            {
                $data = [
                    RefundEntity::ID        => $scroogeRefund[RefundEntity::ID],
                    RefundEntity::ATTEMPTS  => $scroogeRefund[RefundEntity::ATTEMPTS]
                ];

                $data['mode'] = Mode::LIVE;

                try
                {
                    BulkScroogeVerifyRefund::dispatch($data);

                    $success += 1;
                }
                catch (\Exception $exception)
                {
                    $failure +=1 ;

                    $failureRefunds[] = $scroogeRefund[RefundEntity::ID];

                    $this->trace->traceException($exception);
                }

                $total += 1;
            }
        }

        $traceData = [
            'total'             => $total,
            'success'           => $success,
            'failure'           => $failure,
            'failure_refunds'   => $failureRefunds,
        ];

        $this->trace->info(TraceCode::BULK_SCROOGE_REFUND_VERIFY_JOB_DISPATCHED, $traceData);

        return $traceData;
    }

    public function validateInputForVerifyRefundsInBulk(array $input)
    {
        $data = [
            'refund_data'     => [],
            'invalid_refunds' => [],
            'refund_count'    => 0
        ];

        foreach ($input['refund_data'] as $refundEntity)
        {
            $refundArray = explode (':', $refundEntity);

            $refundId = $refundArray[0];

            try
            {
                $internalId = $refundId;

                Refund\Entity::verifyIdAndSilentlyStripSign($internalId);

                $refund = $this->repo->refund->findOrFailPublic($internalId);

                $payment = $refund->payment;

                $attempts = 1;

                if (($payment->isUpi() === true) and (isset($refundArray[1]) === true))
                {
                    $attempts = (int)$refundArray[1];
                }

                $data['refund_count'] += $attempts;

                $data['refund_data'][] = [
                    'refund'               => $refund,
                    RefundEntity::ATTEMPTS => $attempts
                ];
            }
            catch (\Throwable $ex)
            {
                $data['invalid_refunds'][] = [
                    RefundEntity::ID       => $refundId,
                    'failure_message'      => 'Verify Refund Not Called. Error : ' . $ex->getMessage()
                ];
            }
        }

        return $data;
    }

    public function verifyRefundsInBulk(array $input)
    {
        $this->trace->info(TraceCode::BULK_REFUND_VERIFY_REQUEST, $input);

        $data = $this->validateInputForVerifyRefundsInBulk($input);

        $response = [
            'message'    => 'Request Processed Successfully',
            'result'     => []
        ];

        if ($data['refund_count'] > RefundConstants::MAX_REFUND_VERIFY_REQUESTS)
        {
            $response['message'] = 'Maximum refunds that can be verified at once is ' . RefundConstants::MAX_REFUND_VERIFY_REQUESTS;

            return $response;
        }

        $fileData = [];

        $refundEntities = $data['refund_data'];

        if (empty($refundEntities) === false)
        {
            foreach ($refundEntities as $refundEntity)
            {
                $merchant = $refundEntity['refund']->merchant;

                $results = $this->getNewProcessor($merchant)->verifyScroogeRefundWithAttempts($refundEntity['refund'],
                                                                                              $refundEntity[RefundEntity::ATTEMPTS],
                                                                                              true);

                array_push($fileData, ...$results);
            }
        }

        if (empty($data['invalid_refunds']) === false)
        {
            foreach ($data['invalid_refunds'] as $invalidRefund)
            {
                $fileData[] = [
                    'refund_id'         => $invalidRefund[RefundEntity::ID],
                    'attempt_number'    => 'NA',
                    'success'           => 'NA',
                    'payment_id'        => 'NA',
                    'verify_response'   => $invalidRefund['failure_message']
                ];
            }
        }

        $response['result'] = $fileData;

        return $response;
    }

    protected function addParamsForDashboard(array &$refundArray)
    {
        if (isset($refundArray[Entity::STATUS]) === true)
        {
            $refundArray[Entity::STATUS] = $this->getPublicStatusValueFromStatus($refundArray);
        }

        try
        {
            $refundId = $refundArray[Entity::ID];

            Entity::verifyIdAndStripSign($refundId);

            $refund = $this->repo->refund->find($refundId);

            // Adds Speed change timestamp when refunds speed transitioned from instant to normal
            $this->addSpeedChangeTime($refundArray, $refund);

            // Adds Processed At timestamp based on refund status and merchant type
            $this->addProcessedAtTime($refundArray, $refund);

            // Adding failed refund attributes only when refund status shown to merchant is failed
            $this->addFailedRefundAttributes($refundArray, $refund);
        }
        catch(\Throwable $exception)
        {
            $this->trace->traceException(
                $exception,
                Trace::WARNING,
                TraceCode::REFUND_ADD_DASHBOARD_PARAMS_FAILED,
                [
                    'refund_id' => $refundId,
                ]
            );
        }
    }

    protected function addProcessedAtTime(array &$refundArray, Entity $refund)
    {
        $refundArray[Entity::PROCESSED_AT] = NULL;

        // Adding actual timestamps only when status is processed
        if ((isset($refundArray[Entity::STATUS]) === false) or ($refundArray[Entity::STATUS] !== Status::PROCESSED))
        {
            return;
        }

        switch ($refund->getSpeedDecisioned())
        {
            // For optimum refunds :
            // i) if speed_processed is normal, it can mean that the refund has failed instant attempt and now
            //    its in the normal refund flow making attempt to gateway. So for
            //      public Status Merchants : processed_at is actual processed_at value
            //      Other Merchants : processed_at is created_at
            // ii) if speed_processed is not normal, it can mean that the refund is still under processing
            //     or was processed instantly. In either case we show actual processed at to merchant
            case Speed::OPTIMUM :
                if ($refund->getSpeedProcessed() === Speed::NORMAL)
                {
                    $speedChangeTime = $refundArray[RefundConstants::SPEED_CHANGE_TIME] ?? NULL;

                    $refundArray[Entity::PROCESSED_AT] =
                        (RefundCore::isRefundsPublicStatusMerchant($this->merchant->getId()) === true) ?
                            $this->getProcessedAtForPublicStatusMerchant($refund) :
                            $speedChangeTime;
                }
                else
                {
                    $refundArray[Entity::PROCESSED_AT] = $refund->getProcessedAt();
                }

                break;

            // For instant refunds :
            //  All Merchants : processed_at is actual processed_at value
            case Speed::INSTANT :
                $refundArray[Entity::PROCESSED_AT] = $refund->getProcessedAt();

                break;

            // For normal refunds :
            //  public Status Merchants : processed_at is actual processed_at value
            //  Other Merchants : processed_at is created_at
            case Speed::NORMAL :
                $refundArray[Entity::PROCESSED_AT] =
                    (RefundCore::isRefundsPublicStatusMerchant($this->merchant->getId()) === true) ?
                        $this->getProcessedAtForPublicStatusMerchant($refund) :
                        $refund->getCreatedAt();

                break;
        }
    }

    protected function getProcessedAtForPublicStatusMerchant(Entity $refund)
    {
        $processedAt = $refund->getProcessedAt();

        if (RefundCore::fetchPublicStatusFromScrooge($this->merchant->getId()) === true)
        {
            $publicProcessedAt = $refund->getCreatedAt() + RefundConstants::SCROOGE_PUBLIC_STATUS_TO_PROCESSED_TIME;

            if (($processedAt === NULL) or
                ($processedAt > $publicProcessedAt))
            {
                $processedAt = $publicProcessedAt;
            }
        }

        return $processedAt;
    }

    protected function addFailedRefundAttributes(array &$refundArray, Entity $refund)
    {
        // Adding failed at only when status is failed
        if ((isset($refundArray[Entity::STATUS]) === true) and ($refundArray[Entity::STATUS] === Status::FAILED))
        {
            $reversals = $this->fetchReversalOfRefund($refund->getId());

            $failedAt = null;

            foreach ($reversals as $reversal)
            {
                if ($reversal->getAmount() === $refund->getAmount())
                {
                    $failedAt = $reversal->getCreatedAt();

                    break;
                }
            }

            $refundArray[RefundConstants::FAILED_AT] = $failedAt;

            if (($refund->getSpeedDecisioned() === RefundSpeed::INSTANT) and
                ($refund->wasGatewayRefundNotSupportedAtCreation($refund->getId()) === true))
            {
                $refundArray[RefundConstants::GATEWAY_REFUND_SUPPORT] = false;
            }
        }
    }

    protected function addSpeedChangeTime(array &$refundArray, Entity $refund)
    {
        if (($refund->getSpeedDecisioned() === RefundSpeed::OPTIMUM) and
            ($refund->getSpeedProcessed() === RefundSpeed::NORMAL))
        {
            $queryParams = [
                RefundConstants::SPEED_CHANGE_TIME => 1,
            ];

            $scroogeResponse = $this->app['scrooge']->getPublicRefund($refundArray[Entity::ID], $queryParams);

            $scroogeResponseCode = $scroogeResponse[RefundConstants::RESPONSE_CODE];

            if ((in_array($scroogeResponseCode, [200, 201, 204], true) === false) or
                (isset($scroogeResponse[RefundConstants::RESPONSE_BODY]) === false) or
                (isset($scroogeResponse[RefundConstants::RESPONSE_BODY][RefundConstants::SPEED_CHANGE_TIME]) === false))
            {
                throw new Exception\RuntimeException('Unexpected response received from scrooge service');
            }

            $speedChangeTime = $scroogeResponse[RefundConstants::RESPONSE_BODY][RefundConstants::SPEED_CHANGE_TIME];

            if ($speedChangeTime !== null)
            {
                $refundArray[RefundConstants::SPEED_CHANGE_TIME] = $speedChangeTime;
            }
        }
    }

    /**
     * Sends flag to merchant config route for merchant dashboard
     *
     * @param string $merchantId
     * @return bool
     */
    public function getRefundStatusFilterFlagForMerchantDashboard(string $merchantId) : bool
    {
        $displayRefundPublicStatus = Payment\Refund\Core::fetchPublicStatusFromScrooge($merchantId);
        $refundPublicStatusFeatureEnabled =
            $this->merchant->isFeatureEnabled(Feature\Constants::SHOW_REFUND_PUBLIC_STATUS);

        if (($displayRefundPublicStatus === true) or
            ($refundPublicStatusFeatureEnabled === true))
        {
            return false;
        }

        return true;
    }

    /**
     * @param string $refundId
     * @return Base\PublicCollection
     * @throws Exception\BadRequestValidationFailureException
     * @throws Exception\InvalidArgumentException
     */
    public function fetchReversalOfRefund(string $refundId)
    {
        $merchantId = $this->merchant->getId();

        $input = [
            ReversalEntity::ENTITY_ID => $refundId,
            ReversalEntity::ENTITY_TYPE => Constants\Entity::REFUND
        ];

        return $this->repo->reversal->fetch($input, $merchantId);
    }

    /**
     * @param array $input
     * @return array
     */
    public function setUnprocessedRefundsConfig(array $input) : array
    {
        (new Validator)->validateInput('set_unprocessed_refunds_config', $input);

        $setConfigInput[ConfigKey::GATEWAY_UNPROCESSED_REFUNDS] = $input[RefundConstants::REFUND_IDS];

        return (new AdminService)->setConfigKeys($setConfigInput);
    }

    public function cancelRefundsBatch(string $batchId)
    {
        $batch = $this->fetchBatchById($batchId);

        if ($batch === [])
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_ID,
                null,
                [
                    'batch_id'      => $batchId,
                ]
            );
        }

        return $this->core->cancelRefundsBatch($batch);
    }

    protected function fetchBatchById(string $batchId): array
    {
        $batch = [];

        if ($this->auth->isAdminAuth() === true)
        {
            $batch = (new Batch\Service())->fetchBatchById($batchId);
        }
        else
        {
            $batch = (new Batch\Service())->getBatchById($batchId, $this->merchant);

            if (($batch !== []) and
                ((array_key_exists(Batch\ResponseEntity::BATCH_TYPE_ID, $batch) === false) or
                 ($batch[Batch\ResponseEntity::BATCH_TYPE_ID] !== Batch\Type::REFUND)))
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_BATCH_FILE_INVALID_TYPE,
                    null,
                    [
                        'batch_id' => $batchId,
                    ]
                );
            }
        }

        return $batch;
    }

    private function populateMerchantSupportDetails(array &$return)
    {
        if (empty($return[RefundConstants::PAYMENTS]) === true)
        {
            return;
        }

        $merchantId = $return[RefundConstants::PAYMENTS][0][RefundConstants::PAYMENT][RefundConstants::MERCHANT_ID];

        try
        {
            $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

            $merchantSupportDetails = (new MerchantEmailCore)->fetchEmailsByType($merchant, MerchantEmailType::SUPPORT);

            $return[RefundConstants::BUSINESS_SUPPORT_DETAILS] = $merchantSupportDetails->toArrayPublicCustomer();
        }
        catch (\Throwable $e)
        {
            if ($e->getCode() !== ErrorCode::BAD_REQUEST_MERCHANT_EMAIL_DOES_NOT_EXIST)
            {
                $this->trace->traceException($e);
            }
        }
    }
}
