<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use Carbon\Carbon;

use RZP\Error\ErrorCode;
use RZP\Base\ConnectionType;
use RZP\Constants\HyperTrace;
use RZP\Models\BankTransfer\Constants;
use RZP\Models\Batch;
use RZP\Http\BasicAuth;
use RZP\Constants\Mode;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Trace\TraceCode;
use RZP\Base\JitValidator;
use RZP\Constants\Timezone;
use RZP\Models\Payment\Gateway;
use RZP\Models\BankTransfer\Entity;
use RZP\Models\BankTransfer\HdfcEcms;
use RZP\Models\BankTransfer\Validator;
use RZP\Models\VirtualAccount\Provider;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Trace\Tracer;
use RZP\Base\RuntimeManager;

class BankTransferController extends Controller
{

    public function processBankTransfer($bankTransferRequestPayload = null)
    {
        $input = Request::all();

        if($bankTransferRequestPayload !== null)
        {
            // For manual entity creation, sending the bank webhook payload
            $input = $bankTransferRequestPayload;
        }

        $this->trace->info(TraceCode::BANK_TRANSFER_YES_BANK_VA_INPUT, [
            Entity::INPUT           => $input,
            Entity::REQUEST_SOURCE  => Entity::INPUT,
            Entity::GATEWAY         => Provider::YESBANK,
        ]);

        try {

            $isCollectXYesbankCallback = $this->service()->checkForYesBankCollectxCallback($input);

            $provider = null;

            if ($isCollectXYesbankCallback === true) {
                $provider = Provider::YESBANK;
            }

            $response = $this->service()->saveRequestAndProcess($input, $provider, false, $input);

            $this->trace->info(TraceCode::BANK_TRANSFER_YES_BANK_VA_RESPONSE, [
                Entity::GATEWAY => Provider::YESBANK,
                "response" => $response
            ]);
        }
        catch (\Throwable $e) {

            return ApiResponse::json(
                [
                    'validateResponse' => [
                            'decision' => 'reject']
                ]);
        }

        return ApiResponse::json($response);
    }

    public function processBankTransferFile()
    {
        $input = Request::all();

        $response = $this->service()->processFile($input, Batch\Type::ECOLLECT_ICICI);

        return ApiResponse::json($response);
    }

    public function processBankTransferFileRbl()
    {
        $input = Request::all();

        $batchType = Batch\Type::ECOLLECT_RBL;

        if (isset($input['file_type'])  && ($input['file_type'] == 'collectx'))
        {
            $batchType = Batch\Type::ECOLLECT_RBL_BANKING;
        }

        $response = $this->service()->processFile($input, $batchType);

        return ApiResponse::json($response);
    }

    public function processBankTransferFileAxis()
    {
        $input = Request::all();

        $batchType = Batch\Type::ECOLLECT_AXIS;

        if (isset($input['file_type'])  && ($input['file_type'] == 'collectx'))
        {
            $batchType = Batch\Type::ECOLLECT_AXIS_BANKING;
        }

        $response = $this->service()->processFile($input, $batchType);

        return ApiResponse::json($response);
    }

    public function processBankTransferFileYesbank()
    {
        $input = Request::all();

        $response = $this->service()->processFile($input, Batch\Type::ECOLLECT_YESBANK);

        return ApiResponse::json($response);
    }

    public function processBankTransferFileIdfc()
    {
        $input = Request::all();

        $response = $this->service()->processFile($input, Batch\Type::ECOLLECT_IDFC);

        return ApiResponse::json($response);
    }

    public function processYesbankBankTransfer()
    {
        $this->app['basicauth']->setModeAndDbConnection(Mode::LIVE);

        $input = Request::all();

        $input[Entity::MODE] = strtolower($input[Entity::MODE]);

        $this->trace->info(TraceCode::YESBANK_VA_MIS, [
            Entity::INPUT           => $input,
            Entity::REQUEST_SOURCE  => Entity::FILE,
            Entity::GATEWAY         => Provider::YESBANK,
        ]);

        $response = $this->service()->saveRequestAndProcess($input, Provider::YESBANK, false, $input);

        return ApiResponse::json($response);
    }

    public function processIciciBankTransfer()
    {
        $this->app['basicauth']->setModeAndDbConnection(Mode::LIVE);

        $input = Request::all();

        $this->trace->info(TraceCode::ICICI_VA_MIS, [
            Entity::INPUT           => $input,
            Entity::REQUEST_SOURCE  => Entity::FILE,
            Entity::GATEWAY         => Provider::ICICI,
        ]);

        $response = $this->service()->saveRequestAndProcess($input, Provider::ICICI, true, $input);

        $this->trace->info(TraceCode::ICICI_VA_MIS_RESPONSE, [
            "response"              => $input,
            Entity::REQUEST_SOURCE  => Entity::FILE,
            Entity::GATEWAY         => Provider::ICICI,
        ]);

        return ApiResponse::json($response);
    }

    public function processPendingBankTransfer()
    {
        $input = Request::all();

        $this->trace->info(TraceCode::PROCESS_PENDING_BANK_TRANSFER_INPUT, $input);

        $response = $this->service()->processPendingBankTransfer($input);

        return ApiResponse::json($response);
    }

    public function processRblBankTransferTest()
    {
        $this->app['basicauth']->setModeAndDbConnection(Mode::TEST);

        return $this->processRblBankTransfer();
    }

    public function processRblBankTransferLive()
    {
        $this->app['basicauth']->setModeAndDbConnection(Mode::LIVE);

        return $this->processRblBankTransfer();
    }

    public function processRblBankTransferInternal()
    {
        $this->app['basicauth']->setModeAndDbConnection(Mode::LIVE);

        return $this->processRblBankTransfer(false);
    }

    public function processAxisBankTransferTest()
    {
        $this->app['basicauth']->setModeAndDbConnection(Mode::TEST);

        return $this->processAxisBankTransfer();
    }

    public function processAxisBankTransferLive()
    {
        $this->app['basicauth']->setModeAndDbConnection(Mode::LIVE);

        return $this->processAxisBankTransfer();
    }

    public function processAxisBankTransferInternal()
    {
        $this->app['basicauth']->setModeAndDbConnection(Mode::LIVE);

        return $this->processAxisBankTransfer(false);
    }

    public function processIblBankTransferTest()
    {
        $this->app['basicauth']->setModeAndDbConnection(Mode::TEST);

        return $this->processIblBankTransfer();
    }

    public function processIblBankTransferLive()
    {
        $this->app['basicauth']->setModeAndDbConnection(Mode::LIVE);

        return $this->processIblBankTransfer();
    }

    public function processIblBankTransferInternal()
    {
        $this->app['basicauth']->setModeAndDbConnection(Mode::LIVE);

        return $this->processIblBankTransfer(false);
    }

    public function validateIdfcBankTransferLive()
    {
        $this->app['basicauth']->setModeAndDbConnection(Mode::LIVE);

        $this->app['basicauth']->setBasicType(BasicAuth\Type::PRIVILEGE_AUTH);

        $request = Request::getContent();

        return $this->service()->validateIdfcBankTransfer($request);
    }

    public function processIdfcBankTransferLive()
    {
        $this->app['basicauth']->setModeAndDbConnection(Mode::LIVE);

        $this->app['basicauth']->setBasicType(BasicAuth\Type::PRIVILEGE_AUTH);

        $request = Request::getContent();

        return $this->service()->processIdfcBankTransfer($request);
    }

    public function processIdfcBankTransferInternal()
    {
        $this->app['basicauth']->setModeAndDbConnection(Mode::LIVE);

        $this->app['basicauth']->setBasicType(BasicAuth\Type::PRIVILEGE_AUTH);

        $request = Request::all();

        $ivForEncryption = random_bytes(16);

        $encryptedRequest = $this->service()->encryptIdfcBankCallbackData($request, $ivForEncryption);

        $response = $this->service()->processIdfcBankTransfer($encryptedRequest);

        return $this->service()->decryptIdfcBankCallbackData($response);
    }

    public function validateIdfcBankTransferTest()
    {
        $this->app['basicauth']->setModeAndDbConnection(Mode::TEST);

        $this->app['basicauth']->setBasicType(BasicAuth\Type::PRIVILEGE_AUTH);

        $request = Request::getContent();

        return $this->service()->validateIdfcBankTransfer($request);
    }

    public function processIdfcBankTransferTest()
    {
        $this->app['basicauth']->setModeAndDbConnection(Mode::TEST);

        $this->app['basicauth']->setBasicType(BasicAuth\Type::PRIVILEGE_AUTH);

        $request = Request::getContent();

        return $this->service()->processIdfcBankTransfer($request);
    }

    public function processRblBankTransfer($validateReqToken = true, $bankTransferRequestPayload = null)
    {
        // hardcoding this for now. We will fix this later.
        $this->app['basicauth']->setBasicType(BasicAuth\Type::PRIVILEGE_AUTH);

        $input = Request::all();

        if($bankTransferRequestPayload !== null)
        {
            // For manual entity creation, sending the bank webhook payload
            $input = $bankTransferRequestPayload;
        }

        $this->trace->info(TraceCode::RBL_VA_CALLBACK, $this->service()->removeSenderSensitiveInfoFromLogging($input, Provider::RBL));

        $errorResp = $this->validateRequestToken($validateReqToken);

        if ($errorResp !== null)
        {
            return $errorResp;
        }

        try
        {
            $inputList = $this->modifyRblDataToEntity($input);

            $provider = $inputList['gateway_provider']['provider'];

            $payeeAccount = $inputList['input']['payee_account'];

            $variantFlag = $this->app['razorx']->getTreatment($payeeAccount,
                                                              RazorxTreatment::SMARTCOLLECT_SERVICE_BANK_TRANSFER,
                                                              Mode::LIVE);

            if ($variantFlag === 'on')
            {
                if($bankTransferRequestPayload !== null)
                {
                    $this->service()->processBankTransferInScService($inputList['input'], $provider, $bankTransferRequestPayload);
                }
                else
                {
                    $this->service()->processBankTransferInScService($inputList['input'], $provider, Request::all());
                }
            }
            else
            {
                if($bankTransferRequestPayload !== null)
                {
                    $response = $this->service()->saveRequestAndProcess($inputList['input'], $provider, false, $bankTransferRequestPayload);
                }
                else
                {
                    $response = $this->service()->saveRequestAndProcess($inputList['input'], $provider, false, Request::all());
                }

                if (array_key_exists("isCollectXResponse", $response) === true && $response['valid'] === false)
                {
                    return ApiResponse::json(['Status' => 'Failure.'], 400);
                }

                /*
                 * Commenting this as RBL doesn't have check on their end to restrict retry count.
                 * In case the response is not 200, the retry is infinite.
                 */
                //if (boolval($response['valid']) === false)
                //{
                //    return ApiResponse::json([], 500);
                //}
            }
        }
        catch (BadRequestValidationFailureException $e)
        {

            if (TraceCode::RBL_PROVIDER_UNEXPEXTED_PAYMENT_ERROR === $e ->getMessage()){
                return array(
                    "error" => array(
                        "code" => TraceCode::RBL_PROVIDER_UNEXPEXTED_PAYMENT_ERROR,
                        "description" => "Payment Method not allowed for the gateway",
                    )
                );
            }

            if (TraceCode::YESBANK_GATEWAY_UNEXPECTED_PAYMENT_ERROR === $e ->getMessage()){
                return array(
                    "error" => array(
                        "code" => TraceCode::YESBANK_GATEWAY_UNEXPECTED_PAYMENT_ERROR,
                        "description" => "Payment Method not allowed for the gateway",
                    )
                );
            }

            $this->trace->traceException($e);

            return ApiResponse::json(['Status' => 'Failure.'], 400);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e);

            return ApiResponse::json(['Status' => 'Failure.']);
        }

        return ApiResponse::json(['Status' => 'Success']);
    }

    public function processAxisBankTransfer($validateReqToken = true, $bankTransferRequestPayload = null)
    {
        $this->app['basicauth']->setBasicType(BasicAuth\Type::PRIVILEGE_AUTH);

        $input = Request::all();

        if($bankTransferRequestPayload !== null)
        {
            // For manual entity creation, sending the bank webhook payload
            $input = $bankTransferRequestPayload;
        }

        $this->trace->info(TraceCode::AXIS_VA_CALLBACK,
            $this->service()->removeSenderSensitiveInfoFromLogging($input, Provider::AXIS));

        $this->traceXFundLoadingMetrics($input);

        $errorResp = $this->validateAxisRequestToken($validateReqToken);

        if ($errorResp !== null)
        {
            $this->traceXFundLoadingMetrics($input, '003');

            return $errorResp;
        }

        try
        {
            $inputList = $this->modifyAxisDataToEntity($input);

            $provider = $inputList['gateway_provider']['provider'];

            $payeeAccount = $inputList['input']['payee_account'];

            $variantFlag = $this->app['razorx']->getTreatment($payeeAccount,
                RazorxTreatment::SMARTCOLLECT_SERVICE_BANK_TRANSFER,
                Mode::LIVE);

            if ($variantFlag === 'on')
            {
                if($bankTransferRequestPayload !== null)
                {
                    $this->service()->processBankTransferInScService($inputList['input'], $provider, $bankTransferRequestPayload);
                }
                else
                {
                    $this->service()->processBankTransferInScService($inputList['input'], $provider, Request::all());
                }
            }
            else
            {
                if($bankTransferRequestPayload !== null)
                {
                    $response = $this->service()->saveRequestAndProcess($inputList['input'], $provider, false, $bankTransferRequestPayload);
                }
                else
                {
                    $response = $this->service()->saveRequestAndProcess($inputList['input'], $provider, false, Request::all());
                }

                if (array_key_exists("isCollectXResponse", $response) === true && $response['valid'] === false)
                {
                    return ApiResponse::json([
                        'Stts_flg' =>  'F',
                        'Err_cd'   =>  '002',
                        'message'  =>  'Validation failed',
                    ], 400);
                }
            }
        }
        catch (BadRequestValidationFailureException $e)
        {
            $this->traceXFundLoadingMetrics($input, '002');

            $this->trace->traceException($e);

            return ApiResponse::json([
                'Stts_flg' =>  'F',
                'Err_cd'   =>  '002',
                'message'  =>  'Validation failed',
            ], 400);
        }
        catch (\Throwable $e)
        {
            $this->traceXFundLoadingMetrics($input, '001');

            $this->trace->traceException($e);

            return ApiResponse::json([
                'Stts_flg' =>  'F',
                'Err_cd'   =>  '001',
                'message'  =>  'Authentication failed',
            ], 400);
        }

        return ApiResponse::json([
            'Stts_flg' =>  'S',
            'Err_cd'   =>  '000',
            'message'  =>  'Success',
        ]);
    }

    public function processIblBankTransfer($validateReqToken = true)
    {
        $this->app['basicauth']->setBasicType(BasicAuth\Type::PRIVILEGE_AUTH);

        $input = Request::all();

        $this->trace->info(TraceCode::IBL_VA_CALLBACK, $this->service()->removeSenderSensitiveInfoFromLogging($input, Provider::INDUSIND));

        $errorResp = $this->validateIblRequestToken($validateReqToken);

        if ($errorResp !== null)
        {
            return $errorResp;
        }

        try
        {
            $inputList = $this->modifyIblDataToEntity($input);

            $provider = $inputList['gateway_provider']['provider'];

            $this->service()->saveRequestAndProcess($inputList['input'], $provider, false, $inputList['input']);

        }
        catch (BadRequestValidationFailureException $e)
        {
            $this->trace->traceException($e);

            if($e->getMessage() === ErrorCode::BAD_REQUEST_MERCHANT_NOT_FOUND) {

                return ApiResponse::json([
                    'Stts_flg'   => 'F',
                    'Err_cd'     => '007',
                    'message'    => $e->getMessage(),
                    'Identifier' => $inputList['input']['description'],
                ], 400);

            }

            return ApiResponse::json([
                'Stts_flg'   => 'F',
                'Err_cd'     => '002',
                'message'    =>  $e->getMessage(),
                'Identifier' => $inputList['input']['description'],
            ], 400);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e);

            return ApiResponse::json([
                'Stts_flg'   => 'F',
                'Err_cd'     => '001',
                'message'    => 'Authentication failed',
                'Identifier' =>  $inputList['input']['description'],
            ], 400);
        }

        return ApiResponse::json([
            'Stts_flg'   => 'S',
            'Err_cd'     => '000',
            'message'    => 'Success',
            'Identifier' =>  $inputList['input']['description'],
        ]);
    }

    protected function traceXFundLoadingMetrics($input = [], $errorCode = '')
    {
        if ($this->doesRequestBelongToX($input))
        {
            $metric = empty($errorCode) ? TraceCode::AXIS_VA_CALLBACK: TraceCode::AXIS_VA_INVALID_CALLBACK_DATA;

            $this->trace->count(
                $metric,
                [
                    'error_code' => $errorCode,
                    'route_name' => $this->app['api.route']->getCurrentRouteName()
                ]);
        }
    }

    public function processIciciBankTransferCallback()
    {
        $input = Request::all();

        $this->trace->info(TraceCode::ICICI_VA_CALLBACK, [
            Entity::INPUT          =>    $this->service()->removeSenderSensitiveInfoFromLogging($input, Provider::ICICI),
            Entity::REQUEST_SOURCE =>   Entity::CALLBACK,
            Entity::GATEWAY        =>   Provider::ICICI,
        ]);

        try
        {
            $entityInput = $this->modifyIciciDataToEntity($input);

            $response = $this->service()->saveRequestAndProcess($entityInput, Provider::ICICI, false, $input);

            if (boolval($response['valid']) === false)
            {
                return $this->getIciciResponse($input, 'SERVER_ERROR');
            }
        }
        catch (BadRequestValidationFailureException $e)
        {
            $this->trace->traceException($e);

            return $this->getIciciResponse($input, 'BAD_REQUEST', 400);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e);

            return $this->getIciciResponse($input, 'ERROR');
        }

        return $this->getIciciResponse($input, '');
    }

    public function processHdfcEcmsBankTransfer()
    {
        $input = Request::all();

        $this->trace->info(TraceCode::HDFC_ECMS_VA_CALLBACK, $this->service()->removeSenderSensitiveInfoFromLogging($input, Provider::HDFC_ECMS));

        $serviceResponse = (new HdfcEcms\Service())->saveAndProcessRequest($input);

        return ApiResponse::json($serviceResponse);
    }

    protected function validateRequestToken($validateReqToken)
    {
        if ($validateReqToken === false)
        {
            return null;
        }

        $headers = Request::header();

        if (empty($headers['xorgtoken']) === true)
        {
            $this->trace->error(TraceCode::RBL_VA_INVALID_CALLBACK_DATA, [
                'message'   => 'empty token',
            ]);

            return ApiResponse::json(['Status' => 'Failure Invalid token.'], 400);
        }

        $actualToken = $headers['xorgtoken'][0];
        $expectedToken = $this->config['applications.rbl_va.org_token'];

        if (hash_equals($expectedToken, $actualToken) === false)
        {
            $this->trace->error(TraceCode::RBL_VA_INVALID_CALLBACK_DATA, [
                'message'   => 'invalid token',
            ]);

            return ApiResponse::json(['Status' => 'Failure Invalid token.'], 400);
        }

        return null;
    }

    protected function validateIblRequestToken($validateReqToken)
    {
        if ($validateReqToken === false)
        {
            return null;
        }

        $headers = Request::header();

        if (empty($headers['xorgtoken']) === true)
        {
            $this->trace->error(TraceCode::IBL_VA_EMPTY_TOKEN, [
                'message'   => 'empty token',
            ]);

            return ApiResponse::json([
                'Stts_flg'=>'F',
                'Err_cd'=>'003',
                'message'=>'Authentication failed',
            ], 400);
        }

        $actualToken = $headers['xorgtoken'][0];
        $expectedToken = $this->config['applications.ibl_va.org_token'];

        if (hash_equals($expectedToken, $actualToken) === false)
        {
            $this->trace->error(TraceCode::IBL_VA_INVALID_CALLBACK_DATA, [
                'message'   => 'invalid token',
            ]);

            return ApiResponse::json([
                'Stts_flg'=>'F',
                'Err_cd'=>'003',
                'message'=>'Authentication failed',
            ], 400);
        }

        return null;
    }

    protected function validateAxisRequestToken($validateReqToken)
    {
        if ($validateReqToken === false)
        {
            return null;
        }

        $headers = Request::header();

        if (empty($headers['xorgtoken']) === true)
        {
            $this->trace->error(TraceCode::AXIS_VA_INVALID_CALLBACK_DATA, [
                'message'   => 'empty token',
            ]);

            return ApiResponse::json([
                'Stts_flg'=>'F',
                'Err_cd'=>'003',
                'message'=>'Authentication failed',
            ], 400);
        }

        $actualToken = $headers['xorgtoken'][0];
        $expectedToken = $this->config['applications.axis_va.org_token'];

        if (hash_equals($expectedToken, $actualToken) === false)
        {
            $this->trace->error(TraceCode::AXIS_VA_INVALID_CALLBACK_DATA, [
                'message'   => 'invalid token',
            ]);

            return ApiResponse::json([
                'Stts_flg'=>'F',
                'Err_cd'=>'003',
                'message'=>'Authentication failed',
            ], 400);
        }

        return null;
    }

    protected function modifyRblDataToEntity($input)
    {
        (new JitValidator)->setStrictFalse()->rules(Validator::$rblRules)->caller($this)->validate($input);

        $data = $input['Data'][0];

        $mode = null;

        $utr = $data['UTRNumber'];

        $messageType = strtolower($data['messageType']);

        $variantFlag = $this->app['razorx']->getTreatment('bt_rbl',
            RazorxTreatment::PAYEE_ACCOUNT_LENGTH_VALIDATION,
            $this->app['rzp.mode']);

        if ($variantFlag === 'on' and strlen($data['beneficiaryAccountNumber']) < Constants::PAYEE_ACCOUNT_MINIMUM_LENGTH) {
            throw new BadRequestValidationFailureException(
                'beneficiaryAccountNumber length less than expected minimum (12)',
                null,
                $data
            );
        }

        switch ($messageType)
        {
            case 'n':
            case 'neft':
                $mode = \RZP\Models\BankTransfer\Mode::NEFT;
                break;

            case 'i':
            case 'ft':
                $mode = \RZP\Models\BankTransfer\Mode::IFT;
                break;

            case 'r':
            case 'rtgs':
                $mode = \RZP\Models\BankTransfer\Mode::RTGS;
                break;

            case 'imps':
                $utr = null;
                $utrPrefix = substr($data['UTRNumber'], 0, 4);
                switch ($utrPrefix)
                {
                    case 'UPI/':
                        $mode = \RZP\Models\BankTransfer\Mode::UPI;

                        // we receive UTR number in this format : UPI/006752404360/PAYMENT FROM PHONEPE/8199080070@Y
                        // 006752404360 is the UTR
                        $pieces = explode('/', $data['UTRNumber']);
                        $upiUtr = $pieces[1];
                        if (strlen($upiUtr) === 12)
                        {
                            $utr = $upiUtr;
                        }
                        break;

                    case 'IMPS':
                        $mode = \RZP\Models\BankTransfer\Mode::IMPS;

                        $utrType = substr($data['UTRNumber'], 0, 5);

                        if ($utrType === 'IMPS ')
                        {
                            // we receive UTR narration in this format: IMPS 006713653919 FROM MR  AAGOSH
                            // 006713653919 is the UTR
                            $value  = trim(preg_replace('/\s+/', ' ', $data['UTRNumber']));
                            $pieces = explode(' ', $value);

                        }
                        else if ($utrType === 'IMPS/')
                        {
                            // we receive UTR narration in this format: IMPS/234712686455/RAJANIKANT/UBI/TYPE YOUR
                            // 006713653919 is the UTR
                            $pieces = explode('/', $data['UTRNumber']);
                        }

                        $impsUtr = $pieces[1];
                        if (strlen($impsUtr) === 12)
                        {
                            $utr = $impsUtr;
                        }
                        break;
                    default:
                        $mode = \RZP\Models\BankTransfer\Mode::IFT;

                        // for internal fund transfer they send 007618022529-ACCOUNT VALIDATION pattern
                        // This is risky pattern to support but RBL sends the RRN like this
                        $pieces = explode('-', $data['UTRNumber']);
                        $iftUtr = $pieces[0];

                        if (strlen($iftUtr) === 12)
                        {
                            $utr = $iftUtr;
                        }
                        break;
                }
                break;

            default:
                throw new BadRequestValidationFailureException('invalid mode: '. $data['messageType'], null, $data);
        }

        if (($mode === null) or
            ($utr === null))
        {
            throw new BadRequestValidationFailureException('invalid data', null, $data);
        }

        try
        {
            if (strlen($data['creditDate']) === 17)
            {
                $time = Carbon::createFromFormat('d-m-Y His', $data['creditDate'], Timezone::IST)->getTimestamp();
            }
            else if (strlen($data['creditDate']) === 19)
            {
                $time = Carbon::createFromFormat('d-m-Y H:i:s', $data['creditDate'], Timezone::IST)->getTimestamp();
            }
            else
            {
                $time = Carbon::createFromFormat('d-m-Y', $data['creditDate'], Timezone::IST)->getTimestamp();
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->warning(TraceCode::RBL_VA_INVALID_CALLBACK_DATA, [
                    'time'  => $data['creditDate'] ?: null,
                ]);

            $time = Carbon::now(Timezone::IST)->getTimestamp();
        }

        $provider  = Provider::RBL;
        $payeeIfsc = Provider::IFSC[Provider::RBL];

        if (substr($data['beneficiaryAccountNumber'], 0, 5) === 'VAJSW')
        {
            $provider  = Provider::RBL_JSW;
            $payeeIfsc = Provider::IFSC[Provider::RBL_JSW];
        }

        return array(
            'input' => [
                            'payee_account'  => $data['beneficiaryAccountNumber'],
                            'payee_ifsc'     => $payeeIfsc,
                            'payer_name'     => $data['senderName'],
                            'payer_account'  => $data['senderAccountNumber'],
                            'payer_ifsc'     => $data['senderIFSC'],
                            'mode'           => $mode,
                            'transaction_id' => $utr,
                            'time'           => $time,
                            'amount'         => number_format($data['amount'], 2, '.', ''),
                            'description'    => $data['senderInformation'] ?? null,
                            'narration'      => $data['UTRNumber'],
                       ],
            'gateway_provider' => [
                            'provider'       => $provider,
                        ]);
    }

    protected function modifyAxisDataToEntity($input)
    {
        (new JitValidator)->setStrictFalse()->rules(Validator::$axisRules)->caller($this)->validate($input);

        $mode = null;

        $utr = $input['UTR'];

        $pMode = strtolower($input['Pmode']);

        switch ($pMode)
        {
            case 'NEFT':
            case 'neft':
                $mode = \RZP\Models\BankTransfer\Mode::NEFT;
                break;

            case 'RTGS':
            case 'rtgs':
                $mode = \RZP\Models\BankTransfer\Mode::RTGS;
                break;

            case 'Transfer':
            case 'transfer':
            case 'TRANSFER':
                $mode = \RZP\Models\BankTransfer\Mode::TRANSFER;
                break;
            case 'FT':
            case 'ft':
                $mode = \RZP\Models\BankTransfer\Mode::FT;
                break;
            case 'IMPS':
            case 'imps':
                $mode = \RZP\Models\BankTransfer\Mode::IMPS;
                break;

            default:
                throw new BadRequestValidationFailureException('invalid mode: '. $input['Pmode'], null, $input);
        }

        if (($mode === null) or
            ($utr === null))
        {
            throw new BadRequestValidationFailureException('invalid data', null, $input);
        }

        try
        {
            $time = Carbon::createFromFormat('Y-m-d H:i:s', $input['Req_dt_time'], Timezone::IST)->getTimestamp();
        }
        catch (\Throwable $e)
        {
            try
            {
                $time = Carbon::createFromFormat('d-m-Y H:i:s', $input['Req_dt_time'], Timezone::IST)->getTimestamp();
            }
            catch (\Throwable $e)
            {
                $this->trace->warning(TraceCode::AXIS_VA_INVALID_CALLBACK_DATA, [
                    'time'  => $input['Req_dt_time'] ?: null,
                ]);

                $time = Carbon::now(Timezone::IST)->getTimestamp();
            }
        }

        $provider  = Provider::AXIS;

        $payerAccount = isset($input['Sndr_acnt'])?$input['Sndr_acnt']:'';

        // correction of HSBC account number
        // original account number = "IN HSBC 054-123456-001"
        // correct account number = "054123456001"
        $hsbc_acc_no_pattern = '/IN\s+HSBC\s+(\d{3}-\d{6}-\d{3})/';
        if (preg_match($hsbc_acc_no_pattern, $payerAccount, $matches))
        {
            $payerAccount = preg_replace('/IN\s+HSBC\s+|-/', '', $payerAccount);
        }

        $payerName = isset($input['Sndr_nm'])?$input['Sndr_nm']:'';

        if ($this->doesRequestBelongToX($input))
        {
            $payeeIfsc = Provider::getIFSC(true)[Provider::AXIS];

            $utr = strtoupper($utr);

            // Check if the request is > 2 days old, reject the request if true
            $diff = Carbon::now(Timezone::IST)->diff(Carbon::createFromTimestamp($time, Timezone::IST));

            if($diff->days >= 2)
            {
                throw new BadRequestValidationFailureException('transaction older than 2 days', null, $input);
            }
        }
        else
        {
            $payeeIfsc = Provider::getIFSC()[Provider::AXIS];
        }

        if (substr($input['Bene_acc_no'], 0, 4) === Provider::BANK_ACCOUNT_RTPL_PREFIX)
        {
            $provider  = Provider::AXIS_RTPL;
            $payeeIfsc = Provider::IFSC[Provider::AXIS_RTPL];
        }

        return array(
            'input' => [
                'request_type'   => $input['Req_type'],
                'payee_account'  => $input['Bene_acc_no'],
                'payee_ifsc'     => $payeeIfsc,
                'payer_name'     => $payerName,
                'payer_account'  => $payerAccount,
                'payer_ifsc'     => $input['Sndr_ifsc'],
                'mode'           => $mode,
                'transaction_id' => $utr,
                'time'           => $time,
                'amount'         => number_format($input['Txn_amnt'], 2, '.', ''),
                'description'    => null,
                'narration'      => $input['UTR'],
            ],
            'gateway_provider' => [
                'provider'       => $provider,
            ]);
    }

    protected function modifyIblDataToEntity($input)
    {
        $provider = Provider::INDUSIND;

        $input['key'] = $this->config['applications.ibl_va.secret'];
        $input['gateway'] = Gateway::BT_IBL;

        $gateway = $this->app['gateway']->gateway(Gateway::BT_IBL);
        $resp = $gateway->preProcessServerCallback($input, Gateway::BT_IBL);

        $payeeIfsc = Provider::getIFSC()[$provider];

        // Fetching the Payee IFSC code from the bank account, as it is dynamic in the bt_ibl case.
        if (isset($resp['payee_account']) === true)
        {
            $bankAccount = $this->repo->bank_account->findVirtualBankAccountByAccountNumberAndBankCode($resp['payee_account'], null, true);
            if ($bankAccount !== null)
            {
                $payeeIfsc = $bankAccount->getIfscCode();
            }
        }

        return array(
            'input' => [
                'request_type'   => $resp['request_type'],
                'payee_account'  => $resp['payee_account'],
                'payee_ifsc'     => $payeeIfsc,
                'payer_name'     => $resp['payer_name'],
                'payer_account'  => $resp['payer_account'],
                'payer_ifsc'     => $resp['payer_ifsc'],
                'mode'           => $resp['mode'],
                'transaction_id' => $resp['transaction_id'],
                'time'           => $resp['time'],
                'amount'         => $resp['amount'],
                'description'    => $resp['description'],
                'narration'      => $resp['narration'],
            ],
            'gateway_provider' => [
                'provider' => $provider,
            ]);

    }

    protected function doesRequestBelongToX($input = []): bool
    {
        $xCorpCode = $this->config['applications.axis_va.x_corp_code'];
        $corpCode = $input['Corp_code'] ?? '';
        $payeeIfsc = $input['Payee_ifsc'] ?? '';

        if (($corpCode === $xCorpCode) or
            ($payeeIfsc === Provider::getIFSC(true)[Provider::AXIS]))
        {
            return true;
        }

        return false;
    }

    protected function modifyIciciDataToEntity($input)
    {
        (new JitValidator)->setStrictFalse()->rules(Validator::$iciciRules)->caller($this)->validate($input);

        $data = $input['Virtual_Account_Number_Verification_IN'][0];

        $mode = strtolower($data['mode']);

        switch ($mode)
        {
            case 'n':
                $mode = \RZP\Models\BankTransfer\Mode::NEFT;
                break;

            case 'f':
                $mode = \RZP\Models\BankTransfer\Mode::FT;
                break;

            case 'r':
                $mode = \RZP\Models\BankTransfer\Mode::RTGS;
                break;

            case 'o':
                $mode = \RZP\Models\BankTransfer\Mode::IMPS;
                break;

            case 'u':
                $mode = \RZP\Models\BankTransfer\Mode::UPI;
                break;

            default:
                throw new BadRequestValidationFailureException('invalid mode: '. $data['mode'], null, $data);
        }

        $time = Carbon::createFromFormat('Y-m-d H:i:s', $data['date'], Timezone::IST)->getTimestamp();

        return [
            'payee_account'  => $data['payee_account'],
            'payee_ifsc'     => Provider::IFSC[Provider::ICICI],
            'payer_name'     => $data['payer_name'],
            'payer_account'  => $data['payer_account'],
            'payer_ifsc'     => $data['payer_ifsc'],
            'mode'           => $mode,
            'transaction_id' => $data['transaction_id'],
            'time'           => $time,
            'amount'         => number_format($data['amount'], 2, '.', ''),
            'description'    => $data['description'] ?? null,
            'narration'      => $data['transaction_id'],
        ];
    }

    public function notifyBankTransfer()
    {
        $input = Request::all();

        $response = $this->service()->notify($input);

        return ApiResponse::json($response);
    }

    public function fetchBankTransferForPayment(string $paymentId)
    {
        $input = Request::all();

        $response = Tracer::inSpan(['name' => HyperTrace::VIRTUAL_ACCOUNTS_FETCH_PAYMENTS], function() use($paymentId)
        {
            return $this->service()->fetchBankTransferForPayment($paymentId);
        });

        return ApiResponse::json($response);
    }

    public function retryBankTransferRefund()
    {
        $input = Request::all();

        $response = $this->service()->retryBankTransferRefund($input);

        return ApiResponse::json($response);
    }

    public function editPayerBankAccount(string $id)
    {
        $input = Request::all();

        $response = $this->service()->editPayerBankAccount($id, $input);

        return ApiResponse::json($response);
    }

    public function stripPayerBankAccounts()
    {
        $input = Request::all();

        $response = $this->service()->stripPayerBankAccounts($input);

        return ApiResponse::json($response);
    }

    public function insertBankTransfer(string $provider)
    {
        $input = Request::all();

        $response = $this->service()->insert($provider, $input);

        return ApiResponse::json($response);
    }

    public function processBankTransferXDemoCron()
    {
        // Setting mode inside service layer does not work
        $this->app['basicauth']->setModeAndDbConnection('test');

        $response = $this->service()->processBankTransferXDemoCron();

        return ApiResponse::json($response);
    }

    public function createAccountForCurrencyCloud()
    {
        RuntimeManager::setTimeLimit(1800);
        RuntimeManager::setMemoryLimit("1024M");

        $input = Request::all();

        $response = $this->service()->createAccountForCurrencyCloud($input);

        return ApiResponse::json($response);

    }

    public function notificationsFromCurrencyCloud()
    {
        $input = Request::all();

        try
        {
            $request = Request::instance();
            $header = $request->header('notification_type');
        }
        catch (\Throwable $e)
        {
            $header = Request::header('notification_type');
        };

        if(isset($header) === false || empty($header) === true)
        {
            $header = $input['header']['notification_type'];
        }

        if(array_key_exists("id", $input) === false)
        {
            $input = $input['body'] ?? '';
        }

        $response = $this->service()->notificationsFromCurrencyCloud($input,$header);

        return ApiResponse::json($response);
    }

    public function captureCronForB2BPayments()
    {
        $input = Request::all();

        $response = $this->service()->captureCronForB2BPayments($input);

        return ApiResponse::json($response);
    }

    public function captureCronForPACBBankTransferPayments()
    {
        $input = Request::all();

        $response = $this->service()->captureCronForPACBBankTransferPayments($input);

        return ApiResponse::json($response);
    }

    public function toggleInternationalVirtualAccountForMerchant()
    {
        $input = Request::all();

        $response = $this->service()->toggleInternationalVirtualAccountForMerchant($input);

        return ApiResponse::json($response);
    }

    public function settlementFromCurrencyCloud()
    {
        $input = Request::all();

        $response = $this->service()->settleFundsFromCurrencyCloudCron($input);

        return ApiResponse::json($response);
    }

    public function getBalanceForMerchantVA($va_currency)
    {
        $input = Request::all();

        $response = $this->service()->getBalanceForMerchantVA($input, $va_currency);

        return ApiResponse::json($response);
    }

    public function createBeneficiaryForMerchantInCC(string $merchantId)
    {
        $input = Request::all();

        $input['merchant_id'] = $merchantId;

        $response = $this->service()->createBeneficiaryForMerchantInCC($input);

        return ApiResponse::json($response);
    }

    public function getBeneficiaryDetailsForMerchantPayout()
    {
        $input = Request::all();

        $response = $this->service()->getBeneficiaryDetailsForMerchantPayout($input);

        $finalResponse = [
            'account_number' => $response['account_number'],
            'name'           => $response['name'],
            'bank_name'      => $response['bank_name'],
            'bic_swift'      => $response['bic_swift'],
            'commission_fee' => $response['commission_fee']
        ];

        return ApiResponse::json($finalResponse);
    }

    public function getBeneficiaryDetailsForMerchantPayoutAdmin(string $merchantId)
    {
        $input = Request::all();

        $input['merchant_id'] = $merchantId;

        $response = $this->service()->getBeneficiaryDetailsForMerchantPayout($input);

        return ApiResponse::json($response);
    }

    public function merchantPayoutFromVAToBeneficiary()
    {
        $input = Request::all();

        $response = $this->service()->merchantPayoutFromVAToBeneficiary($input);

        return ApiResponse::json($response);
    }

    public function fetchAllPayoutsForIntlVA()
    {
        $input = Request::all();

        $response = $this->service()->fetchAllPayoutsForIntlVA($input);

        return ApiResponse::json($response);
    }

    private function getIciciResponse(array $input, string $failureReason, int $statusCode = 200)
    {
        $input = $input['Virtual_Account_Number_Verification_IN'][0];

        $input['status'] = $statusCode === 200 ? 'ACCEPT' : 'REJECT';

        $input['reject_reason'] = $failureReason;

        $this->trace->info(TraceCode::ICICI_VA_CALLBACK_RESPONSE, [
            "response"             =>   $input,
            "status_code"          =>   $statusCode,
            Entity::GATEWAY        =>   Provider::ICICI,
        ]);

        return ApiResponse::json(['Virtual_Account_Number_Verification_OUT' =>
            [
                $input
            ]
        ], $statusCode);
    }

    public function processBankTransferInternal()
    {
        $input = Request::all();

        $headers = Request::header();

        $routeName = (isset($headers['route-name'][0]) === true) ? $headers['route-name'][0] : null;

        $response = $this->service()->saveRequestAndProcessInternal($input, $routeName);

        return ApiResponse::json($response);

    }

    public function createAddressEntityForB2B(string $paymentId)
    {
        $input = Request::all();

        $response = $this->service()->createAddressEntityForB2B($input, $paymentId);

        return ApiResponse::json($response);

    }

    public function getAddressEntityForB2B(string $paymentId)
    {
        [$payment, $addresses] = $this->service()->getAddressEntityForB2B($paymentId);

        return ApiResponse::json($addresses->first());

    }

    public function sendNotificationForB2B()
    {
        $input = Request::all();

        $response = $this->service()->sendNotificationForB2B($input);

        return ApiResponse::json($response);

    }

    public function cbInvoiceWorkflowCallback()
    {
        $input = Request::all();

        $data = $this->service()->cbInvoiceWorkflowCallback($input);

        return ApiResponse::json($data);
    }
    public function fetchMerchantIntegrationByParams()
    {
        $input = Request::all();

        $data = $this->service()->fetchMerchantIntegrationByParams($input);

        return ApiResponse::json($data);
    }

    public function manualProcessBankTransferRequest($input)
    {
        $gateway = $input['gateway'];

        $requestPayload = $input['request_payload'] ?? null;

        if(isset($input['bank_transfer_request_id']))
        {

            $bankTransferRequestEntity = $this->repo->bank_transfer_request->findOrFailPublic($input['bank_transfer_request_id']);

            if($bankTransferRequestEntity === null)
            {
                throw new BadRequestValidationFailureException(
                    ErrorCode::BANK_TRANSFER_REQUEST_NOT_FOUND,
                    null,
                    $input
                );
            }

            $bankTransferRequestEntity = $bankTransferRequestEntity->toArray();

            $requestPayload = json_decode($bankTransferRequestEntity['request_payload'], true);
        }

        if($requestPayload === null)
        {
            throw new BadRequestValidationFailureException(
                ErrorCode::BANK_TRANSFER_REQUEST_NOT_FOUND,
                null,
                $input
            );
        }

        try
        {
            switch ($gateway) {
                case Provider::YESBANK:

                    $resp = $this->processBankTransfer($requestPayload);

                    return $resp->getData(true);

                case Provider::RBL:

                    $resp = $this->processRblBankTransfer(false, $requestPayload);

                    return $resp->getData(true);

                case Provider::AXIS:

                    $resp = $this->processAxisBankTransfer(false, $requestPayload);

                    return $resp->getData(true);

                default:

                    throw new BadRequestValidationFailureException(ErrorCode::GATEWAY_ERROR_UNKNOWN_ERROR,
                        null,
                        $input
                    );
            }
        }
        catch (\Exception $e) {

            $this->trace->traceException($e);

            throw new BadRequestValidationFailureException(
                message: $e->getMessage() ? $e->getMessage() : ErrorCode::MANUAL_SMART_COLLECT_ENTITY_CREATION_FAILED,
                field :null,
                data: $input
            );
        }
    }
}
