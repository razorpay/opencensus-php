<?php

namespace RZP\Gateway\P2p\Upi\Sharp;

use Carbon\Carbon;
use RZP\Error\P2p\ErrorCode;
use RZP\Gateway\P2p\Base\Request;
use RZP\Gateway\P2p\Base\Response;
use RZP\Models\P2p\Mandate\Entity;
use RZP\Gateway\P2p\Upi\Contracts;
use RZP\Models\P2p\Mandate\Action;
use RZP\Gateway\P2p\Upi\ErrorCodes;
use RZP\Gateway\P2p\Upi\Npci\ClAction;
use RZP\Gateway\P2p\Upi\Mock\Scenario;
use RZP\Models\P2p\Mandate\UpiMandate\Entity as UpiMandate;

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
            Entity::MANDATE => [
               Entity::ID                   => $this->input->get(Entity::MANDATE)->get(Entity::ID),
               Entity::INTERNAL_STATUS      => array_get($callback, 's', $codes[0]),
               Entity::INTERNAL_ERROR_CODE  => $codes[1],
               Entity::AMOUNT               => $this->input->get(Entity::MANDATE)->get(Entity::AMOUNT),
               Entity::START_DATE           => $this->input->get(Entity::MANDATE)->get(Entity::START_DATE),
               Entity::END_DATE             => $this->input->get(Entity::MANDATE)->get(Entity::END_DATE),
               Entity::RECURRING_TYPE       => $this->input->get(Entity::MANDATE)->get(Entity::RECURRING_TYPE),
               Entity::RECURRING_VALUE      => $this->input->get(Entity::MANDATE)->get(Entity::RECURRING_VALUE),
               Entity::RECURRING_RULE       => $this->input->get(Entity::MANDATE)->get(Entity::RECURRING_RULE),
               Entity::UPI                  => [
                   UpiMandate::NETWORK_TRANSACTION_ID       => '123456',
                   UpiMandate::GATEWAY_TRANSACTION_ID       => 'SRP' . $this->input->get(Entity::PAYER)[Entity::ID],
                   UpiMandate::GATEWAY_REFERENCE_ID         => 'SRP' . $this->input->get(Entity::PAYER)[Entity::ID],
                   UpiMandate::RRN                          => (string) random_integer(12),
                   UpiMandate::GATEWAY_ERROR_CODE           => $codes[2],
                   UpiMandate::GATEWAY_ERROR_DESCRIPTION    => $codes[1],
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


    /**
     * This is the method to initiate pause response
     * @param Response $response
     */
    public function initiatePause(Response $response)
    {
        if ($this->handleFailureScenarios($response, [Scenario::MA501]))
        {
            return;
        }

        $this->cl()->setData($this->input->toArray());

        $request = $this->cl()->getCredentialRequest(ClAction::RECURRING_DEBIT);

        $response->setRequest($request);
    }

    /**
     * This is the method to initiate unpause response
     * @param Response $response
     */
    public function initiateUnPause(Response $response)
    {
        if ($this->handleFailureScenarios($response, [Scenario::MA601]))
        {
            return;
        }

        $this->cl()->setData($this->input->toArray());

        $request = $this->cl()->getCredentialRequest(ClAction::RECURRING_DEBIT);

        $response->setRequest($request);
    }

    /**
     * This is the method to initiate revoke response
     * @param Response $response
     */
    public function initiateRevoke(Response $response)
    {
        if ($this->handleFailureScenarios($response, [Scenario::MA701]))
        {
            return;
        }

        $request = new Request();
        $request->setRedirect($this->getContextDevice()->get(Entity::CREATED_AT));
        $request->setCallback(['f' => __FUNCTION__]);

        $response->setRequest($request);
    }

    /**
     * This is the function to pause the mandate
     */
    public function pause(Response $response)
    {
        if ($this->handleFailureScenarios($response, [Scenario::MA501]))
        {
            return;
        }

        $callback = $this->input->get(Fields::CALLBACK);

        $codes = $this->handleForErrorCode(Scenario::MA501, $callback['f'] ?? 'initiatePause');

        $response->setData([
            Entity::MANDATE => [
               Entity::ID                               => $this->input->get(Entity::MANDATE)->get(Entity::ID),
               Entity::INTERNAL_STATUS                  => array_get($callback, 's', $codes[0]),
               UpiMandate::GATEWAY_ERROR_CODE           => $codes[2],
               UpiMandate::GATEWAY_ERROR_DESCRIPTION    => $codes[1],
               Entity::PAUSE_START                      => $this->input->get(Entity::MANDATE)->get(Entity::PAUSE_START),
               Entity::PAUSE_END                        =>  $this->input->get(Entity::MANDATE)->get(Entity::PAUSE_END),
               Entity::UPI                  => [
                   UpiMandate::NETWORK_TRANSACTION_ID       => '123456',
                   UpiMandate::GATEWAY_TRANSACTION_ID       => 'SRP' . $this->input->get(Entity::PAYER)[Entity::ID],
                   UpiMandate::GATEWAY_REFERENCE_ID         => 'SRP' . $this->input->get(Entity::PAYER)[Entity::ID],
                   UpiMandate::RRN                          => (string) random_integer(12),
                   UpiMandate::GATEWAY_ERROR_CODE           => $codes[2],
                   UpiMandate::GATEWAY_ERROR_DESCRIPTION    => $codes[1],
                ],
           ],
       ]);
    }

    /**
     * This is the function to unpause the mandate
     */
    public function unpause(Response $response)
    {
        if ($this->handleFailureScenarios($response, [Scenario::MA501]))
        {
            return;
        }

        $callback = $this->input->get(Fields::CALLBACK);

        $codes = $this->handleForErrorCode(Scenario::MA501, $callback['f'] ?? 'initiateUnPause');

        $response->setData([
               Entity::MANDATE  => [
                   Entity::ID                               => $this->input->get(Entity::MANDATE)->get(Entity::ID),
                   Entity::PAUSE_START                      => $this->input->get(Entity::MANDATE)->get(Entity::PAUSE_START),
                   Entity::PAUSE_END                        => $this->input->get(Entity::MANDATE)->get(Entity::PAUSE_END),
                   UpiMandate::GATEWAY_ERROR_CODE           => $codes[2],
                   UpiMandate::GATEWAY_ERROR_DESCRIPTION    => $codes[1],
                   Entity::INTERNAL_STATUS                  => array_get($callback, 's', $codes[0]),
                   Entity::INTERNAL_ERROR_CODE              => $codes[1],
                   Entity::UNPAUSED_AT                      => Carbon::now()->getTimestamp(),
                   Entity::UPI                  => [
                       UpiMandate::NETWORK_TRANSACTION_ID       => '123456',
                       UpiMandate::GATEWAY_TRANSACTION_ID       => 'SRP' . $this->input->get(Entity::PAYER)[Entity::ID],
                       UpiMandate::GATEWAY_REFERENCE_ID         => 'SRP' . $this->input->get(Entity::PAYER)[Entity::ID],
                       UpiMandate::RRN                          => (string) random_integer(12),
                       UpiMandate::GATEWAY_ERROR_CODE           => $codes[2],
                       UpiMandate::GATEWAY_ERROR_DESCRIPTION    => $codes[1],
                   ],
               ],
        ]);
    }

    /**
     * This is the function to unpause the mandate
     */
    public function revoke(Response $response)
    {
        if ($this->handleFailureScenarios($response, [Scenario::MA701]))
        {
            return;
        }

        $callback = $this->input->get(Fields::CALLBACK);

        $codes = $this->handleForErrorCode(Scenario::MA701, $callback['f'] ?? 'initiateRevoke');

        $response->setData([
               Entity::MANDATE  => [
                   Entity::ID                               => $this->input->get(Entity::MANDATE)->get(Entity::ID),
                   UpiMandate::GATEWAY_ERROR_CODE           => $codes[2],
                   UpiMandate::GATEWAY_ERROR_DESCRIPTION    => $codes[1],
                   Entity::INTERNAL_STATUS                  => array_get($callback, 's', $codes[0]),
                   Entity::INTERNAL_ERROR_CODE              => $codes[1],
                   Entity::REVOKED_AT                       =>  Carbon::now()->getTimestamp(),
                   Entity::UPI                  => [
                       UpiMandate::NETWORK_TRANSACTION_ID       => '123456',
                       UpiMandate::GATEWAY_TRANSACTION_ID       => 'SRP' . $this->input->get(Entity::PAYER)[Entity::ID],
                       UpiMandate::GATEWAY_REFERENCE_ID         => 'SRP' . $this->input->get(Entity::PAYER)[Entity::ID],
                       UpiMandate::RRN                          => (string) random_integer(12),
                       UpiMandate::GATEWAY_ERROR_CODE           => $codes[2],
                       UpiMandate::GATEWAY_ERROR_DESCRIPTION    => $codes[1],
                   ],
               ],
        ]);
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
                Action::INITIATE_PAUSE => [
                    'paused',
                    'Mandate is paused',
                    '00'
                ],
                Action::INITIATE_UNPAUSE => [
                    'approved',
                    'Mandate is unpaused',
                    '00'
                ],
                Action::INITIATE_REVOKE => [
                    'revoked',
                    'Mandate is Revoked',
                    'RZ'
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
