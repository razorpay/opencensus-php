<?php

namespace RZP\Gateway\Enach\Rbl;

use RZP\Error;
use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Payment;
use RZP\Constants\Mode;
use phpseclib\Crypt\AES;
use RZP\Constants\Timezone;
use RZP\Gateway\Enach\Base;
use RZP\Gateway\Base\Action;
use RZP\Models\Customer\Token;
use RZP\Models\Settlement\Holidays;

class Gateway extends Base\Gateway
{
    protected $gateway = 'enach_rbl';

    public function authorize(array $input)
    {
        parent::authorize($input);

        $input['gateway'] = $this->getGatewayInput($input);

        $content = [
            Base\Entity::REGISTRATION_DATE => $input['gateway']['next_working_dt']->getTimestamp()
        ];

        $this->createGatewayPaymentEntity($content, 'authorize');

        return $this->callAuthenticationGateway($input);
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $authResponse = $this->callAuthenticationGateway($input);

        $enach = $this->repo->findByPaymentIdAndAction(
            $input['payment']['id'],
            Action::AUTHORIZE
        );

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
        throw new Exception\RuntimeException(
            'Verify is not implemented');
    }

    protected function callAuthenticationGateway(array $input)
    {
        return $this->app['gateway']->call(
            Payment\Gateway::ESIGNER_DIGIO,
            $this->action,
            $input,
            $this->mode);
    }
}
