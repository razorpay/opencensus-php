<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use Carbon\Carbon;

use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Base\JitValidator;
use RZP\Constants\Timezone;
use RZP\Models\BankTransfer\Validator;
use RZP\Models\VirtualAccount\Provider;
use RZP\Exception\BadRequestValidationFailureException;

class BankTransferController extends Controller
{
    public function processBankTransfer()
    {
        $input = Request::all();

        $response = $this->service()->process($input);

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

    public function processRblBankTransfer()
    {
        $errorResp = $this->validateRequestToken();

        if ($errorResp !== null)
        {
            return $errorResp;
        }

        $input = Request::all();

        try
        {
            $this->trace->info(TraceCode::RBL_VA_CALLBACK, $input);

            $input = $this->modifyRblDataToEntity($input);

            $response = $this->service()->process($input, Provider::RBL);

            if (boolval($response['valid']) === false)
            {
                return ApiResponse::json([], 500);
            }
        }
        catch (BadRequestValidationFailureException $e)
        {
            $this->trace->traceException($e);

            return ApiResponse::json(['Status' => 'Failure.'], 400);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e);

            return ApiResponse::json([], 500);
        }

        return ApiResponse::json(['Status' => 'Success']);
    }

    protected function validateRequestToken()
    {
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

        $mode = '';

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
                $mode = \RZP\Models\BankTransfer\Mode::IMPS;
                break;

            default:
                throw new BadRequestValidationFailureException('invalid mode: '. $data['messageType'], null, $data);
        }

        try
        {
            if (strlen($data['creditDate']) === 17)
            {
                $time = Carbon::createFromFormat('d-m-Y His', $data['creditDate'], Timezone::IST)->getTimestamp();
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
            'transaction_id' => $data['UTRNumber'],
            'time'           => $time,
            'amount'         => number_format($data['amount'], 2, '.', ''),
            'description'    => $data['senderInformation'] ?? null,
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

        $response = $this->service()->fetchBankTransferForPayment($paymentId);

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
}
