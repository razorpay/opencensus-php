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

    public function processBankTransferFile()
    {
        $input = Request::all();

        $response = $this->service()->processFile($input);

        return ApiResponse::json($response);
    }

    public function processIciciBankTransfer()
    {
        $input = Request::all();

        $response = $this->service()->process($input, Provider::ICICI, true);

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
        $input = Request::all();

        $this->trace->info(TraceCode::RBL_VA_CALLBACK, $input);

        $errorResp = $this->validateRequestToken();

        if ($errorResp !== null)
        {
            return $errorResp;
        }

        try
        {
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
