<?php

namespace RZP\Gateway\P2p\Upi\Sharp;

use RZP\Error\P2p\ErrorCode;
use RZP\Gateway\P2p\Base\Request;
use RZP\Gateway\P2p\Base\Response;
use RZP\Models\P2p\Mandate\Entity;
use RZP\Gateway\P2p\Upi\Contracts;
use RZP\Models\P2p\Mandate\Action;
use RZP\Gateway\P2p\Upi\ErrorCodes;
use RZP\Gateway\P2p\Upi\Npci\ClAction;
use RZP\Gateway\P2p\Upi\Mock\Scenario;

/**
 * Class MandateGateway
 *
 * @package RZP\Gateway\P2p\Upi\Sharp
 * Mandate Gateway defintion for sharp gateway
 */
class MandateGateway extends Gateway implements Contracts\MandateGateway
{
    /**
     * This is the method to initiate authorize mandate flow
     *
     * @param Response $response
     *
     */
    public function initiateAuthorize(Response $response)
    {
        return $this->initiatePay($response);
    }

    /**
     * This is the method to create credential request
     *
     * @param Response $response
     */
    public function initiatePay(Response $response)
    {
        if ($this->handleFailureScenarios($response, [Scenario::MA101]))
        {
            return;
        }

        $this->cl()->setData($this->input->toArray());

        $request = $this->cl()->getCredentialRequest(ClAction::RECURRING_DEBIT);

        $response->setRequest($request);
    }

    /**
     * This is the method to authorize mandate
     *
     * @param Response $response
     */
    public function authorizeMandate(Response $response)
    {
        if ($this->handleFailureScenarios($response, [Scenario::MA201]))
        {
            return;
        }

        $callback = $this->input->get(Fields::CALLBACK);

        $codes = $this->handleForErrorCode(Scenario::MA201, $callback['f'] ?? null);

        $response->setData([
           'mandate' => [
               'id'                  => $this->input->get('mandate')->get('id'),
               'internal_status'     => array_get($callback, 's', $codes[0]),
               'internal_error_code' => $codes[1],
               'amount'              => $this->input->get('mandate')->get('amount'),
               'start_date'          => $this->input->get('mandate')->get('start_date'),
               'end_date'            => $this->input->get('mandate')->get('end_date'),
               'recurring_type'      => $this->input->get('mandate')->get('recurring_type'),
               'recurring_value'     => $this->input->get('mandate')->get('recurring_value'),
               'recurring_rule'      => $this->input->get('mandate')->get('recurring_rule'),
               'upi'                 => [
                   'network_transaction_id'    => '123456',
                   'gateway_transaction_id'    => 'SRP' . $this->input->get('payer')['id'],
                   'gateway_reference_id'      => 'SRP' . $this->input->get('payer')['id'],
                   'rrn'                       => (string) random_integer(12),
                   'gateway_error_code'        => $codes[2],
                   'gateway_error_description' => $codes[1],
               ],
           ]
       ]);
    }


    /**
     * This is the method to initiate reject response
     * @param Response $response
     */
    public function initiateReject(Response $response)
    {
        if ($this->handleFailureScenarios($response, [Scenario::MA401]))
        {
            return;
        }

        $request = new Request();
        $request->setRedirect($this->getContextDevice()->get(Entity::CREATED_AT));
        $request->setCallback(['f' => __FUNCTION__]);

        $response->setRequest($request);
    }

    // This method with fail the scenario with error code
    protected function handleForErrorCode($scenario, $f)
    {
        if ($this->scenario->is($scenario) === false)
        {
            $map = [
                Action::INITIATE_AUTHORIZE => [
                    'approved',
                    'Mandate is authorized',
                    '00'
                ],
                Action::INITIATE_REJECT => [
                    'rejected',
                    ErrorCode::BAD_REQUEST_PAYMENT_UPI_COLLECT_REQUEST_REJECTED,
                    'ZA',
                ],
            ];

            return ($map[$f] ?? $map[Action::INITIATE_AUTHORIZE]);
        }

        $sub = $this->scenario->getParsedSub($scenario);
        // Remove leading zeros
        $code = ltrim(substr($sub, 0, 1), 0) . substr($sub, 1);

        $internal = ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE;

        if (isset(ErrorCodes::$errorCodeMap[$code]) === true)
        {
            $internal = ErrorCodes::$errorCodeMap[$code];
        }

        $map = [
            'U70' => 'expired',
        ];

        if(isset($map[$code]) === true)
        {
            return [
                $map[$code],
                $internal,
                $code,
            ];
        }

        return [
            'failed',
            $internal,
            $code,
        ];
    }
}
