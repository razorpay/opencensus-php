<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use Carbon\Carbon;

use RZP\Base\ConnectionType;
use RZP\Constants\HyperTrace;
use RZP\Models\Batch;
use RZP\Http\BasicAuth;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Base\JitValidator;
use RZP\Constants\Timezone;
use RZP\Models\BankTransfer\Entity;
use RZP\Models\BankTransfer\HdfcEcms;
use RZP\Models\BankTransfer\Validator;
use RZP\Models\VirtualAccount\Provider;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Trace\Tracer;

class BankTransferController extends Controller
{

    public function processBankTransfer()
    {
        $input = Request::all();

        $response = $this->service()->saveRequestAndProcess($input, null, false, $input);

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

        $response = $this->service()->processFile($input, Batch\Type::ECOLLECT_RBL);

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

    public function processRblBankTransfer($validateReqToken = true)
    {
        // hardcoding this for now. We will fix this later.
        $this->app['basicauth']->setBasicType(BasicAuth\Type::PRIVILEGE_AUTH);

        $input = Request::all();

        $this->trace->info(TraceCode::RBL_VA_CALLBACK, $this->service()->removeSenderSensitiveInfoFromLogging($input, Provider::RBL));

        $errorResp = $this->validateRequestToken($validateReqToken);

        if ($errorResp !== null)
        {
            return $errorResp;
        }

        try
        {
            $input = $this->modifyRblDataToEntity($input);
            $provider = Provider::RBL;

//          manually config for JSW as the IFSC changed for JSW
            if (substr($input['payee_account'], 0, 5) === 'VAJSW')
            {
                $provider = Provider::RBL_JSW;
            }

            $response = $this->service()->saveRequestAndProcess($input, $provider, false, Request::all());
            /*
             * Commenting this as RBL doesn't have check on their end to restrict retry count.
             * In case the response is not 200, the retry is infinite.
             */
            //if (boolval($response['valid']) === false)
            //{
            //    return ApiResponse::json([], 500);
            //}
        }
        catch (BadRequestValidationFailureException $e)
        {
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

    protected function modifyRblDataToEntity($input)
    {
        (new JitValidator)->setStrictFalse()->rules(Validator::$rblRules)->caller($this)->validate($input);

        $data = $input['Data'][0];

        $mode = null;

        $utr = $data['UTRNumber'];

        $messageType = strtolower($data['messageType']);

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

                        // we receive UTR narration in this format: IMPS 006713653919 FROM MR  AAGOSH
                        // 006713653919 is the UTR
                        $value = trim(preg_replace('/\s+/', ' ', $data['UTRNumber']));
                        $pieces = explode(' ', $value);

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

        return [
            'payee_account'  => $data['beneficiaryAccountNumber'],
            'payee_ifsc'     => Provider::IFSC[Provider::RBL],
            'payer_name'     => $data['senderName'],
            'payer_account'  => $data['senderAccountNumber'],
            'payer_ifsc'     => $data['senderIFSC'],
            'mode'           => $mode,
            'transaction_id' => $utr,
            'time'           => $time,
            'amount'         => number_format($data['amount'], 2, '.', ''),
            'description'    => $data['senderInformation'] ?? null,
            'narration'      => $data['UTRNumber'],
        ];
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

    private function getIciciResponse(array $input, string $failureReason, int $statusCode = 200)
    {
        $input = $input['Virtual_Account_Number_Verification_IN'][0];

        $input['status'] = $statusCode === 200 ? 'ACCEPT' : 'REJECT';

        $input['reject_reason'] = $failureReason;

        return ApiResponse::json(['Virtual_Account_Number_Verification_OUT' =>
            [
                $input
            ]
        ], $statusCode);
    }
}
