<?php

namespace RZP\Models\P2p\Upi;

use RZP\Models\P2p\Vpa;
use RZP\Models\P2p\Base;
use RZP\Models\P2p\Device;
use RZP\Models\P2p\Mandate;
use RZP\Error\P2p\ErrorCode;
use RZP\Models\P2p\Transaction;
use RZP\Models\P2p\Transaction\UpiTransaction;

/**
 * Class Processor
 */
class Processor extends Base\Processor
{
    public function initiateGatewayCallback(array $input): array
    {
        $this->initializeApplicationTrait(Action::INITIATE_GATEWAY_CALLBACK, $input);

        $this->gatewayInput = $this->input;

        return $this->callGateway();
    }

    protected function initiateGatewayCallbackSuccess(array $input): array
    {
        $this->initializeApplicationTrait(Action::INITIATE_GATEWAY_CALLBACK_SUCCESS, $input);

        return $input;
    }

    public function gatewayCallback(array $input): array
    {
        $this->initializeApplicationTrait(Action::GATEWAY_CALLBACK, $input);

        $this->resolveContext();

        $this->gatewayInput = $this->input;

        return $this->callGateway();
    }

    protected function gatewayCallbackSuccess(array $input): array
    {
        $this->initializeApplicationTrait(Action::GATEWAY_CALLBACK_SUCCESS, $input);

        return $this->input->toArray();
    }

    public function initiateReminderCallback(array $input): array
    {
        return $this->initiateReminderCallbackSuccess($input);
    }

    public function initiateReminderCallbackSuccess(array $input): array
    {
        $this->initializeApplicationTrait(Action::INITIATE_REMINDER_CALLBACK_SUCCESS, $input);

        $this->resolveContext();

        return $this->input->toArray();
    }

    public function reminderCallback(array $input): array
    {
        $this->initializeApplicationTrait(Action::REMINDER_CALLBACK, $input);

        return [
            Base\Entity::SUCCESS => true,
        ];
    }

    protected function resolveContext()
    {
        $context =$this->input->get(Base\Entity::CONTEXT);

        switch ($context[Base\Entity::ENTITY])
        {
            case Transaction\Entity::TRANSACTION:
                $this->resolveContextFromTransaction($context[Base\Entity::ACTION]);
                break;

            case Transaction\Entity::CONCERNS:
                $this->resolveContextFromConcerns($context[Base\Entity::ACTION]);
                break;

            case Device\Entity::REGISTER_TOKEN:
                $this->resolveContextFromRegisterToken($context[Base\Entity::ACTION]);
                break;

            case Device\Entity::DEVICE:
                $this->resolveContextFromDevice($context[Base\Entity::ACTION]);
                break;

            case Mandate\Entity::MANDATE:
                $this->resolveContextFromMandate($context[Base\Entity::ACTION]);
                break;

            default:
                throw $this->logicException(ErrorCode::GATEWAY_ERROR_CALLBACK_EMPTY_INPUT);
        }
    }

    public function resolveContextFromTransaction(string $action)
    {
        $context =$this->input->get(Base\Entity::CONTEXT);

        switch ($context[Base\Entity::ACTION])
        {
            case Transaction\Action::INCOMING_COLLECT:

                $payer = $this->input->get(Transaction\Entity::TRANSACTION)[Transaction\Entity::PAYER];

                $this->context()->setHandleAndMode($payer[Vpa\Entity::HANDLE]);

                $device = $this->resolveDeviceFromVpa($payer);

                break;

            case Transaction\Action::INCOMING_PAY:

                $payee = $this->input->get(Transaction\Entity::TRANSACTION)[Transaction\Entity::PAYEE];

                $this->context()->setHandleAndMode($payee[Vpa\Entity::HANDLE]);

                $device = $this->resolveDeviceFromVpa($payee);

                break;

            case Transaction\Action::AUTHORIZE_TRANSACTION_SUCCESS:

                $upi = $this->input->get(Transaction\Entity::UPI);

                $this->context()->setHandleAndMode($upi[Vpa\Entity::HANDLE]);

                $device = $this->resolveDeviceFromUpi($this->input->get(Transaction\Entity::UPI));
        }

        $this->context()->setMerchant($device->merchant);
        $this->context()->setDevice($device);
    }

    public function resolveContextFromConcerns(string $action)
    {
        $context =$this->input->get(Base\Entity::CONTEXT);

        switch ($context[Base\Entity::ACTION])
        {
            case Transaction\Action::CONCERN_STATUS_SUCCESS:

                $concern = $this->input->get(Transaction\Entity::CONCERNS)[0];

                $this->context()->setHandleAndMode($concern[Transaction\Entity::HANDLE]);

                $device = $this->resolveDeviceFromConcern($concern);
        }

        $this->context()->setMerchant($device->merchant);
        $this->context()->setDevice($device);
    }

    /**
     * @param string $action
     *
     * @throws \RZP\Exception\P2p\BadRequestException
     */
    public function resolveContextFromMandate(string $action)
    {
        $context =$this->input->get(Base\Entity::CONTEXT);

        switch ($context[Base\Entity::ACTION])
        {
            case Mandate\Action::INCOMING_COLLECT:
            case Mandate\Action::INCOMING_UPDATE:
            case Mandate\Action::INCOMING_PAUSE:
            case Mandate\Action::MANDATE_STATUS_UPDATE:

                $payer = $this->input->get(Mandate\Entity::MANDATE)[Mandate\Entity::PAYER];

                $this->context()->setHandleAndMode($payer[Vpa\Entity::HANDLE]);

                $device = $this->resolveDeviceFromVpa($payer);

                break;
        }

        $this->context()->setMerchant($device->merchant);
        $this->context()->setDevice($device);
    }

    public function resolveContextFromRegisterToken(string $action)
    {
        $context =$this->input->get(Base\Entity::CONTEXT);

        switch ($context[Base\Entity::ACTION])
        {
            case Device\Action::VERIFICATION_SUCCESS:
                $token = $this->input->get(Device\Entity::REGISTER_TOKEN)['token'];

                $registerToken = (new Device\RegisterToken\Core)->find($token);

                $this->context()->setMerchant($registerToken->merchant);
                $this->context()->setHandleAndMode($registerToken->handle);
        }
    }

    public function resolveContextFromDevice(string $action)
    {
        $context = $this->input->get(Base\Entity::CONTEXT);

        switch ($context[Base\Entity::ACTION])
        {
            case Device\Action::DEVICE_COOLDOWN_COMPLETED:

                $this->context()->setHandleAndMode($context[Device\Entity::HANDLE]);

                $device = (new Device\Core())->find($context[Device\Entity::ID], false);

                $this->context()->setMerchant($device->merchant);

                $this->context()->setDevice($device, true);

                break;
        }
    }

    public function resolveDeviceFromVpa(array $input)
    {
        $vpa = (new Vpa\Core)->findByUsernameHandle($input, true);

        return $vpa->device;
    }

    public function resolveDeviceFromUpi(array $input)
    {
        $upis = (new Transaction\Core)->findAllUpi($input);

        if ($upis->count() === 1)
        {
            $transaction = $this->input->get(Transaction\Entity::TRANSACTION);
            $upi         = $this->input->get(Transaction\Entity::UPI);

            // Transaction ID will not be same as UPI transaction ID
            // in case of incoming collect expire callback.
            if ((isset($transaction[Transaction\Entity::ID]) === false)
                or ($transaction[Transaction\Entity::ID] !== $upis->first()->getTransactionId()))
            {
                $transaction[Transaction\Entity::ID]        = $upis->first()->getTransactionId();
                $upi[UpiTransaction\Entity::TRANSACTION_ID] = $upis->first()->getTransactionId();
                $upi[Transaction\Entity::TRANSACTION][UpiTransaction\Entity::ID] = $upis->first()->getTransactionId();

                $this->input->put(Transaction\Entity::TRANSACTION, $transaction);
                $this->input->put(Transaction\Entity::UPI, $upi);
            }

            return $upis->first()->device;
        }

        throw $this->logicException('Count of UPI should be exactly one', $input);
    }

    public function resolveDeviceFromConcern(array $input)
    {
        $concern = (new Transaction\Concern\Core)->find($input[Transaction\Concern\Entity::ID]);

        if ($concern->getGatewayReferenceId() !== $input[Transaction\Concern\Entity::GATEWAY_REFERENCE_ID])
        {
            throw $this->logicException('Gateway reference id should be same');
        }

        return $concern->device;
    }

    public function fetchVpaFromTransaction(string $type)
    {
        $plucked = $this->input->get(Transaction\Entity::TRANSACTION)[$type];

        $vpa = (new Vpa\Core)->findByUsernameHandle($plucked);

        return $vpa;
    }

    protected function getGateway()
    {
        if ($this->context()->getHandle() instanceof Vpa\Handle\Entity)
        {
            return parent::getGateway();
        }

        if($this->input->get(Base\Entity::GATEWAY) != null)
        {
            return $this->input->get(Base\Entity::GATEWAY);
        }

        return $this->gatewayInput->get(Base\Entity::GATEWAY);
    }
}
