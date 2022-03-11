<?php

namespace RZP\Gateway\P2p\Upi\Sharp;

use RZP\Gateway\P2p\Base\Response;
use RZP\Gateway\P2p\Upi\Contracts;
use RZP\Gateway\P2p\Upi\Mock\Scenario;
use RZP\Gateway\P2p\Upi\Npci\ClAction;

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
}
