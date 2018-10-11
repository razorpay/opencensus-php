<?php

namespace RZP\Gateway\Enach\Rbl;

use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Payment;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Gateway\Enach\Base;
use RZP\Gateway\Base\Action;
use RZP\Models\Customer\Token;
use RZP\Models\Settlement\Holidays;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Models\Payment\Verify\Action as VerifyAction;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    protected $gateway = 'enach_rbl';

    /**
     * @param array $input
     * @return array|void
     * @throws Exception\GatewayErrorException
     */
    public function authorize(array $input)
    {
        parent::authorize($input);

        $input['gateway'] = $this->getGatewayInput($input);

        $content = [
            Base\Entity::REGISTRATION_DATE => $input['gateway']['next_working_dt']->getTimestamp()
        ];

        try
        {
            $authenticationGateway = $input['authenticate']['gateway'];

            $authenticationResponse = $this->callAuthenticationGateway($input, $authenticationGateway);

            $content[Base\Entity::GATEWAY_REFERENCE_ID] = $authenticationResponse['content']['reference_id'];

            $this->createGatewayPaymentEntity($content, $authenticationGateway, Action::AUTHORIZE);

            unset($authenticationResponse['content']['reference_id']);
        }

        catch (Exception\GatewayErrorException $e)
        {
            $responseArrary = $e->getData();

            $content[Base\Entity::ERROR_CODE] = $responseArrary['code'] ?? null;

            $content[Base\Entity::ERROR_MESSAGE] = $responseArrary['message'] ?? null;

            $content[Base\Entity::GATEWAY_REFERENCE_ID] = $responseArrary['details'] ?? null;

            if ($content[Base\Entity::GATEWAY_REFERENCE_ID] !== null)
            {
                $this->createGatewayPaymentEntity($content, $authenticationGateway, Action::AUTHORIZE);
            }
            else
            {
                $this->trace->info(
                    TraceCode::PAYMENT_AUTH_ESIGN_FAILURE,
                    [
                        'response' => $content
                    ]);
            }

            throw $e;
        }

        return $authenticationResponse;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $enach = $this->repo->findByPaymentIdAndAction(
            $input['payment']['id'],
            Action::AUTHORIZE
        );

        $authenticationGateway = $enach[Base\Entity::AUTHENTICATION_GATEWAY];

        $authResponse = $this->callAuthenticationGateway($input, $authenticationGateway);

        $this->updateGatewayPaymentEntity($enach, $authResponse, false);

        $data = [];

        if ($input['payment'][Payment\Entity::RECURRING_TYPE] === Payment\RecurringType::INITIAL)
        {
            $data = $this->getRecurringData();
        }

        return $data;
    }

    protected function getRecurringData()
    {
        $recurringData = [
            Token\Entity::RECURRING_STATUS => Token\RecurringStatus::INITIATED,
        ];

        return $recurringData;
    }

    protected function getGatewayInput(array $input)
    {
        return [
            'next_working_dt' => $this->getNextWorkingDate($input)
        ];
    }

    // @todo: Fix this using the holiday schedule
    protected function getNextWorkingDate(array $input)
    {
        $currentTs = $input['payment']['created_at'];

        $dt = Carbon::createFromTimestamp($currentTs, Timezone::IST);

        // @todo: Move this to a holiday model
        return Holidays::getNextWorkingDay($dt);
    }

    protected function getGatewayTerminalId()
    {
        if ($this->mode === Mode::LIVE)
        {
            return $this->input['terminal']['gateway_terminal_id'];
        }

        return $this->config['test_terminal_id'];
    }

    public function refund(array $input)
    {
        throw new Exception\RuntimeException(
            'Refund is not implemented');
    }

    public function verify(array $input)
    {
        parent::verify($input);

        // For debit payments, we do not need to verify, since it's file based
        if ($input['payment']['recurring_type'] === Payment\RecurringType::AUTO)
        {
            throw new Exception\PaymentVerificationException(
                [
                    'gateway'    => $this->gateway,
                    'payment_id' => $input['payment']['id'],
                    'action'     => 'verify'
                ],
                null,
                VerifyAction::FINISH
            );
        }

        $enach = $this->repo->findByPaymentIdAndAction(
            $input['payment']['id'],
            Action::AUTHORIZE
        );

        $authenticationGateway = $enach[Base\Entity::AUTHENTICATION_GATEWAY];

        return $this->callAuthenticationGateway($input, $authenticationGateway);
    }

    /**
     * @param array $input
     * @param $authenticationGateway
     * @return array
     */
    protected function callAuthenticationGateway(array $input, $authenticationGateway)
    {
        $esignerGatewayResponse = $this->app['gateway']->call(
            $authenticationGateway,
            $this->action,
            $input,
            $this->mode);

        return $esignerGatewayResponse;
    }

    protected function extractPaymentsProperties($gatewayPayment)
    {
        $response = [];

        // For api based emandate initial payments, if late authorized,
        // we need to update the token status to confirmed
        if (($this->input['payment']['method'] === Payment\Method::EMANDATE) and
            ($this->input['payment'][Payment\Entity::RECURRING_TYPE] === Payment\RecurringType::INITIAL))
        {
            $recurringData = $this->getRecurringData($gatewayPayment);

            $response = array_merge($response, $recurringData);
        }

        return $response;
    }
}
