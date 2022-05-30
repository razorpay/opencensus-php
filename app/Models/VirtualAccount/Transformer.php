<?php

namespace RZP\Models\VirtualAccount;

use RZP\Base\JitValidator;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Exception;
use RZP\Exception\BadRequestException;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Base;
use RZP\Trace\TraceCode;

class Transformer extends Base\Service
{
    protected $authToResponseMap = [
      'hdfc_otc' =>   [
          'challan_number' => 'challan_no',
          'amount' => 'expected_amount',
          'identification_id' => 'identification_id',
          'currency' => 'currency',
          'partial_payment' => 'partial_payment',
          'status' => 'status',
          'error' => 'error'
      ]
    ];

    protected $authToRequestMap = [
        'hdfc_otc' => [
            'challan_no' => 'challan_number',
            'expected_amount' => 'amount',
            'client_code' => 'client_code',
            'identification_id' => 'identification_id'
        ]
    ];
    /**
     * @throws \Throwable
     * @throws Exception\BadRequestException
     * @throws Exception\BadRequestValidationFailureException
     */
    public function getGenericRequest($input): array
    {
        $this->trace->info(TraceCode::OTC_VALIDATION_BANK_REQUEST,
            [
                'Input from Bank' => $input
            ]);

        $auth = $this->auth->getInternalApp();

        $genericRequest = [];

        switch ($auth) {
            case 'hdfc_otc' :
                $map = $this->authToRequestMap[$auth];
                (new Validator())->validateInput('offline_challan_hdfc', $input);
                $genericRequest = $this->getGenericRequestFromHdfcRequest($input,$map);
                break;

            default :
                throw new Exception\ServerErrorException(
                    'Invalid Auth',
                    ErrorCode::SERVER_ERROR_INVALID_AUTH
                );

        }

        return $genericRequest;

    }

    public function getGenericRequestFromHdfcRequest($input,$map): array
    {
        $genericRequest = [];

        foreach ($map as $key => $value) {
            if(isset($input[$key]) === true)
                $genericRequest[$value] = $input[$key];
        }

        $this->trace->info(TraceCode::OTC_VALIDATION_GENERIC_REQUEST,
            [
                'Generic Request' => $genericRequest
            ]);

        return $genericRequest;
    }

    public function getHdfcResponseFromGenericResponse($input,$map): array
    {
        $genericResponse = [];

        if(empty($input) === true)
            return $genericResponse;

        $this->trace->info(TraceCode::OTC_VALIDATION_GENERIC_RESPONSE,
            [
                'Generic Response' => $input
            ]);

        foreach ($map as $key => $value) {
            if(is_null($input['error']) === true or isset($input[$key]) === true)
                $genericResponse[$value] = $input[$key];
        }


        return $genericResponse;
    }

    /**
     * @throws Exception\AuthenticationException
     */
    public function getCustomResponse($input): array
    {
        $auth = $this->auth->getInternalApp();

        $map = $this->authToResponseMap[$auth];

        $customResponse = [];

        switch ($auth) {
            case 'hdfc_otc' :
                $customResponse = $this->getHdfcResponseFromGenericResponse($input,$map);
                break;

            default :
                throw new Exception\ServerErrorException(
                    'Invalid Auth',
                    ErrorCode::SERVER_ERROR_INVALID_AUTH
                );

        }

        $this->trace->info(TraceCode::OTC_VALIDATION_BANK_RESPONSE,
            [
                'Response for bank' => $customResponse
            ]);

        return  $customResponse;
    }

    /**
     * @throws Exception\ServerErrorException
     */
    public function genericExceptionHandler($input, $errorCode, $description = null,
                                            $code = null, $isGeneric = false): array
    {
        $auth = $this->auth->getInternalApp();

        $data = [];

        switch ($auth){
            case 'hdfc_otc':
                $data = $this->exceptionHandler($input,$errorCode,$description,$code,$isGeneric);
                break;

            default :
                throw new Exception\ServerErrorException(
                    'Invalid Auth',
                    ErrorCode::SERVER_ERROR_INVALID_AUTH
                );
        }

        return $data;
    }

    public function exceptionHandler($input, $errorCode, $description = null, $code = null, $isGeneric = false)
    {
        $responseBody = [
            'challan_no' => '',
            'expected_amount' => 0,
            'currency' => 'INR',
            'partial_payment' => '',
            'identification_id' => '',
            'status' => '',
            'error' => null
        ];

        if($isGeneric === true)
            $input = $this->getCustomResponse($input);

        if (empty($input) === true)
            $input = $responseBody;

        $input['status'] = '1';

        $errorBody = [
            'code' => $code ?? 'BAD_REQ_ER',
            'description' => $description ?? constant(PublicErrorDescription::class . '::' . $errorCode),
            'field' => '',
            'source' => 'business',
            'step' => null,
            'reason' => str_replace('BAD_REQUEST_', '', $errorCode),
            'metadata' => []
        ];
        switch ($errorCode) {
            case ErrorCode::BAD_REQUEST_CHALLAN_EXPIRED:
            case ErrorCode::BAD_REQUEST_CHALLAN_NOT_FOUND:
                $errorBody['field'] = 'challan_no';
                break;
            case ErrorCode::BAD_REQUEST_CLIENT_CODE_NOT_FOUND:
                $errorBody['field'] = 'client_code';
                break;
            case ErrorCode::BAD_REQUEST_IDENTIFICATION_ID_NOT_FOUND:
                $errorBody['field'] = 'identification_id';
                break;
            case ErrorCode::BAD_REQUEST_AMOUNT_MISMATCH :
                $errorBody['field'] = 'amount';
                break;
            default:
                $errorBody['code'] = $code;

        }
        $input['error'] = $errorBody;

        return $input;
    }

}
