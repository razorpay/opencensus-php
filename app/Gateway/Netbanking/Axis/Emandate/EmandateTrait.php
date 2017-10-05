<?php

/*
| This trait adds E Mandate functionality to the Axis Gateway
*/

namespace RZP\Gateway\Netbanking\Axis\Emandate;

use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Constants\Timezone;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Constants\HashAlgo;
use RZP\Gateway\Base\Action;
use RZP\Models\Customer\Token;
use RZP\Gateway\Netbanking\Base;
use RZP\Models\Currency\Currency;
use RZP\Exception\GatewayErrorException;
use RZP\Gateway\Netbanking\Base as Netbanking;
use RZP\Gateway\Netbanking\Axis\Constants as AxisConstants;

trait EmandateTrait
{
    public function handleEmandateCallback(array $input) : array
    {
        $content = $input['gateway'];

        $this->assertPaymentId($input['payment']['id'],
            $content[ResponseFields::TRANS_REF_NO]);

        $this->validateCallbackChecksum($content);

        $gatewayEntity = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment']['id'], Action::AUTHORIZE);

        $attributes = $this->getEmandateCallbackAttributes($content);

        $gatewayEntity->fill($attributes);

        $this->repo->saveOrFail($gatewayEntity);

        // We check the status of the payment, and not the SI registration here
        $this->checkResponseStatus($attributes, $content, StatusCode::SUCCESS);

        $acquirerData = $this->getEmandateAcquirerData($gatewayEntity);

        return $this->getCallbackResponseData($input, $acquirerData);
    }

    protected function getEmandateAcquirerData(Base\Entity $gatewayPayment) : array
    {
        $siStatus = $gatewayPayment->getSIStatus();

        $recurringStatus = ($siStatus === StatusCode::SUCCESS) ?
                            (Token\RecurringStatus::CONFIRMED) :
                            (Token\RecurringStatus::REJECTED);

        $recurringFailureReason = $gatewayPayment->getSIMessage();

        return [
            'acquirer' => [
                Payment\Entity::REFERENCE1         => $gatewayPayment->getBankPaymentId(),
            ],
            Token\Entity::GATEWAY_TOKEN            => $gatewayPayment->getSIToken(),
            Token\Entity::RECURRING_STATUS         => $recurringStatus,
            Token\Entity::RECURRING_FAILURE_REASON => $recurringFailureReason,
        ];
    }

    protected function handleEmandateFlow(array $input) : string
    {
        $this->validateActionInput($input, 'emandateauth');

        $response = $this->createEmandateResponse($input);

        $callbackUrl = $input[RequestFields::RETURN_URL] . '?' . http_build_query($response);

        return $callbackUrl;
    }

    protected function createEmandateResponse(array $input) : array
    {
        $data = [
            ResponseFields::VERSION         => $input[RequestFields::VERSION],
            ResponseFields::CORP_ID         => $input[RequestFields::CORP_ID],
            ResponseFields::TYPE            => $input[RequestFields::TYPE],
            ResponseFields::CUSTOMER_REF_NO => $input[RequestFields::CUSTOMER_REF_NO],
            ResponseFields::CURRENCY        => $input[RequestFields::CURRENCY],
            ResponseFields::AMOUNT          => $input[RequestFields::AMOUNT],
            // TODO: Docs say this is not needed, but docs checksum says it is needed
            ResponseFields::REQUEST_ID      => $input[RequestFields::REQUEST_ID],
            ResponseFields::BANK_REF_NO     => 9999999999,
            ResponseFields::STATUS_CODE     => StatusCode::SUCCESS,
            ResponseFields::REMARKS         => 'Recurring payment successful',
            ResponseFields::TRANS_REF_NO    => $input[RequestFields::REQUEST_ID], // TODO: Confirm this
            ResponseFields::TRANS_EXEC_TIME => Carbon::now(Timezone::IST)->toDateTimeString(), // TODO: Confirm this
            ResponseFields::PAYMENT_MODE    => Constants::PMD,
            ResponseFields::CHECKSUM        => $input[RequestFields::CHECKSUM],
            ResponseFields::MANDATE_NUMBER  => 8888888888,
        ];

        // TODO: Encrypt this

        // for test cases
        $this->content($response, 'emandateauth');

        return $data;
    }

    /**
     * This method creates the recurring payment request data
     * We pass the token ID as customer reference number
     *
     * @param array $input
     * @return array
     */
    protected function getRecurringPaymentData(array $input) : array
    {
        $ppiArray = [
            $input['payment']['id'],
            'max',
            Constants::FREQUENCY_ADHOC,
            '123123123',
            Carbon::now(Timezone::IST)->format('dmy'),
            Carbon::now(Timezone::IST)->addYears(30)->format('dmy'),
            $this->formatAmount($input['payment']['amount']),
        ];

        $data = [
            RequestFields::VERSION         => Constants::VERSION,
            RequestFields::CORP_ID         => $this->getMerchantId(),
            RequestFields::TYPE            => AxisConstants::TYPE,
            RequestFields::REQUEST_ID      => $input['payment']['id'],
            RequestFields::CUSTOMER_REF_NO => $input['token']->getId(),
            RequestFields::CURRENCY        => Currency::INR,
            RequestFields::AMOUNT          => $this->formatAmount($input['payment']['amount']),
            RequestFields::RETURN_URL      => $input['callbackUrl'],
            RequestFields::PRE_POP_INFO    => implode('|', $ppiArray),
            RequestFields::RESERVE_FIELD_1 => AxisConstants::NO_MODIFICATION
        ];

        $data[RequestFields::CHECKSUM] = $this->getChecksum($data);

        return $data;
    }

    protected function getEmandateEntityAttributes(array $input) : array
    {
        return [
            RequestFields::AMOUNT          => $input['payment']['amount'] / 100,
            RequestFields::REQUEST_ID      => $input['payment']['id'],
            RequestFields::CUSTOMER_REF_NO => $input['token']->getId()
        ];
    }

    protected function getEmandateCallbackAttributes(array $content) : array
    {
        return [
            Netbanking\Entity::RECEIVED        => true,
            Netbanking\Entity::STATUS          => $content[ResponseFields::STATUS_CODE],
            Netbanking\Entity::BANK_PAYMENT_ID => $content[ResponseFields::BANK_REF_NO],

            // SI registration specific callback attributes
            Netbanking\Entity::SI_TOKEN        => $content[ResponseFields::CUSTOMER_REF_NO], // TODO: Confirm this
            Netbanking\Entity::SI_STATUS       => $content[ResponseFields::STATUS_CODE], // TODO: Confirm this
            Netbanking\Entity::SI_MSG          => $content[ResponseFields::REMARKS],
        ];
    }

    protected function getHashOfString($str) : string
    {
        return hash(HashAlgo::SHA256, $str);
    }

    /**
     * This method validates that any callback checksum matches that of the
     * expected value based on the algorithm that the bank have shared with us
     *
     * @param array $content
     * @throws GatewayErrorException
     */
    protected function validateCallbackChecksum(array $content)
    {
        $actualChecksum = $this->getChecksum($content);

        if ($actualChecksum !== $content[ResponseFields::CHECKSUM])
        {
            throw new GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_CHECKSUM_MATCH_FAILED,
                null,
                null,
                [
                    'content'    => $content,
                    'payment_id' => $this->input['payment']['id'],
                    'action'     => $this->action,
                ]);
        }
    }

    /**
     * Calculates the checksum attribute for authorize and verify calls
     *
     * @param array $data
     * @return mixed
     */
    protected function getChecksum(array $data) : string
    {
        $arrayToBeHashed = [
            $data[RequestFields::CORP_ID],
            $data[RequestFields::REQUEST_ID],
            $data[RequestFields::CURRENCY],
            $data[RequestFields::AMOUNT],
            $this->getRecSecret(),
        ];

        //
        // Amount is not part of the hash for verify
        //
        if ($this->action === Action::VERIFY)
        {
            unset($arrayToBeHashed[3]);
        }

        return $this->generateHash($arrayToBeHashed);
    }

    protected function getRecSecret(bool $encryption = false) : string
    {
        if ($encryption === true)
        {
            $key = ($this->mode === Mode::TEST) ? 'test_hash_secret_encrec' : 'live_hash_secret_encrec';
        }
        else
        {
            $key = ($this->mode === Mode::TEST) ? 'test_hash_secret_rec' : 'live_hash_secret_rec';
        }

        return $this->config[$key];
    }
}
