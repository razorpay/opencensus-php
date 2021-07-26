<?php

namespace RZP\Models\Batch\Processor\Nach\Cancel;

use RZP\Models\Batch;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Customer\Token;
use Razorpay\Trace\Logger as Trace;
use RZP\Exception\BadRequestException;
use RZP\Models\Batch\Processor\Nach\Base as BaseProcessor;

class Base extends BaseProcessor
{
    const TOKEN_ID      = 'token_id';
    const GATEWAY_TOKEN = 'gateway_token';
    const TOKEN_STATUS  = 'token_status';

    protected function processEntry(array &$entry)
    {
        try
        {
            $parsedData = $this->getDataFromRow($entry);

            $this->validateParsedData($parsedData);

            $token = $this->fetchTokenEntity($parsedData);

            $this->updateTokenEntity($token, $parsedData);

            $entry[Batch\Header::STATUS] = Batch\Status::SUCCESS;
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::NACH_ACKNOWLEDGE_RESPONSE_ERROR
            );

            throw $ex;
        }
    }

    protected function fetchTokenEntity($data): Token\Entity
    {
        return $this->repo->token->findOrFailTrashedById($data[self::TOKEN_ID]);
    }

    /**
     * @throws BadRequestException
     */
    protected function updateTokenEntity(Token\Entity $token, array $content)
    {
        $gatewayToken = $content[self::GATEWAY_TOKEN];

        $currentRecurringStatus = $token->getRecurringStatus();

        if (Token\RecurringStatus::isTokenStatusConfirmed($currentRecurringStatus) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_TOKEN_FOR_CANCEL,
                null,
                [
                    'token_id' => $token->getId(),
                    'status'   => $token->getRecurringStatus(),
                ],
                'token is in invalid status to perform this action'
            );
        }

        if ($token->getGatewayToken() !== $gatewayToken)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_ID_TOKEN,
                null,
                [
                    'token_id' => $token->getId(),
                    'have'     => $token->getGatewayToken(),
                    'got'      => $gatewayToken,
                ],
                'token in DB and token from gateway does not match'
            );
        }

        $tokenParams = [
            Token\Entity::RECURRING_STATUS => $content[self::TOKEN_STATUS],
        ];

        (new Token\Core)->updateTokenFromNachGatewayData($token, $tokenParams);

        $this->repo->saveOrFail($token);

        (new Payment\Processor\Processor($token->merchant))->eventTokenStatus($token, $currentRecurringStatus);
    }

    /**
     * @throws BadRequestException
     */
    protected function validateParsedData($data)
    {
        foreach ($data as $key => $value)
        {
            if (empty($value) === true)
            {
                throw new BadRequestException(
                    ErrorCode::GATEWAY_ERROR_INVALID_DATA,
                    $key,
                    [
                        'gateway'       => $this->gateway,
                        'invalid_field' => $key,
                        'field_value'   => $value,
                    ]
                );
            }
        }
    }
}
