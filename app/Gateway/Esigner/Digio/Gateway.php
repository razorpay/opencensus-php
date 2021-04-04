<?php

namespace RZP\Gateway\Esigner\Digio;

use View;
use RZP\Error;
use Carbon\Carbon;
use RZP\Exception;
use Lib\PhoneBook;
use RZP\Gateway\Base;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Gateway\Base\Verify;
use RZP\Models\Customer\Token;
use RZP\Gateway\Base\VerifyResult;
use RZP\Models\Settlement\Holidays;
use RZP\Constants\Mode as BaseMode;
use RZP\Models\Bank\Name as BankName;
use RZP\Gateway\Enach\Base\CategoryCode;

class Gateway extends Base\Gateway
{
    protected $gateway = 'esigner_digio';

    protected $accountTypeMapping = [
        Token\Entity::ACCOUNT_TYPE_SAVINGS => 'Savings',
        Token\Entity::ACCOUNT_TYPE_CURRENT => 'Current',
    ];

    public function authorize(array $input)
    {
        parent::authorize($input);

        $request = $this->getMandateCreationRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $this->trace->info(TraceCode::GATEWAY_MANDATE_RESPONSE, [
            'gateway' => 'digio',
            'payment_id' => $input['payment']['id'],
            'response' => $response->body]);

        if ($response->status_code !== 200)
        {
            $responseBody = json_decode($response->body, true);

            throw new Exception\GatewayErrorException(Error\ErrorCode::GATEWAY_ERROR_MANDATE_CREATION_FAILED,
                                                      $responseBody['code'],
                                                      $responseBody['message'],
                                                      $responseBody);
        }

        return $this->getRedirectRequestArray($input, $response);
    }

    public function callback(array $input)
    {
        parent::callback($input);

        if ((isset($input['gateway']['status']) === true) and
            ($input['gateway']['status'] === Status::CANCEL))
        {
            $status = $input['gateway']['status'];
            $message = $input['gateway']['message'] ?? null;

            throw new Exception\BadRequestException(
                Error\ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED_AT_EMANDATE_REGISTRATION,
                $status,
                $message);
        }

        if ((isset($input['gateway']['status']) === false) or
            ($input['gateway']['status'] !== Status::SUCCESS))
        {
            $status = $input['gateway']['status'] ?? null;
            $message = $input['gateway']['message'] ?? null;

            throw new Exception\GatewayErrorException(
                Error\ErrorCode::GATEWAY_ERROR_MANDATE_CREATION_FAILED,
                $status,
                $message);
        }

        $request = $this->getMandateFetchRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        if ($response->status_code !== 200)
        {
            throw new Exception\GatewayErrorException(
                Error\ErrorCode::GATEWAY_ERROR_MANDATE_CREATION_FAILED);
        }

        $mandateXml = $response->body;

        if (empty($mandateXml) === true)
        {
            throw new Exception\GatewayErrorException(
                Error\ErrorCode::GATEWAY_ERROR_MANDATE_CREATION_FAILED);
        }

        $content = [
            'signed_xml' => $mandateXml
        ];

        return $content;
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    protected function getRedirectRequestArray(array $input, $response)
    {
        $decodedResponse = json_decode($response->body, true);

        $mandateId = $decodedResponse['id'];

        $content = [
            'logo'         => urlencode('https://razorpay.com/assets/razorpay-logo-95e9447029.svg'),
            'redirect_url' => $input['callbackUrl'],
            'error_url'    => $input['callbackUrl'],
        ];

        // For biometric authentication, Digio expects this parameter
        if ($input['payment'][Payment\Entity::AUTH_TYPE] === Payment\AuthType::AADHAAR_FP)
        {
            $content['mode'] = Constants::MODE_FP;
        }

        $this->domainType = 'redirect_' . $this->getMode();

        $request = $this->getStandardRequestArray([], 'get', null, false);

        $request['url'] .= '?' . http_build_query($content);
        unset($request['options']);
        unset($request['headers']);

        $replacePairs = [
            '{id}' => $mandateId,
            '{txnId}' => strtolower(substr($input['payment']['id'], 0, 10)),
            '{contact}' => $this->getFormattedContact($input['payment']['contact'])
        ];

        $request['url'] = strtr($request['url'], $replacePairs);

        $request['content']['reference_id'] = $mandateId;

        return $request;
    }

    protected function getRequestForDirectType(array $input, $response)
    {
        $decodedResponse = json_decode($response->body, true);

        $mandateId = $decodedResponse['id'];

        $request = [
            'content' => json_encode([
                'signer_id'     => $mandateId,
                'identifier'    => $this->getFormattedContact($input['payment']['contact']),
                'environment'   => Mode::map($this->getMode()),
            ]),
            'callback_url'  => $input['callbackUrl'],
        ];

        $content = View::make('gateway.digio')->with('request', $request)->render();

        return [
            'method' => 'direct',
            'content' => $content
        ];
    }

    protected function getMandateCreationRequestArray(array $input)
    {
        $content = [
            'signers' => [
                [
                    'identifier' => $this->getFormattedContact($input['payment']['contact'])
                ]
            ],
            'expire_in_days' => 1,
            'enach_type'     => Type::CREATE,
            'content'        => $this->getEmandateData($input)
        ];

        if ($input['token']->getAadhaarVid() !== null)
        {
            $content['signers'][0]['vid'] = $input['token']->getAadhaarVid();
        }

        return $this->getStandardRequestArray($content, 'POST', 'create');
    }

    protected function getMandateFetchRequestArray(array $input)
    {
        $content = [
            'mandate_id' => $input['gateway']['digio_doc_id'],
        ];

        return $this->getStandardRequestArray($content, 'GET', 'fetch', false);
    }

    protected function getEmandateData(array $input)
    {
        $nextWorkingDt = $this->getNextWorkingDate($input);

        $destinationBankIfsc = $input['token']->getIfsc();
        $bankCode = $this->getTerminalAccessCode($input);

        $mcc = $this->input['terminal']['category'];
        $serviceProviderName = $input['merchant']->getFilteredDba() ?: $this->getGatewayMerchantId2();

        $content = [
            'mandate_request_id'            => $input['payment']['id'],
            'mandate_creation_date_time'    => $nextWorkingDt->toIso8601String(),
            'sponsor_bank_id'               => $bankCode,
            'sponsor_bank_name'             => BankName::getName($bankCode),
            'destination_bank_id'           => $destinationBankIfsc,
            'destination_bank_name'         => BankName::getName($destinationBankIfsc),
            // TODO: Remove sending aadhaar number later
            'aadhaar'                       => $input['token']->getAadhaarNumber(),
            'bank_identifier'               => substr($bankCode, 0, 4),
            'management_category'           => CategoryCode::getCategoryCodeFromMcc($mcc),
            'service_provider_name'         => substr($serviceProviderName, 0, 40),
            'service_provider_utility_code' => $this->getGatewayMerchantId(),
            'login_id'                      => $this->getGatewayTerminalId(),
            'customer_account_number'       => $input['token']->getAccountNumber(),
            'customer_account_type'         => $this->getAccountType($input['token']->getAccountType()),
            'instrument_type'               => Instrument::DEBIT,
            'customer_name'                 => $input['token']->getBeneficiaryName(),
            'maximum_amount'                => $input['token']->getMaxAmount() / 100,
            'is_recurring'                  => true,
            'frequency'                     => Frequency::ADHOC,
            'first_collection_date'         => $nextWorkingDt->format('Y-m-d'),
        ];

        if ($input['token']->getExpiredAt() !== null)
        {
            $finalCollection = Carbon::now(Timezone::IST)->setTimestamp($input['token']->getExpiredAt());

            $content['final_collection_date'] = $finalCollection->format('Y-m-d');
        }

        $traceContent = $content;

        $paymentEmail = $input['payment'][Payment\Entity::EMAIL];

        if ((empty($paymentEmail) === false) and
            ($paymentEmail !== Payment\Entity::DUMMY_EMAIL))
        {
            $traceContent['customer_email'] = $content['customer_email'] = $paymentEmail;
        }

        unset($traceContent['aadhaar'], $traceContent['customer_account_number']);

        $this->trace->info(TraceCode::GATEWAY_MANDATE_CONTENT, $traceContent);

        return json_encode($content);
    }

    protected function getAccountType($accountType)
    {
        if (isset($this->accountTypeMapping[$accountType]) === true)
        {
            return $this->accountTypeMapping[$accountType];
        }

        return 'Savings';
    }

    protected function getStandardRequestArray($content = [], $method = 'post', $type = null, $json = true)
    {
        $headers = [];

        if ($json === true)
        {
            $content = json_encode($content);

            $headers = [
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ];
        }

        $request = parent::getStandardRequestArray($content, $method, $type);

        $request['headers'] = $headers;

        $request['options'] = [
            'auth' => [$this->getClientId(), $this->getClientPassword()]
        ];

        return $request;
    }

    protected function getClientId()
    {
        return $this->config['client_id'];
    }

    protected function getClientPassword()
    {
        return $this->config['client_password'];
    }

    protected function getTerminalAccessCode(array $input)
    {
        if ($this->mode === BaseMode::LIVE)
        {
            return $input['terminal']['gateway_access_code'];
        }

        return $this->getTestAccessCode();
    }

    protected function getGatewayTerminalId()
    {
        if ($this->mode === BaseMode::LIVE)
        {
            return $this->input['terminal']['gateway_terminal_id'];
        }

        return $this->config['test_terminal_id'];
    }

    protected function getGatewayMerchantId()
    {
        if ($this->mode === BaseMode::LIVE)
        {
            return $this->getLiveMerchantId();
        }

        return $this->getTestMerchantId();
    }

    protected function getGatewayMerchantId2()
    {
        if ($this->mode === BaseMode::LIVE)
        {
            return $this->getLiveMerchantId2();
        }

        return $this->getTestMerchantId2();
    }

    protected function getNextWorkingDate($input)
    {
        if (isset($input['gateway']['next_working_dt']) === true)
        {
            return $input['gateway']['next_working_dt'];
        }

        $paymentCreatedAt = $input['payment']['created_at'];

        $dt = Carbon::createFromTimestamp($paymentCreatedAt, Timezone::IST);

        return Holidays::getNextWorkingDay($dt);
    }

    protected function getFormattedContact($contact)
    {
        $number = new PhoneBook($contact, true);

        return $number->format(PhoneBook::DOMESTIC);
    }

    protected function getRepository()
    {
        $gateway = 'enach';

        return $this->app['repo']->$gateway;
    }

    protected function sendPaymentVerifyRequest(Verify $verify)
    {
        $request = $this->getVerifyRequestArray($verify);

        $response = $this->sendGatewayRequest($request);

        $rawContent = $response->body;

        $verify->verifyResponseContent = $this->jsonToArray($rawContent);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            [
                'response_body' => $rawContent,
                'content'       => $verify->verifyResponseContent,
                'payment_id'    => $verify->input['payment']['id'],
                'status_code'   => $response->status_code,
                'gateway'       => $this->gateway,
            ]);
    }

    protected function getVerifyRequestArray($verify)
    {
        $request = $this->getStandardRequestArray([], 'get', null, false);

        $gatewayPayment = $verify->payment;

        $replacePairs = [
            '{id}' => $gatewayPayment->getGatewayReferenceId(),
        ];

        $request['url'] = strtr($request['url'], $replacePairs);

        return $request;
    }

    protected function verifyPayment(Verify $verify)
    {
        $verify->status = $this->getVerifyStatus($verify);

        $verify->match = ($verify->status === VerifyResult::STATUS_MATCH);

        $verify->payment = $this->saveSignedXml($verify);
    }

    protected function getVerifyStatus(Verify $verify): string
    {
        $status = VerifyResult::STATUS_MATCH;

        $this->checkApiSuccess($verify);

        $this->checkGatewaySuccess($verify);

        if ($verify->gatewaySuccess !== $verify->apiSuccess)
        {
            $status = VerifyResult::STATUS_MISMATCH;
        }

        return $status;
    }

    protected function checkGatewaySuccess(Verify $verify)
    {
        $verify->gatewaySuccess = false;

        $content = $verify->verifyResponseContent;

        $status = (isset($content['status']) === true) ? trim($content['status']) : Status::UNSIGNED;

        if ($status === Status::SIGNED)
        {
            $verify->gatewaySuccess = true;
        }
    }

    protected function saveSignedXml(Verify $verify)
    {
        if ($verify->gatewaySuccess === true)
        {
            $gatewayPayment = $verify->payment;

            $content = [
                'mandate_id' => $gatewayPayment->getGatewayReferenceId()
            ];

            $request = $this->getStandardRequestArray($content, 'GET', 'fetch', false);

            $response = $this->sendGatewayRequest($request);

            $mandateXml = $response->body;

            $dt = Carbon::now(Timezone::IST);
            $nextWorkingDayTimestamp = Holidays::getNextWorkingDay($dt)->getTimestamp();

            $content = [
                'registration_date' => $nextWorkingDayTimestamp,
                'signed_xml'        => $mandateXml,
            ];

            $gatewayPayment->fill($content);

            $this->app['repo']->enach->saveOrFail($gatewayPayment);

            return $gatewayPayment;
        }
    }
}
