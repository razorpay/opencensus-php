<?php

namespace RZP\Gateway\P2p\Upi\Sharp;

use RZP\Models\P2p\Vpa;
use RZP\Error\P2p\ErrorCode;
use RZP\Gateway\P2p\Base\Request;
use RZP\Gateway\P2p\Base\Response;
use RZP\Gateway\P2p\Upi\Contracts;
use RZP\Gateway\P2p\Upi\ErrorCodes;
use RZP\Models\P2p\Transaction\Action;
use RZP\Models\P2p\Transaction\Entity;
use RZP\Gateway\P2p\Upi\Mock\Scenario;
use RZP\Gateway\P2p\Upi\Npci\ClAction;
use RZP\Models\P2p\Transaction\Concern;

class TransactionGateway extends Gateway implements Contracts\TransactionGateway
{
    public function initiatePay(Response $response)
    {
        if ($this->handleFailureScenarios($response, [Scenario::TR101]))
        {
            return;
        }

        $this->cl()->setData($this->input->toArray());

        $request = $this->cl()->getCredentialRequest(ClAction::DEBIT);

        $response->setRequest($request);
    }

    public function initiateCollect(Response $response)
    {
        if ($this->handleFailureScenarios($response, [Scenario::TR201]))
        {
            return;
        }

        $request = new Request();
        $request->setRedirect($this->getContextDevice()->get(Entity::CREATED_AT));
        $request->setCallback(['f' => __FUNCTION__]);

        $response->setRequest($request);
    }

    public function fetchAll(Response $response)
    {

    }

    public function fetch(Response $response)
    {

    }

    public function initiateAuthorize(Response $response)
    {
        $type = $this->input->get(Entity::TRANSACTION)->get(Entity::TYPE);
        $pending = $this->input->get(Entity::TRANSACTION)->get(Entity::IS_PENDING_COLLECT);

        if ($type === 'pay' or $pending === true)
        {
            return $this->initiatePay($response);
        }

        return $this->initiateCollect($response);
    }

    public function authorizeTransaction(Response $response)
    {
        if ($this->handleFailureScenarios($response, [Scenario::TR301]))
        {
            return;
        }

        $callback = $this->input->get(Fields::CALLBACK);
        $codes = $this->handleForErrorCode(Scenario::TR302, $callback['f'] ?? null);

        $response->setData([
            'transaction' => [
                'id'                            => $this->input->get('transaction')->get('id'),
                'internal_status'               => array_get($callback, 's', $codes[0]),
                'internal_error_code'           => $codes[1],
                'amount'                        => $this->input->get('transaction')->get('amount'),
            ],
            'upi' => [
                'transaction_id'                => $this->input->get('upi')->get('transaction_id'),
                'network_transaction_id'        => $this->input->get('upi')->get('network_transaction_id'),
                'gateway_transaction_id'        => 'SRP' . $this->input->get('transaction')->get('payer_id'),
                'gateway_reference_id'          => 'SRP' . $this->input->get('transaction')->get('payer_id'),
                'rrn'                           => (string) random_integer(12),
                'gateway_error_code'            => $codes[2],
                'gateway_error_description'     => $codes[1],
            ],
        ]);
    }

    public function initiateReject(Response $response)
    {
        if ($this->handleFailureScenarios($response, [Scenario::TR401]))
        {
            return;
        }

        $request = new Request();
        $request->setRedirect($this->getContextDevice()->get(Entity::CREATED_AT));
        $request->setCallback(['f' => __FUNCTION__]);

        $response->setRequest($request);
    }

    public function reject(Response $response)
    {
        $transactionId = 'SRP' . str_random();

        $response->setData([
            'transaction' => [
                'id'    => $this->input->get('transaction')->get('id'),
            ],
            'success' => true,
        ]);
    }

    public function raiseConcern(Response $response)
    {
        if ($this->handleFailureScenarios($response, [Scenario::TR501]))
        {
            return;
        }

        $sharpId = 'SharpQuery' . $this->input->get(Entity::CONCERN)[Entity::ID];

        $codes = $this->handleForErrorCode(Scenario::TR502, __FUNCTION__);

        $respCode = ($codes[0] === 'completed' ? 'success' : $codes[0]);
        $internal = ($codes[0] === 'pending' ? 'initiated' : 'closed');

        $output = [
            Concern\Entity::ID                      => $this->input->get(Entity::CONCERN)[Entity::ID],
            Concern\Entity::TRANSACTION_ID          => $this->input->get(Entity::CONCERN)['transaction_id'],
            Concern\Entity::GATEWAY_REFERENCE_ID    => $sharpId,
            Concern\Entity::INTERNAL_STATUS         => $internal,
            Concern\Entity::RESPONSE_CODE           => $respCode,
            Concern\Entity::RESPONSE_DESCRIPTION    => $codes[1] ?? '',
        ];

        $output[Entity::GATEWAY_DATA] = [
            'id'    => $sharpId,
        ];

        $response->setData([Entity::CONCERN => $output]);
    }

    public function concernStatus(Response $response)
    {
        if ($this->handleFailureScenarios($response, [Scenario::TR601]))
        {
            return;
        }

        $sharpId = 'SharpQuery' . $this->input->get(Entity::CONCERN)[Entity::ID];

        $codes = $this->handleForErrorCode(Scenario::TR602, __FUNCTION__);

        $respCode = ($codes[0] === 'completed' ? 'success' : $codes[0]);
        $internal = ($codes[0] === 'pending' ? 'initiated' : 'closed');

        $output = [
            Concern\Entity::ID                      => $this->input->get(Entity::CONCERN)[Entity::ID],
            Concern\Entity::TRANSACTION_ID          => $this->input->get(Entity::CONCERN)['transaction_id'],
            Concern\Entity::GATEWAY_REFERENCE_ID    => $sharpId,
            Concern\Entity::INTERNAL_STATUS         => $internal,
            Concern\Entity::RESPONSE_CODE           => $respCode,
            Concern\Entity::RESPONSE_DESCRIPTION    => $codes[1] ?? '',
        ];

        $output[Entity::GATEWAY_DATA] = [
            'id'    => $sharpId,
        ];

        $response->setData([Entity::CONCERN => $output]);
    }

    // This method with fail the scenario with error code
    protected function handleForErrorCode($scenario, $f)
    {
        if ($this->scenario->is($scenario) === false)
        {
            $map = [
                Action::INITIATE_PAY => [
                    'completed',
                    'Transaction is completed',
                    '00'
                ],
                Action::INITIATE_COLLECT => [
                    'created',
                    'Transaction request sent',
                    '00',
                ],
                Action::INITIATE_REJECT => [
                    'rejected',
                    ErrorCode::BAD_REQUEST_PAYMENT_UPI_COLLECT_REQUEST_REJECTED,
                    'ZA',
                ],
                Action::RAISE_CONCERN => [
                    'pending',
                    ErrorCode::GATEWAY_ERROR_TRANSACTION_PENDING,
                    'RB',
                ],
                Action::CONCERN_STATUS => [
                    'completed',
                    'Transaction is completed',
                    'RB',
                ],
            ];

            return ($map[$f] ?? $map[Action::INITIATE_PAY]);
        }

        $sub    = $this->scenario->getParsedSub($scenario);
        // Remove leading zeros
        $code   = ltrim(substr($sub, 0, 1), 0) . substr($sub, 1);

        $internal = ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE;

        if (isset(ErrorCodes::$errorCodeMap[$code]) === true)
        {
            $internal =  ErrorCodes::$errorCodeMap[$code];
        }

        $map = [
            'pending'   => ['RB', 'BT', '01'],
            'expired'   => ['U69'],
            'rejected'  => ['ZA'],
        ];

        // Now check for special errors
        foreach ($map as $status => $codes)
        {
            if (in_array($code, $codes) === true)
            {
                return [
                    $status,
                    $internal,
                    $code,
                ];
            }
        }

        return [
            'failed',
            $internal,
            $code,
        ];
    }
}
