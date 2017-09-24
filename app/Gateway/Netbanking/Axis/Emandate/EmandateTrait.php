<?php

namespace RZP\Gateway\Netbanking\Axis\Emandate;

use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Constants\HashAlgo;
use RZP\Gateway\Base\Action;
use RZP\Models\Customer\Token;
use RZP\Gateway\Netbanking\Base;
use RZP\Models\Currency\Currency;
use RZP\Gateway\Netbanking\Axis\Constants;

trait EmandateTrait
{
    public function handleEmandateCallback(array $input)
    {
        $content = $input['gateway'];

        $this->assertPaymentId($input['payment']['id'],
            $content[ResponseFields::TRANS_REF_NO]);

        $gatewayEntity = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment']['id'], Action::AUTHORIZE);

        $attrs = $this->getEmandateCallbackAttributes($content);

        $gatewayEntity->fill($attrs);

        $this->repo->saveOrFail($gatewayEntity);

        $this->checkResponseStatus($attrs, $content);

        $acquirerData = $this->getEmandateAcquirerData($gatewayEntity);

        return $this->getCallbackResponseData($input, $acquirerData);
    }

    protected function getEmandateAcquirerData(Base\Entity $gatewayPayment)
    {
        return [
            'acquirer' => [
                Payment\Entity::REFERENCE1  => $gatewayPayment->getBankPaymentId(),
                Token\Entity::GATEWAY_TOKEN => $gatewayPayment->getSiRefId()
            ]
        ];
    }

    protected function getRecurringPaymentData(array $input)
    {
        $data = [
            RequestFields::VERSION         => 'Random Version',
            RequestFields::CORP_ID         => $this->getMerchantId(),
            RequestFields::TYPE            => Constants::TYPE,
            RequestFields::REQUEST_ID      => $input['payment']['id'],
            RequestFields::CUSTOMER_REF_NO => $input['token']->getId(),
            RequestFields::CURRENCY        => Currency::INR,
            RequestFields::AMOUNT          => $input['payment']['amount'] / 100,
            RequestFields::RETURN_URL      => $input['callbackUrl'],
            RequestFields::PRE_POP_INFO    => 'Random PPI value',
            RequestFields::RESERVE_FIELD_1 => Constants::NO_MODIFICATION
        ];

        $data[RequestFields::CHECKSUM] = $this->getChecksum($data);

        return $data;
    }

    protected function getEmandateEntityAttributes(array $input)
    {
        return [
            RequestFields::AMOUNT          => $input['payment']['amount'] / 100,
            RequestFields::REQUEST_ID      => $input['payment']['id'],
            RequestFields::CUSTOMER_REF_NO => $input['token']->getId()
        ];
    }

    protected function getEmandateCallbackAttributes(array $content)
    {
        return [
            'received'        => true,
            'status'          => $content[ResponseFields::STATUS_CODE],
            'bank_payment_id' => $content[ResponseFields::BANK_REF_NO],
        ];
    }

    protected function getHashOfString($str)
    {
        return hash(HashAlgo::SHA256, $str);
    }

    protected function getChecksum(array $data)
    {
        $arrayToBeHashed = [
            $data[RequestFields::CORP_ID],
            $data[RequestFields::REQUEST_ID],
            $data[RequestFields::CURRENCY],
            $data[RequestFields::AMOUNT],
            $this->getRecSecret(),
        ];

        if ($this->action === Action::VERIFY)
        {
            unset($arrayToBeHashed[3]);
        }

        return $this->generateHash($arrayToBeHashed);
    }

    protected function getRecSecret($encryption = false)
    {
        if ($encryption === true)
        {
            if ($this->mode === Mode::TEST)
            {
                return $this->config['test_hash_secret_encrec'];
            }
            else
            {
                return $this->config['live_hash_secret_encrec'];
            }
        }
        else
        {
            if ($this->mode === Mode::TEST)
            {
                return $this->config['test_hash_secret_rec'];
            }
            else
            {
                return $this->config['live_hash_secret_rec'];
            }
        }
    }
}