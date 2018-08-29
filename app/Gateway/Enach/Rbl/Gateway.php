<?php

namespace RZP\Gateway\Enach\Rbl;

use RZP\Error;
use Carbon\Carbon;
use RZP\Exception;
use RZP\Gateway\Netbanking\Icici\RefundFileFields;
use RZP\Models\Payment;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use phpseclib\Crypt\AES;
use RZP\Constants\Timezone;
use RZP\Constants\HashAlgo;
use RZP\Gateway\Enach\Base;
use RZP\Gateway\Base\Action;
use RZP\Models\Customer\Token;
use RZP\Models\Settlement\Holidays;
use RZP\Gateway\Enach\Base\CategoryCode;

class Gateway extends Base\Gateway
{
    protected $gateway = 'enach_rbl';

    public function authorize(array $input)
    {
        parent::authorize($input);

        if (($input['payment']['method'] === 'emandate') and
            ($input['payment']['auth_type'] === 'netbanking'))
        {
            $this->emandateNpciAuth($input);
        }

        $input['gateway'] = $this->getGatewayInput($input);

        $content = [
            Base\Entity::REGISTRATION_DATE => $input['gateway']['next_working_dt']->getTimestamp()
        ];

        try
        {
            $authenticationResponse = $this->callAuthenticationGateway($input);

            $content[Base\Entity::GATEWAY_REFERENCE_ID] = $authenticationResponse['content']['reference_id'];

            $this->createGatewayPaymentEntity($content, 'authorize');

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
                $this->createGatewayPaymentEntity($content, 'authorize');
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

    protected function emandateNpciAuth($input)
    {
        $attributes = $this->getGatewayAttributes($input);

        $this->createGatewayPaymentEntity($attributes, 'authorize');

        $request = $this->getRequest($input);

        $this->traceGatewayPaymentRequest($request, $input);

        return $request;
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

    protected function getRequest($input)
    {
        $secureData = $this->getSecureData($input);

        $checksum = $this->generateHash($secureData);

        $xml = $this->getXmlForNpci($input, $secureData);

        $mid = $this->getMerchantId();

        $bank = $input['bank'];

        $content = [
            'MerchantID' => $mid,
            'MandateReqDoc' => $xml,
            'CheckSumVal' => $checksum,
            'BankID' => $bank,
        ];

        $request = $this->getStandardRequestArray($content, 'post', 'npciauth');

        $request = $this->addHeadersForNpciRequest($request);

        return $request;
    }

    protected function getSecureData($input)
    {
        $nextWorkingDt = $this->getNextWorkingDate($input);

        $finalCollection = Carbon::createFromTimestamp($input['token']->getExpiredAt(), Timezone::IST);

        return [
            RequestNpciTags::DEBTOR_ACCOUNT => $input['token']->getAccountNumber(),
            RequestNpciTags::FIRST_COLLECTION_DATE => $nextWorkingDt->toIso8601String(), //TODO check if this format is correct
            RequestNpciTags::FINAL_COLLECTION_DATE => $finalCollection->toIso8601String(),
            RequestNpciTags::COLLECTION_AMOUNT => '',
            RequestNpciTags::MAX_AMOUNT => $input['token']->getMaxAmount() / 100,
        ];
    }

    protected function getXmlForNpci($input, $secureData)
    {
        $encryptedData = $this->getEncryptedData($secureData);

        $mid = $this->getMerchantId();

        $mcc = $input['terminal']['category'];

        $content = [
            RequestNpciTags::MESSAGE_ID => $this->getMsgId(),
            RequestNpciTags::CREATION_DATE_TIME => Carbon::now()->toIso8601String(),
            RequestNpciTags::MID => $mid,
            RequestNpciTags::CATEGORY_CODE => CategoryCode::getCategoryCodeFromMcc($mcc), //Todo Check if Cat code is this
            RequestNpciTags::UTILITY_CODE => $mid,
            RequestNpciTags::CATEGORY_DESCRIPTION => '', //Todo what to add here?
            RequestNpciTags::NAME => '', //Todo find this value
            RequestNpciTags::MANDATE_ID => $this->getMandateId(),
            RequestNpciTags::SEQUENCE_TYPE => '',
            RequestNpciTags::FREQUENCY => Frequency::ADHOC,
            RequestNpciTags::FIRST_COLLECTION_DATE => $encryptedData[RequestNpciTags::FIRST_COLLECTION_DATE],
            RequestNpciTags::FINAL_COLLECTION_DATE => $encryptedData[RequestNpciTags::FINAL_COLLECTION_DATE],
            RequestNpciTags::COLLECTION_AMOUNT => $encryptedData[RequestNpciTags::COLLECTION_AMOUNT],
            RequestNpciTags::MAX_AMOUNT => $encryptedData[RequestNpciTags::MAX_AMOUNT],
            RequestNpciTags::DEBTOR_NAME => $input['token']->getBeneficiaryName(),
            RequestNpciTags::DEBTOR_ACCOUNT => $encryptedData[RequestNpciTags::DEBTOR_ACCOUNT],
            RequestNpciTags::CREDITOR_NAME => '', //TODO
            RequestNpciTags::CREDITOR_ACCOUNT => '', //TODO
            RequestNpciTags::IFSC_SPONSOR => '' // TODO : is this similar to how its done in digio
        ];
    }

    protected function getEncryptedData($secureData)
    {

    }

    protected function getStringToHash($content, $glue = '|')
    {
        return implode($glue, $content);
    }

    protected function getHashOfString($string)
    {
        return hash(HashAlgo::SHA256, $string);
    }

    public function getMerchantId()
    {
        $mid = $this->getLiveMerchantId();

        if ($this->mode === Mode::TEST)
        {
            $mid = $this->getTestMerchantId();
        }

        return $mid;
    }

    protected function addHeadersForNpciRequest($request)
    {
        //TODO add appropriate values here below
        $headers = [
            'Content-Type'  => 'application/x-www-form-urlencoded'
        ];

        $request['headers'] = $headers;

        return $request;
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
