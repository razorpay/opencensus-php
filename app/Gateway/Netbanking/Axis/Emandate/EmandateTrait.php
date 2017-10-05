<?php

/*
| This trait adds E Mandate functionality to the Axis Gateway
*/

namespace RZP\Gateway\Netbanking\Axis\Emandate;

use RZP\Constants\HashAlgo;
use RZP\Constants\Mode;
use RZP\Constants\Timezone;
use RZP\Error\ErrorCode;
use RZP\Exception\GatewayErrorException;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Base\AESCrypto;
use RZP\Gateway\Netbanking\Axis\Constants as AxisConstants;
use RZP\Gateway\Netbanking\Base as Netbanking;
use RZP\Models\Currency\Currency;
use RZP\Models\Customer\Token;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;

use Carbon\Carbon;
use phpseclib\Crypt\AES;

trait EmandateTrait
{
    public function handleEmandateCallback(array $input) : array
    {
        $content = $input['gateway'];

        $content = $this->getDecryptedData($content[ResponseFields::DATA]);

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

    protected function getEmandateAcquirerData(Netbanking\Entity $gatewayPayment) : array
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
            Carbon::now(Timezone::IST)->format('m/d/y'),
            Carbon::now(Timezone::IST)->addYears(30)->format('m/d/y'),
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

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_REQUEST,
            [
                'gateway'         => $this->gateway,
                'payment_id'      => $input['payment']['id'],
                'data_before_enc' => $data
            ]
        );

        $content = [
            RequestFields::DATA => $this->getEncryptedData($data)
        ];

        return $content;
    }

    public function getEncryptedData(array $data): string
    {
        return base64_encode(
            $this->getEncryptor()->encryptString(
                urldecode(http_build_query($data))
            )
        );
    }

    public function getDecryptedData(string $input): array
    {
        $decrypted = $this->getEncryptor()->decryptString(base64_decode($input));

        parse_str($decrypted, $output);

        return $output;
    }

    public function getEncryptor()
    {
        $aes = new AESCrypto(AES::MODE_CBC, $this->getRecSecret(true));

        $aes->setKeyLength(256);

        return $aes;
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
            $data[RequestFields::CUSTOMER_REF_NO],
            $data[RequestFields::AMOUNT],
            $this->getRecSecret(),
        ];

        // Amount is not part of the hash for verify
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
