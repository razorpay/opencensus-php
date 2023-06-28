<?php

namespace RZP\Models\Payment\Refund;

use Config;
use ApiResponse;
use Carbon\Carbon;

use Ramsey\Uuid\Uuid;
use RZP\Exception;
use RZP\Constants;
use RZP\Models\Base;
use RZP\Error\Error;
use RZP\Models\Batch;
use RZP\Constants\Mode;
use RZP\Models\Currency\Currency;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Models\Payment;
use RZP\Models\Feature;
use RZP\Error\ErrorCode;
use RZP\Base\ConnectionType;
use RZP\Services\Dcs\Features\Type;
use RZP\Services\Ledger as LedgerService;

use RZP\Models\Reversal;

use RZP\Models\Reversal\Constants as ReversalConstants;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Base\Repository;
use RZP\Models\Bank\IFSC;
use RZP\Models\Settlement;
use RZP\Http\RequestHeader;
use RZP\Constants\Timezone;
use RZP\Models\BankTransfer;
use RZP\Base\RuntimeManager;
use RZP\Models\Card\IIN\IIN;
use RZP\Models\Bank\BankCodes;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Refund;
use RZP\Models\Admin\ConfigKey;
use RZP\Mail\Base\OrgWiseConfig;
use RZP\Jobs\ScroogeRefundUpdate;
use RZP\Models\Base\UniqueIdEntity;
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
use RZP\Services\Segment\EventCode as SegmentEvent;
use RZP\Models\Payment\Refund\Speed as RefundSpeed;
use RZP\Models\Payment\Refund\Entity as RefundEntity;
use RZP\Models\Payment\Refund\Helpers as RefundHelpers;
use RZP\Services\Segment\Constants as SegmentConstants;
use RZP\Models\Merchant\Email\Type as MerchantEmailType;
use RZP\Models\Merchant\Email\Core as MerchantEmailCore;
use RZP\Models\Payment\Refund\Constants as RefundConstants;
use RZP\Models\Payment\Processor\Processor as PaymentProcessor;
use RZP\Models\Terminal\Entity as TerminalEntity;
use RZP\Models\Ledger\Constants as LedgerConstants;

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
        catch (\Throwable $e)
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
        return $this->app['scrooge']->createNewRefundV2($input);
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
        $this->trace->info(TraceCode::API_REFUNDS_FETCH_REQUEST, [
            'route_name'  => $this->app['api.route']->getCurrentRouteName(),
            'extra_trace' => $this->app['basicauth']->getAuthType(),
            'input'       => $input,
        ]);

        return $this->app['scrooge']->refundsFetchById($id, $input);
    }

    public function setSettlementDetailsForOptimizer($refundArray)
    {
        try {
            if (isset($refundArray['transaction']) === true)
            {
                $fetchInput = [
                    'transaction_id' => str_replace("txn_", "", $refundArray['transaction']['id']),
                ];

                $settlementResponse = app('settlements_merchant_dashboard')->getSettlementForTransaction($fetchInput);

                $settlement = $settlementResponse['settlement'];

                unset($refundArray['transaction']['settlement_id']);
                unset($refundArray['transaction']['settlement']);

                if ($settlement != null) {

                    $setl = new Settlement\Entity($settlement);

                    $setl->setPublicAttributeForOptimiser($settlement);

                    $refundArray['transaction']['settlement'] = $setl->toArrayPublic();

                    $refundArray['transaction']['settlement_id'] = $setl->getId();
                }
            }

        } catch (\Throwable $e) {
            $this->trace->traceException(
                $e,
                Trace::WARNING,
                TraceCode::GET_SETTLEMENT_DETAILS_FOR_REFUND_FAILED,
                [
                    'transaction_id' => $refundArray['transaction']['id'],
                ]);
        } finally {
            return $refundArray;
        }
    }

    /***
     * @param array $apiRefundsArray api db refunds array
     * @param array $scroogeRefundsArray scrooge db refunds array
     * @param array $extraTrace additional trace info to log
     * @return void
     */
    public function compareRefundsAndLogDifference(array $apiRefundsArray, array $scroogeRefundsArray, array $extraTrace = [])
    {
        // Compare scrooge and api response
        $inconsistentParams = [];

        try
        {
            if (count($apiRefundsArray) !== count($scroogeRefundsArray))
            {
                $inconsistentParams['api_refunds_collection_length'] = count($apiRefundsArray);
                $inconsistentParams['scrooge_refunds_collection_length'] = count($scroogeRefundsArray);
            }

            foreach ($apiRefundsArray as $apiRefundArray)
            {
                $idx = 0;

                foreach ($scroogeRefundsArray as $scroogeRefundArray)
                {
                    $scroogeRefundId = $scroogeRefundArray[RefundEntity::ID] ?? '';

                    if ($apiRefundArray[RefundEntity::ID] === $scroogeRefundId)
                    {
                        $diff = $this->differenceKeysOfRefunds($apiRefundArray, $scroogeRefundArray);

                        if (empty($diff) === false)
                        {
                            $inconsistentParams[$apiRefundArray[RefundEntity::ID]] = $diff;
                        }

                        break;
                    }

                    $idx += 1;
                }

                // null value here means that the refund is not present in scrooge but is present in the API monolith
                if ($idx === count($scroogeRefundsArray))
                {
                    $inconsistentParams[$apiRefundArray[RefundEntity::ID]] = null;
                }
            }

            if (empty($inconsistentParams) === false)
            {
                $this->trace->info(TraceCode::SCROOGE_AND_API_REFUNDS_INCONSISTENCY, [
                    'diff'        => $inconsistentParams,
                    'route_name'  => $this->app['api.route']->getCurrentRouteName(),
                    'extra_trace' => $extraTrace,
                ]);
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->info(
                TraceCode::COMPARE_REFUNDS_ERROR,
                [
                    'api' => $apiRefundsArray,
                    'scrooge' => $scroogeRefundsArray,
                ]);
        }
    }

    public function differenceKeysOfRefunds($apiRefundArray, $scroogeRefundArray) : array
    {
        $responseDiff = [];

        $apiFieldsNotPopulated = ["attempts", "last_attempted_at"];
        $timestampFields = ["updated_at", "processed_at"];

        foreach ($apiRefundArray as $key => $value)
        {
            if ($key === RefundEntity::NOTES)
            {
                if ($scroogeRefundArray[$key] != $value)
                {
                    $responseDiff[$key]["scrooge"] = $scroogeRefundArray[$key];
                    $responseDiff[$key]["api"] = $value;
                }

                continue;
            }

            if ($key === RefundEntity::ACQUIRER_DATA)
            {
                // casting this to array as acquirer_data is a spine dictionary object, compare would fail
                $value = $value->toArray();
            }

            if (is_array($value) === true)
            {
                if ($scroogeRefundArray[$key] != $value)
                {
                    $responseDiff[$key]["scrooge"] = $scroogeRefundArray[$key];
                    $responseDiff[$key]["api"] = $value;
                }

                continue;
            }

            if (in_array($key, $apiFieldsNotPopulated))
            {
                continue;
            }

            if (in_array($key, $timestampFields))
            {
                $diff = $scroogeRefundArray[$key] - $value ;
                if (abs($diff) <= 5)
                {
                    continue;
                }
            }

            if ((isset($scroogeRefundArray[$key]) === true) and ($scroogeRefundArray[$key] !== $value))
            {
                $responseDiff[$key]["scrooge"] = $scroogeRefundArray[$key];
                $responseDiff[$key]["api"] = $value;
            }
        }

        return $responseDiff;
    }

    public function compareThrowableAndLogDifference(\Throwable $apiException, $scroogeException, array $extraTrace = [])
    {
        try
        {
            if ($scroogeException === null)
            {
                $this->trace->info(TraceCode::SCROOGE_AND_API_REFUNDS_EXCEPTION_INCONSISTENCY, [
                    'api_error_code'        => $apiException->getCode(),
                    'scrooge_error_code'    => null,
                    'api_error_message'     => $apiException->getMessage(),
                    'scrooge_error_message' => null,
                    'route_name'            => $this->app['api.route']->getCurrentRouteName(),
                    'extra_trace'           => $extraTrace,
                ]);

                return;
            }

            if (($apiException->getCode() !== $scroogeException->getCode()) or
                ($apiException->getMessage() !== $scroogeException->getMessage()))
            {
                $this->trace->info(TraceCode::SCROOGE_AND_API_REFUNDS_EXCEPTION_INCONSISTENCY, [
                    'api_error_code'        => $apiException->getCode(),
                    'scrooge_error_code'    => $scroogeException->getCode(),
                    'api_error_message'     => $apiException->getMessage(),
                    'scrooge_error_message' => $scroogeException->getMessage(),
                    'route_name'            => $this->app['api.route']->getCurrentRouteName(),
                    'extra_trace'           => $extraTrace,
                ]);
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->info(TraceCode::COMPARE_REFUNDS_ERROR, ['message' => 'error in throwable comparison']);
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

                        $data[RefundConstants::PAYMENT_RAW_AMOUNT] = $payment->getAmount();

                        $data[RefundConstants::PAYMENT_RAW_CURRENCY] = $payment->getCurrency();

                        $data[RefundConstants::AMOUNT_UNREFUNDED]=$payment->getAmountUnrefunded();

                        $data[RefundConstants::BASE_AMOUNT_UNREFUNDED]=$payment->getBaseAmountUnrefunded();

                        $data[RefundConstants::CURRENCY_CONVERSION_RATE] = $payment->getCurrencyConversionRate();

                        $data[RefundConstants::IS_UPI_OTM] = $payment->isUpiOtm();

                        $data[RefundConstants::IS_DCC] = $payment->isDCC();

                        $data[RefundConstants::GATEWAY_AMOUNT] = $payment->getGatewayAmount();

                        $data[RefundConstants::DISCOUNTED_AMOUNT] = $payment->getDiscountedAmountIfApplicable();

                        $data[RefundConstants::IS_UPI_AND_AMOUNT_MISMATCHED] = $payment->isUpiAndAmountMismatched();

                        $data[RefundConstants::IS_HDFC_VAS_DS_CUSTOMER_FEE_BEARER] = $payment->isHdfcVasDSCustomerFeeBearerSurcharge();

                        $data[RefundConstants::DISCOUNT_RATIO] = $payment->getDiscountRatioIfApplicable();

                        $data[RefundConstants::MIN_CURRENCY_AMOUNT] = Currency::getMinAmount($payment->getCurrency());

                        $data[RefundConstants::PAYMENT_RAW_CURRENCY_DENOMINATION] = Currency::getDenomination($payment->getCurrency());

                        $data[RefundConstants::GATEWAY_CURRENCY_DENOMINATION] = Currency::getDenomination($payment->getGatewayCurrency());
                    }

                    $response[RefundConstants::ENTITIES][Constants\Entity::PAYMENT][RefundConstants::DATA] = $data;

                    $response[RefundConstants::ENTITIES][Constants\Entity::PAYMENT][RefundConstants::ERROR] = $paymentError;
                }

                if (isset($input[RefundConstants::ENTITIES]) === true)
                {
                    foreach ($input[RefundConstants::ENTITIES] as $key)
                    {
                        if ($key === Constants\Entity::PAYMENT)
                        {
                            continue;
                        }

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
                                catch (\Throwable $ex)
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

                                    if (method_exists($this->repo->$entity, 'findByPaymentIdAndActionOrFail') === true) {
                                        try {
                                            $data = $this->repo
                                                ->$entity
                                                ->findByPaymentIdAndActionOrFail($id, $action)
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
                            else if ($key === Constants\Entity::TERMINAL)
                            {
                                $entity = $payment->terminal;
                                if (empty($entity) === false)
                                {
                                    $entity = $entity->makeVisible([TerminalEntity::GATEWAY_SECURE_SECRET, TerminalEntity::GATEWAY_SECURE_SECRET2, TerminalEntity::GATEWAY_TERMINAL_PASSWORD]);
                                    $data = $entity->toArray();
                                }
                            }
                            else if ($key === Constants\Entity::TOKEN)
                            {
                                $data = $payment->getGlobalOrLocalTokenEntity();
                            }
                            else if ($key === Constants\Entity::TOKEN_CARD)
                            {
                                $token = $payment->getGlobalOrLocalTokenEntity();
                                if (empty($token) === false)
                                {
                                    $data = $token->card;
                                }
                            }
                            else if ($key === Constants\Entity::CARD)
                            {
                                $card = $payment->card;
                                if (empty($card) === false)
                                {
                                    $data = $card->toArrayRefund();
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
                            catch(\Throwable $ex)
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
            catch (\Throwable $ex)
            {
                $skippedPayments[] = [
                    $id =>
                        [
                            RefundConstants::CODE => $ex->getCode(),
                            RefundConstants::MESSAGE => $ex->getMessage()
                        ]
                ];
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

    /**
     * @param Entity $refund
     * @return bool
     */
    public function refundsPublicStatusMerchant(Entity $refund): bool
    {
        $refundPublicStatusFeature = $refund->merchant->isFeatureEnabled(Feature\Constants::SHOW_REFUND_PUBLIC_STATUS);
        $refundPendingStatusFeature = $refund->merchant->isFeatureEnabled(Feature\Constants::REFUND_PENDING_STATUS);

        return $refundPublicStatusFeature || $refundPendingStatusFeature;
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

    protected function getPaymentExtraDataOrgFeatures(Payment\Entity $payment)
    {
        return $payment->merchant->org->getEnabledFeatures();
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
        if (empty($payment->card) === true)
        {
            return null;
        }

        $cardEntityArray = $payment->card->toArrayRefund();

        if (isset($cardEntityArray[RefundConstants::IIN]) === false)
        {
            return null;
        }

        return IIN::isIinPrepaid($cardEntityArray[RefundConstants::IIN]);
    }

    protected function getPaymentExtraDataCardHasSupportedIssuer(Payment\Entity $payment)
    {
        if (empty($payment->card) === true)
        {
            return null;
        }

        $cardIssuer = $payment->card->getIssuer();

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
        $this->trace->info(TraceCode::API_REFUNDS_FETCH_REQUEST, [
            'route_name'  => $this->app['api.route']->getCurrentRouteName(),
            'extra_trace' => $this->app['basicauth']->getAuthType(),
            'input'       => $input,
        ]);

        if (isset($input['notes']) === false)
        {
            return $this->app['scrooge']->refundsFetchMultiple($input);
        }

        $experimentVariable = UniqueIdEntity::generateUniqueId();
        // shadow mode experiment
        $variant = $this->app->razorx->getTreatment($experimentVariable,
            RefundConstants::RAZORX_KEY_REFUND_FETCH_MULTIPLE_FROM_SCROOGE,
            $this->mode
        );

        if ($variant === RefundConstants::RAZORX_VARIANT_ON)
        {
            return $this->fetchMultipleShadowMode($input);
        }

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

    public function fetchMultipleShadowMode($input)
    {
        $scroogeRefundsArray = [];
        $scroogeException = null;

        try
        {
            $scroogeRefundsArray = $this->app['scrooge']->refundsFetchMultiple($input);
        }
        catch (\Throwable $ex)
        {
            $scroogeException = $ex;

            $this->trace->info(TraceCode::SCROOGE_REFUNDS_FETCH_EXCEPTION, [
                'error_code'    => $ex->getCode(),
                'error_message' => $ex->getMessage(),
                'route_name'    => $this->app['api.route']->getCurrentRouteName(),
                'extra_trace'   => $this->app['basicauth']->getAuthType(),
            ]);
        }

        try
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

            $this->compareRefundsAndLogDifference($refundsArray['items'], $scroogeRefundsArray['items'] ?? []);

            return $refundsArray;
        }
        catch (\Throwable $apiException)
        {
            $extraTraceData = [
                'input'      => $input,
            ];

            $this->compareThrowableAndLogDifference($apiException, $scroogeException, $extraTraceData);

            throw $apiException;
        }
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

            if($merchant->isFeatureEnabled(FeatureConstants::PG_LEDGER_REVERSE_SHADOW))
            {
                $refundId = $refundInput[RefundEntity::ID];

                $txn = $this->repo->transaction->findByEntityId($refundId, $merchant);

                if($txn !== null)
                {
                    $this->trace->info(TraceCode::SCROOGE_REFUND_TRANSACTION_ALREADY_EXISTS, $refundInput);
                    return  RefundHelpers::getScroogeRefundTransactionCreateResponse(null, false, $txn->getId());
                }
            }

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

    public function buildVirtualRefundEntity(Payment\Entity $payment, array &$input, string $refundId = null)
    {
        $refund = (new RefundEntity())->forceFill($input);

        if (empty($refund[Refund\Entity::STATUS]) === true) {
            $refund[Refund\Entity::STATUS] = Status::CREATED;
        }

        if (empty($refund[Refund\Entity::LAST_ATTEMPTED_AT]) === true) {
            $refund[Refund\Entity::LAST_ATTEMPTED_AT] = $input[Refund\Entity::CREATED_AT] ?? null;
        }

        if (empty($refund[Refund\Entity::TRANSACTION_ID]) === true) {
            $refund[Refund\Entity::TRANSACTION_ID] = null;
        }

        $refund[Refund\Entity::IS_SCROOGE] = true;

        $refund->payment()->associate($payment);

        $merchant = $payment->merchant;

        $refund->merchant()->associate($merchant);

        return $refund;
    }

    public function makeGatewayRefundCall(string $refundId, array $input)
    {

        $payment = $this->repo->payment->findOrFail($input[RefundConstants::PAYMENT_ID]);

        $merchant = $payment->merchant;

        $refund = $this->buildVirtualRefundEntity($payment, $input, $refundId);

        return $this->getNewProcessor($merchant)->scroogeGatewayRefund($refund, $input);
    }

    public function makeGatewayVerifyRefundCall(string $refundId, array $input)
    {
        $payment = $this->repo->payment->findOrFail($input[RefundConstants::PAYMENT_ID]);

        $merchant = $payment->merchant;

        $refund = $this->buildVirtualRefundEntity($payment, $input, $refundId);

        return $this->getNewProcessor($merchant)->scroogeGatewayVerifyRefund($refund, $input);
    }

    public function makeScroogeVerifyRefundCall(string $refundId, array $input)
    {
        $payment = $this->repo->payment->findOrFail($input[RefundConstants::PAYMENT_ID]);

        $merchant = $payment->merchant;

        $refund = $this->buildVirtualRefundEntity($payment, $input, $refundId);

        return $this->getNewProcessor($merchant)->scroogeVerifyRefund($refund, $input);
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

                    if (($transaction === null) and
                        ($refundWithoutTxn->merchant->isFeatureEnabled(FeatureConstants::PG_LEDGER_REVERSE_SHADOW) === false))
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

    public function updateRefundInternal($refundId, array $input)
    {
        return $this->mutex->acquireAndRelease($refundId,
            function() use ($refundId, $input)
            {
                $refund = $this->repo->refund->findOrFail($refundId);

                // this supports updating notes only. Check $editRules in validator
                $refund->edit($input);

                $this->repo->saveOrFail($refund);

                return $refund;
            },
            20,
            ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS);
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

                                $skipMerchantWebhooks = $input['skip_merchant_webhooks'] ?? false;

                                if ((($refund->merchant->isFeatureRefundPublicStatusOrPendingStatusEnabled() === true) or
                                    ($refund->getSpeedProcessed() !== RefundSpeed::NORMAL)) and $skipMerchantWebhooks === false)
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

                                $skipMerchantWebhooks = $input['skip_merchant_webhooks'] ?? false;

                                if ($skipMerchantWebhooks === true)
                                {
                                    break;
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

                                    $refund->setSettledBy($refund->payment->getSettledBy());
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

    /**
     * @throws \Throwable
     */
    public function createReversalForVirtualRefund(array $input)
    {
        (new RefundEntity())->getValidator()->validateInput('create_reversal', $input);

        $this->trace->info(
            TraceCode::VIRTUAL_REFUND_REVERSAL_REQUEST,
            [
                RefundConstants::INPUT  => $input,
            ]);

        $refundId = $input['refund_id'];

        $merchantId = $input['merchant_id'];

        $merchant = $this->repo->merchant->find($merchantId);

        if ($merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW) === false)
        {
            $this->trace->info(TraceCode::REVERSAL_CREATION_FOR_VIRTUAL_REFUND_NOT_APPLICABLE, [
                RefundConstants::REFUND_ID      => $refundId,
            ]);

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_REFUND_REVERSAL_NOT_APPLICABLE,
                null,
                [
                    RefundConstants::REFUND_ID      => $refundId,
                    ReversalConstants::REASON       => "Merchant not onboarded on pg_ledger_reverse_shadow"
                ]);
        }

        $reversal = $this->repo->reversal->findReversalByRefundId($refundId);

        // We will create a new reversal only if there are no existing reversals for the given refund Id
        if ($reversal !== null)
        {
            $this->trace->info(TraceCode::REVERSAL_CREATION_FOR_VIRTUAL_REFUND_DUPLICATE_REQUEST, [
                RefundConstants::REFUND_ID      => $refundId,
                ReversalConstants::REVERSAL_ID  => $reversal->getId(),
            ]);

            return [
                RefundConstants::REFUND_ID      => $refundId,
                ReversalConstants::REVERSAL_ID  => $reversal->getId(),
                ReversalConstants::IS_DUPLICATE => true
            ];
        }

        $payment = $this->repo->payment->findOrFail($input[RefundConstants::PAYMENT_ID]);

        $refund = $this->buildVirtualRefundEntity($payment, $input, $input[RefundConstants::REFUND_ID]);
        $refund[RefundEntity::ID] = $refundId;

        $response = [];

        try
        {
            $reversal = (new Reversal\Core)->reverseForRefund($refund, $input[RefundConstants::FEE_ONLY_REVERSAL], true);

            $response[RefundConstants::REFUND_ID]   = $refundId;
            $response[ReversalConstants::REVERSAL_ID] = $reversal->getId();
            $response[ReversalConstants::IS_DUPLICATE] = false;
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException($ex, null, null, [
                RefundConstants::REFUND_ID  => $input[RefundConstants::REFUND_ID],
                RefundConstants::MESSAGE    => $ex->getMessage()
            ]);

            throw $ex;
        }
        return $response;
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

            $payment = null;

            try
            {
                $payment = $this->repo->payment->findOrFail($paymentId);
            }
            catch (\Throwable $exception){}

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
        $payment = $this->repo->payment->fetch([Payment\Entity::NOTES => $id], null, ConnectionType::DATA_WAREHOUSE_MERCHANT);

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

            try
            {
                $payment = $this->repo->payment->findOrFail($payment->toArray()[0][Payment\Entity::ID]);
            }
            catch (\Throwable $exception){}

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

        $dcs = $this->app['dcs'];

        $featureEntities = $dcs->fetchByFeatureName(Feature\Constants::IRCTC_REPORT, Type::MERCHANT, $this->mode);

        if(empty($featureEntities))
        {

            $featureEntities = $this->repo->feature->findMerchantsHavingFeatures([Feature\Constants::IRCTC_REPORT]);

            $irctcMerchantIds = $featureEntities->pluck(Feature\Entity::ENTITY_ID)->toArray();

        }
        else
        {

            $irctcMerchantIds = array_column($featureEntities,Feature\Entity::ENTITY_ID);
        }

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

        $payment = null;

        try
        {
            $payment = $this->repo->payment->findOrFail($paymentId);
        }
        catch (\Throwable $exception){}

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

        $skipArnEvent = $input['skip_arn_updated_event'] ?? false;

        $processor = $this->getNewProcessor($refund->merchant);

        if ((empty($refund->getReference1()) === true) and
            ($processor->isValidArn($referenceNo) === true))
        {
            if ($skipArnEvent === true)
            {
                $processor->updateReference1AndTriggerEventArnUpdated($refund, $referenceNo, false);
            }
            else
            {
                $processor->updateReference1AndTriggerEventArnUpdated($refund, $referenceNo);
            }
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
                if ($refundEntity->getReference1() !== $refund[Refund\Entity::REFERENCE1]) {
                    if ($this->repo->refund->updateRefundReference1($refund) !== 1)
                    {
                        $updateFailures[] = $refund;
                    }
                    // To be deprecated later
                    // else
                    // {
                    //     // reload refund entity for webhook
                    //     $refundEntity = $this->repo->refund->findOrFail($internalId);

                    //     // trigger arn updated webhook
                    //     $this->getNewProcessor($refundEntity->merchant)->eventRefundArnUpdated($refundEntity);
                    // }
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

                    $isRefundsPublicStatusMerchant = $this->refundsPublicStatusMerchant($refund);

                    $refundArray[Entity::PROCESSED_AT] =
                        ($isRefundsPublicStatusMerchant === true) ?
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
                $isRefundsPublicStatusMerchant = $this->refundsPublicStatusMerchant($refund);
                $refundArray[Entity::PROCESSED_AT] =
                    ($isRefundsPublicStatusMerchant === true) ?
                        $this->getProcessedAtForPublicStatusMerchant($refund) :
                        $refund->getCreatedAt();

                break;
        }
    }

    protected function getProcessedAtForPublicStatusMerchant(Entity $refund)
    {
        $processedAt = $refund->getProcessedAt();

        $fetchPublicStatusFromScrooge =  $this->merchant->isFeatureEnabled(Feature\Constants::SHOW_REFUND_PUBLIC_STATUS);

        if ($fetchPublicStatusFromScrooge === true)
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
        $refundPublicStatusFeatureEnabled =
            $this->merchant->isFeatureEnabled(Feature\Constants::SHOW_REFUND_PUBLIC_STATUS);

        if ($refundPublicStatusFeatureEnabled === true)
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

    public function scroogeBackWriteRefund($input): array
    {
        $refund = (new RefundEntity())->forceFill($input);

        $this->repo->saveOrFail($refund);

        return $refund->toArray();
    }

    /***
     * Sample Request Body :
     * {
     * 	"public_entities": [
     *         {
     *             "entity_id": "JR4h9ibYDKjbel",
     *             "entity_type": "payment",
     *             "expand": []
     *         },
     *         {
     *             "entity_id": "JR4i2pIaEqdnbA",
     *             "entity_type": "refund",
     *             "expand": ["transaction.settlement"]
     *         }
     *     ]
     *  "custom_public_entities": [
     *         {
     *             "entity_id": "JR4h9ibYDKjbel",
     *             "entity_type": "optimizer_settlement",
     *             "transaction_id": "JR4h9ibYDKjbel"
     *         }
     *     ]
     * }
     *
     * Sample Response Body :
     * {
     *     "JR4h9ibYDKjbel": {
     *         "data": {
     *             "id": "pay_JR4h9ibYDKjbel",
     *             "entity": "payment",
     *             .
     *             .
     *             .
     *         },
     *         "error": null
     *     },
     *     "JR4i2pIaEqdnbA": {
     *         "data": {
     *             "id": "rfnd_JR4i2pIaEqdnbA",
     *             "entity": "refund",
     *             .
     *             .
     *             .
     *         },
     *         "error": null
     *     }
     * }
     *
     * @param $input
     * @return array
     */
    public function scroogeFetchPublicEntities($input): array
    {
        (new Validator)->validateInput('fetch_public_entities', $input);

        $responseArray = [];

        $this->trace->info(TraceCode::SCROOGE_FETCH_PUBLIC_ENTITIES_REQUEST, $input);

        foreach ($input[RefundConstants::PUBLIC_ENTITIES] as $requestEntity)
        {
            $entityId   = $requestEntity[RefundConstants::ENTITY_ID];
            $entityRepo = $requestEntity[RefundConstants::ENTITY_TYPE];
            // set only necessary params in $fetchInput
            $fetchInput = [];

            if (empty($requestEntity[Repository::EXPAND]) === false)
            {
                $fetchInput[Repository::EXPAND] = $requestEntity[Repository::EXPAND];
            }

            $responseArray[$entityId] = [
                RefundConstants::DATA  => NULL,
                RefundConstants::ERROR => NULL,
            ];

            try
            {
                $entity = $this->repo->$entityRepo->findOrFailByPublicIdWithParams($entityId, $fetchInput)->toArrayPublicWithExpand();

                $responseArray[$entityId][RefundConstants::DATA] = $entity;
            }
            catch (\Throwable $ex)
            {
                $responseArray[$entityId][RefundConstants::ERROR] = [
                    RefundConstants::CODE => $ex->getCode(),
                    RefundConstants::MESSAGE => $ex->getMessage()
                ];
            }
        }

        if (isset($input[RefundConstants::CUSTOM_PUBLIC_ENTITIES]) === false)
        {
            $input[RefundConstants::CUSTOM_PUBLIC_ENTITIES] = [];
        }

        foreach ($input[RefundConstants::CUSTOM_PUBLIC_ENTITIES] as $requestEntity)
        {
            $entityId   = $requestEntity[RefundConstants::ENTITY_ID];
            $entityType = $requestEntity[RefundConstants::ENTITY_TYPE];

            $responseArray[$entityId] = [
                RefundConstants::DATA  => NULL,
                RefundConstants::ERROR => NULL,
            ];

            try
            {
                switch ($entityType)
                {
                    case 'optimizer_settlement':
                        $fetchInput = ['transaction_id' => $requestEntity['transaction_id'] ?? ''];

                        $settlementResponse = app('settlements_merchant_dashboard')->getSettlementForTransaction($fetchInput);

                        $settlement = $settlementResponse['settlement'];

                        if (empty($settlement) === false)
                        {
                            $setl = new Settlement\Entity($settlement);

                            $setl->setPublicAttributeForOptimiser($settlement);

                            $responseArray[$entityId][RefundConstants::DATA] = $setl->toArrayPublic();
                        }
                }
            }
            catch (\Throwable $ex)
            {
                $responseArray[$entityId][RefundConstants::ERROR] = [
                    RefundConstants::CODE => $ex->getCode(),
                    RefundConstants::MESSAGE => $ex->getMessage()
                ];
            }
        }

        return $responseArray;
    }

    public function getRefundEmailData($input)
    {
        (new Validator)->validateInput('refund_email_data', $input);

        $response = [];

        $payment = $this->repo->payment->findOrFail($input[RefundConstants::PAYMENT_ID]);

        $merchant = $payment->merchant;

        $orgData = OrgWiseConfig::getOrgDataForEmail($merchant);

        $orgData['refunded_mail_enabled'] = OrgWiseConfig::getEmailEnabledForOrg($merchant->org->getCustomCode(), 'RZP\Mail\Payment\Refunded', $merchant);

        $response['org_data'] = $orgData;

        $virtualRefund = $this->buildVirtualRefundEntity($payment, $input[RefundConstants::REFUND]);

        $viewData = (new PaymentProcessor($merchant))->getRefundEmailData($virtualRefund);

        $viewData['payment']['method']  = [
            'first'   => $viewData['payment']['method'][0],
            'second'  => $viewData['payment']['method'][1],
        ];

        $viewData['payment']['amount_spread']  = [
            'symbol'     => $viewData['payment']['amount_spread'][0],
            'units'      => $viewData['payment']['amount_spread'][1],
            'subunits'   => $viewData['payment']['amount_spread'][2],
        ];

        $viewData['refund']['amount_components']  = [
            'symbol'     => $viewData['refund']['amount_components'][0],
            'units'      => $viewData['refund']['amount_components'][1],
            'subunits'   => $viewData['refund']['amount_components'][2],
        ];

        $response['view_entities_data'] = $viewData;

        return $response;
    }

    public function sendSelfServeSuccessAnalyticsEventToSegmentForFetchingRefundDetails($input)
    {
        [$segmentEventName, $segmentProperties] = $this->pushSelfServeSuccessEventsToSegment();

        $segmentProperties[SegmentConstants::SELF_SERVE_ACTION] = $this->getSelfServeActionForFetchingRefundDetail($input);

        if (isset($segmentProperties[SegmentConstants::SELF_SERVE_ACTION]) === true)
        {
            $this->app['segment-analytics']->pushIdentifyAndTrackEvent(
                $this->merchant, $segmentProperties, $segmentEventName
            );
        }
    }

    public function sendSelfServeSuccessAnalyticsEventToSegmentForFetchingRefundDetailsFromRefundId()
    {
        [$segmentEventName, $segmentProperties] = $this->pushSelfServeSuccessEventsToSegment();

        $segmentProperties[SegmentConstants::SELF_SERVE_ACTION] = 'Refund Details Searched';

        $this->app['segment-analytics']->pushIdentifyAndTrackEvent(
            $this->merchant, $segmentProperties, $segmentEventName
        );
    }

    private function pushSelfServeSuccessEventsToSegment()
    {
        $segmentProperties = [];

        $segmentEventName = SegmentEvent::SELF_SERVE_SUCCESS;

        $segmentProperties[SegmentConstants::OBJECT] = SegmentConstants::SELF_SERVE;

        $segmentProperties[SegmentConstants::ACTION] = SegmentConstants::SUCCESS;

        $segmentProperties[SegmentConstants::SOURCE] = SegmentConstants::BE;

        return [$segmentEventName, $segmentProperties];
    }

    private function getSelfServeActionForFetchingRefundDetail($input)
    {
        if ((isset($input[Entity::PAYMENT_ID]) === true) or
            (isset($input[Entity::NOTES]) === true))
        {
            return 'Refund Details Searched';
        }

        if (isset($input[Entity::PUBLIC_STATUS]) === true)
        {
            return 'Refund Details Filtered';
        }
    }

    /**
     * Fetches refund and merchant details for the payment.
     *
     * @param  Payment\Entity $payment
     * @return array
     */
    public function getPaymentAlongWithRefundDetails(Payment\Entity $payment): array
    {
        $paymentDetails = [
            RefundConstants::PAYMENTS => [],
        ];

        $this->populateRefundDetailsForCustomer($paymentDetails, $payment);

        $merchantLogo = $payment->merchant->getFullLogoUrlWithSize('medium');

        $paymentDetails['merchant_logo'] = $merchantLogo;

        $this->populateMerchantSupportDetails($paymentDetails);

        return $this->slicingDetailsforSecurity($paymentDetails);
    }

    /**
     * Gets Transaction Related data for scrooge
     *
     * @param $input
     * @return array
     * @throws \Throwable
     */
    public function getRefundTransactionData($input)
    {
        (new Validator)->validateInput('refund_transaction_data', $input);

        $response = [];

        $transaction = $this->repo->transaction->findByEntityIdWithoutMerchant($input[RefundConstants::REFUND_ID]);

        //Can add more data here in future if needed related to transaction
        $transaction_data ['transaction_id'] = $transaction->getId();

        $response['transaction_data'] = $transaction_data;

        return $response;
    }
}
