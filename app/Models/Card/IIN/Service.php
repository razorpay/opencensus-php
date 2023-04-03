<?php

namespace RZP\Models\Card\IIN;

use App;
use RZP\Diag\EventCode;
use RZP\Error\Error;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Models\Card\IIN;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Http\RequestHeader;
use RZP\Models\Payment\Method;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Locale\Core as Locale;
use RZP\Models\Payment\AuthType as AuthType;
use RZP\Models\Feature\Constants as Feature;
use RZP\Models\Card\TokenisedIIN\Entity as TokenEntity;

class Service extends Base\Service
{
    public function addIin($input)
    {
        $iin = (new Entity)->build($input);

        $this->repo->saveOrFail($iin);

        return $iin->toArrayAdmin();
    }

    public function editIin($id, $input)
    {
        $iin = $this->repo->iin->findOrFail($id);

        $this->formatEditInput($iin, $input);

        $iin->edit($input);

        $this->repo->saveOrFail($iin);

        return $iin->toArrayAdmin();
    }

    public function getIinDetails($input)
    {
        $merchant = $this->merchant;

        Locale::setLocale($input, $merchant->getId());

        (new Validator)->validateInput('get_iin_details', $input);

        $iinEntity = $this->repo->iin->find($input['iin']);

        $data['flows'] = $merchant->getPaymentFlows($iinEntity);

        if (isset($input['order_id']) === true)
        {
            $order = $this->repo->order->findByPublicIdAndMerchant($input['order_id'], $this->merchant);

            if ($order->hasOffers() === true)
            {
                $payment = $this->getDummyPayment($order, $iinEntity);

                $applicableOffers = (new Offer\Core)->getApplicableOffersForPayment($order, $payment);

                $data['offers'] = $applicableOffers;
            }
        }

        if (is_null($iinEntity) === false)
        {
            $data['flows']['emi']       = $iinEntity->isEmiAvailable();
            $data['type']               = $iinEntity->getType();
            $data['issuer']             = $iinEntity->getIssuer();
            $data['network']            = $iinEntity->getNetwork();
            $data['cobranding_partner'] = $iinEntity->getCobrandingPartner();

            $app = App::getFacadeRoot();

            $routeName = $app['api.route']->getCurrentRouteName();

            if($routeName == 'payment_get_iin_details')
            {
                $data['country']  = $iinEntity->getCountry();
            }
            /*
             * Need to return emi as available for HDFC Debit Cards because their eligibility is checked
             * in the next step when the user enters his/her phone number.
             */
            if ($iinEntity->isEmiAvailable() or
                (($iinEntity->getIssuer() === Card\Issuer::HDFC) and
                ($iinEntity->getType() === Card\Type::DEBIT)))
            {
                $data['flows']['emi'] = true;
            }
        }

        return $data;
    }

    public function getIssuerDetails($id)
    {
        $iinEntity = $this->repo->iin->findOrFailPublic($id);

        return [
            Entity::ISSUER      => $iinEntity->getIssuer(),
            Entity::ISSUER_NAME => $iinEntity->getIssuerName(),
            Entity::NETWORK     => $iinEntity->getNetwork(),
            Entity::TYPE        => $iinEntity->getType(),
        ];
    }

    public function fetch($id)
    {
        $startTime = microtime(true);

        $this->trace->info(TraceCode::BIN_API, [
            'iin'           => $id,
            'merchant'      => $this->merchant->getId(),
        ]);

        try
        {
            $this->app['diag']->trackIINEvent(EventCode::BIN_API_INITIATION, null, null, $this->getCustomProperties($id));

            $input[Entity::IIN] = $id;

            (new Validator)->validateInput('fetch_iin', $input);

            $token_iin = $this->repo->tokenised_iin->findbyTokenIin($id);

            $token_bin = null;

            if(strlen($id) === 9 && !isset($token_iin)){
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_INVALID_TOKEN_IIN,
                    null,[],'The requested IIN is not a valid token IIN');
            }

            if((isset($token_iin)) && (strlen($token_iin['low_range']) != strlen($id))){

                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_INVALID_IIN,
                    null,[],'The requested IIN is a token IIN & should be 9 digits long.');

            }

            if ($token_iin != null)
            {
                $token_bin = $id ;

                $id = $token_iin[ENTITY::IIN];

            }

            if(!isset($token_iin)){

                $bin = $this->repo->tokenised_iin->findbyrange($id);

                if((isset($bin))){

                    throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_INVALID_IIN,
                        null,[],'The requested IIN is not a valid token IIN');

                }
            }

            $iin = $this->repo->iin->find($id);

            if (isset($iin) === false or $iin->isEnabled() !== true)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_IIN_NOT_EXISTS,
                    null,
                    [
                        'method'  => Method::CARD
                    ]);
            }

            $data = $this->getBasicDetails($iin);

            $data = $this->getTokenDetails($data, $token_iin , $token_bin);

            $data = $this->getPaymentFlows($data, $iin);

            $this->app['diag']->trackIINEvent(EventCode::BIN_API_SUCCESS, $iin, null, $this->getCustomProperties($id));

            (new Metric())->pushIinMetrics(Metric::BIN_API, Metric::SUCCESS, $iin);

            (new Metric())->pushIINResponseTimeMetrics($iin, Metric::BIN_API_RESPONSE_TIME, $startTime);

            return $data;
        }

        catch (\Exception $e)
        {
            $iin = $iin ?? null;

            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::BIN_API_EXCEPTION,
                [
                    'iin'   => $id,
                ]);

            $this->app['diag']->trackIINEvent(EventCode::BIN_API_FAILURE, $iin, $e, $this->getCustomProperties($id));

            (new Metric())->pushIinMetrics(Metric::BIN_API, Metric::FAILED, $iin, $e);

            throw $e;
        }
    }

    public function editIinBulk($input)
    {
        $this->trace->info(TraceCode::IIN_UPDATE_BULK, [
            'input' => $input,
        ]);

        (new Validator())->validateInput('edit_bulk', $input);

        $returnData = [];

        $editPayload = $input['payload'];

        $iins = $input['iins'];

        foreach ($iins as $iin)
        {
            try
            {
                $iinEditResponse = $this->editIin($iin, $editPayload);

                $returnData[$iin] = $iinEditResponse;
            }
            catch (\Exception $e)
            {
                $returnData[$iin] = $e->getMessage();

                $this->trace->error(
                    TraceCode::IIN_UPDATE_FAILED,
                    [
                        'iin'    => $iin,
                        'error'  => $e->getMessage(),
                    ]
                );
            }
        }

        return $returnData;
    }

    public function disableIinFlow($id, $flow)
    {
        $iin = $this->repo->iin->findOrFail($id);

        $iin->disableFlow($flow);

        $this->repo->saveOrFail($iin);

        return $iin->toArrayAdmin();
    }

    public function enableIinFlow($id, $flow)
    {
        $iin = $this->repo->iin->findOrFail($id);

        $iin->enableFlow($flow);

        $this->repo->saveOrFail($iin);

        return $iin->toArrayAdmin();
    }

    public function addIinRange($input)
    {
        $result = (new Import\RangeImporter)->import($input);

        return $result;
    }

    public function importIin($input)
    {
        $result = (new Import\XLSImporter)->import($input);

        return $result;
    }

    public function importCsvIin($job, $input)
    {
        $result = (new Import\XLSImporter)->importWithoutNetwork($input);

        return $result;
    }

    public function generateIinFile($input)
    {
        $result = (new Import\IinGenerator)->generate($input);

        return $result;
    }

    /**
     * Validates if a given IIN is issued by the issuer.
     *
     * @param array $input
     *
     * @return array
     */
    public function validateIinIssuer(array $input): array
    {
        (new Validator)->validateInput('bin_issuer_validation', $input);

        $cardNumber = $input[Entity::NUMBER];

        $response = ['result' => false];

        $iinNumber = intval(substr($cardNumber, 0, 6));

        $iin = $this->repo->iin->find($iinNumber);

        if (empty($iin) === false)
        {
            $response['result'] = true;

            $response['issuer'] = ($iin->getIssuer() === 'HDFC') ? 'HDFC' : 'Others';

            $response['type'] = $iin->getType();
        }
        else
        {
            // This log helps us track any bin validations
            // which we are unable to serve because of our iin database errors.
            $this->trace->info(
                TraceCode::BIN_ISSUER_VALIDATION_FAILED,
                [
                    'iin'    => $iinNumber
                ]);
        }

        return $response;
    }

    public function getIinsList(array $input) : array
    {
        (new Validator)->validateInput('bin_list_validation', $input);

        $iins = [];

        if(isset($input[Entity::FlOW]) === true)
        {
            $iins = $this->getIinsWithMerchantFeatures($input[Entity::FlOW]);
        }

        else if(isset($input[Entity::SUBTYPE]) === true)
        {
            $iins = $this->repo->iin->findIinsBySubType($input[Entity::SUBTYPE]);
        }

        $response['count'] = count($iins);

        $response['iins'] = $iins;

        return $response;
    }

    protected function getIinsWithMerchantFeatures($exposedFlow): array
    {
        $collectiveIins = [];

        foreach (AuthType::$featureToAuthMap[$exposedFlow] as $feature)
        {
            if ($this->merchant->isFeatureEnabled($feature) === true)
            {
                $flowValue = Flow::$flows[Flow::$featureToFlowMappings[$exposedFlow][$feature]];

                $iins = $this->repo->iin->findIinsByFlows($flowValue);

                $collectiveIins =  array_merge($collectiveIins, $iins);
            }
        }

        if (($exposedFlow === Flow::OTP) and
            ($this->merchant->isHeadlessEnabled() === true))
        {
            $iins = $this->repo->iin->findIinsByFlows(Flow::$flows[Flow::HEADLESS_OTP]);

            $collectiveIins =  array_merge($collectiveIins, $iins);
        }

        if (($exposedFlow === Flow::OTP) and
            ($this->merchant->isIvrEnabled() === true))
        {
            $iins = $this->repo->iin->findIinsByFlows(Flow::$flows[Flow::IVR]);

            $collectiveIins =  array_merge($collectiveIins, $iins);
        }

        return array_values(array_unique($collectiveIins));
    }

    public function addorUpdateMultiple($iinMin, $iinMax, $input)
    {
        for ($i = $iinMin ; $i <= $iinMax ; $i++)
        {
            $iin = str_pad($i, 6, '0', STR_PAD_LEFT);

            $this->addOrUpdate($iin, $input);
        }

        $count = $iinMax - $iinMin + 1;

        return $count;
    }

    public function addOrUpdate($id, $input) : array
    {
        $iin = $this->repo->iin->find($id);

        if ($iin === null)
        {
            $input['iin'] = $id;

            return $this->addIin($input);
        }
        else
        {
            return $this->editIin($id, $input);
        }
    }

    public function processRecords(string $type, array $input)
    {
        $response = [];

        // hardcoding for now
        switch ($type)
        {
            case "iin_npci_rupay" :
                $this->processor = new Batch\NpciRupay;
                break;
            case "iin_hitachi_visa":
                $this->processor = new Batch\HitachiVisa;
                break;
            case "iin_mc_mastercard":
                $this->processor = new Batch\McMastercard;
                break;
            default :

        }

        $IinBatchCollection = new Base\PublicCollection;


        $batchId = $this->app['request']->header(RequestHeader::X_Batch_Id, null);

        (new Validator) ->validateBatchId($batchId);

        $idempotentId = null;

        $this->trace->info(
            TraceCode::BATCH_SERVICE_IIN_BULK_REQUEST,
            [
                'batch_id'  => $batchId,
                'input'     => $input,
            ]);

        foreach($input as $entry)
        {
            try
            {
                    $idempotentId = $entry['idempotent_id'] ?? null ;

                    $this->processor->preprocess($entry);

                    $status = $this->processor->process();

                    $data = [
                        'batch_id'        => $batchId,
                        'idempotent_id' => $idempotentId,
                        'status' =>  $status,
                    ];

                    $IinBatchCollection->push($data);


            }
            catch(Exception\BaseException $exception)
            {
                $this->trace->traceException($exception,
                    Trace::ERROR,
                    TraceCode::BATCH_SERVICE_BULK_BAD_REQUEST
                );

                $exceptionData = [
                    'batch_id'        => $batchId,
                    'idempotent_id' => $idempotentId,
                    'status'       => 0,
                    'error'                 => [
                        Error::DESCRIPTION       => $exception->getError()->getDescription(),
                        Error::PUBLIC_ERROR_CODE => $exception->getError()->getPublicErrorCode(),
                    ],
                    Error::HTTP_STATUS_CODE => $exception->getError()->getHttpStatusCode(),
                ];

                $IinBatchCollection->push($exceptionData);
            }
            catch (\Throwable $throwable)
            {
                $this->trace->traceException($throwable,
                    Trace::CRITICAL,
                    TraceCode::BATCH_SERVICE_BULK_EXCEPTION
                );

                $exceptionData = [
                    'batch_id'        => $batchId,
                    'idempotent_id' => $idempotentId,
                    'status'       => 0,
                    'error'                 => [
                        Error::DESCRIPTION       => $throwable->getMessage(),
                        Error::PUBLIC_ERROR_CODE => $throwable->getCode(),
                    ],
                    Error::HTTP_STATUS_CODE => 500,
                ];

                $IinBatchCollection->push($exceptionData);
            }
        }

        $this->trace->info(
            TraceCode::BATCH_SERVICE_IIN_BULK_RESPONSE,
            [
                'batch_id'  => $batchId,
                'output'    => $IinBatchCollection->toArrayWithItems(),
            ]);

        return $IinBatchCollection->toArrayWithItems();
    }

    protected function formatEditInput(Entity $iin, array & $input)
    {
        foreach ($iin->getEditFormattableKeys() as $key)
        {
            if (isset($input[$key]) === true)
            {
                $existingValues = [];

                foreach ($this->getExistingMutatorValues($key, $iin) as $value)
                {
                    $existingValues[$value] = '1';
                }

                $mergedValues = array_merge($existingValues, $input[$key]);

                $this->pushIINFlowEventIfApplicable($existingValues, $mergedValues, $key, $iin);

                $input[$key] = $mergedValues;
            }
        }
    }

    /**
     * @param $mutatorKey
     * @param Entity $iin
     * @return array
     * @throws Exception\LogicException
     */
    protected function getExistingMutatorValues($mutatorKey, Entity $iin) : array
    {
        switch ($mutatorKey)
        {
            case Entity::FLOWS:
                return Flow::getEnabledFlows($iin->getFlows());
            case Entity::MANDATE_HUBS:
                return MandateHub::getEnabledMandateHubs($iin->getMandateHubs());
            default:
                throw new Exception\LogicException('Unknown mutator key : ' . $mutatorKey);
        }
    }

    protected function pushIINFlowEventIfApplicable(array $enabledFlows, $newValues, $key, Entity $iin)
    {
        if ($key !== Entity::FLOWS)
        {
            return;
        }

        if (isset($newValues[Flow::HEADLESS_OTP]) === false)
        {
            return;
        }

        if (isset($enabledFlows[Flow::HEADLESS_OTP]) === true)
        {
            if ($enabledFlows[Flow::HEADLESS_OTP] !== $newValues[Flow::HEADLESS_OTP])
            {
                if ($newValues[Flow::HEADLESS_OTP] === '0')
                {
                    $this->app['diag']->trackIINEvent(
                        EventCode::BIN_HEADLESS_DISABLED,
                        $iin,
                        null,
                        [
                            'iin' => $iin->getIin(),
                            'disable_reason' => 'manual'
                        ]);
                }
            }
        }
        elseif ($newValues[Flow::HEADLESS_OTP] === '1')
        {
            $this->app['diag']->trackIINEvent(
                EventCode::BIN_HEADLESS_ENABLED,
                $iin,
                null,
                [
                    'iin' => $iin->getIin(),
                ]);
        }
    }

    protected function getBasicDetails(Entity $iin)
    {
        $data = [
            Entity::IIN             => $iin->getIin(),
            Constants::ENTITY       => Entity::IIN,
            Entity::NETWORK         => $iin->getNetwork(),
            Entity::TYPE            => $iin->getType(),
            Entity::SUBTYPE         => $iin->getSubType(),
            Entity::CARD_IIN        => "null"
        ];

        if(IIN\IIN::isDomesticBin($iin->getCountry(), 'IN')){
            $data[ENTITY::ISSUER_CODE] = $iin->getIssuer();
            $data[ENTITY::ISSUER_NAME] = $iin->getIssuerName();
            $data[ENTITY::INTERNATIONAL] = false;
        }else{
            $data[ENTITY::ISSUER_CODE] = Entity::UNKNOWN;
            $data[ENTITY::ISSUER_NAME] = Entity::UNKNOWN;
            $data[ENTITY::INTERNATIONAL] = true;
        }
        $this->formatResponses($data);

        return $data;
    }

    protected function getTokenDetails(array $data, $token_iin , $token_bin)
    {
        $iin = $data[ENTITY::IIN];

        if($token_iin != null){

            $data[ENTITY::TOKENISED] = true;

            $data[ENTITY::CARD_IIN] =  $iin;

            $data[ENTITY::IIN] = $token_bin;

        }
        else{

            $data[ENTITY::TOKENISED] = false;

        }

        return $data;
    }

    protected function getPaymentFlows(array $data, Entity $iin)
    {
        $data[Entity::EMI][Entity::AVAILABLE] = $iin->isEmiAvailable();

        $data[Entity::RECURRING][Entity::AVAILABLE] = false;

        $flowsData = $this->merchant->getPaymentFlows($iin);

        $authTypes = [];

        // 3ds will be by default supported
        $authType[Entity::TYPE] = Flow::_3DS;
        array_push($authTypes, $authType);

        if (isset($flowsData) === true)
        {
            if (isset($flowsData[Entity::RECURRING]))
            {
                $data[Entity::RECURRING][Entity::AVAILABLE] = $flowsData[Entity::RECURRING];
            }

            if ((isset($flowsData[Constants::OTP]) === true) and ($flowsData[Constants::OTP] === true))
            {
                $authType[Entity::TYPE] = Flow::OTP;
                array_push($authTypes, $authType);
            }
        }

        $data['authentication_types'] = $authTypes;

        return $data;
    }

    protected function formatResponses(& $data)
    {
        foreach ($data as $key => $value)
        {
            if($value === null or $value === '')
            {
                if ($key === Entity::NETWORK)
                {
                    $data[$key] = Card\NetworkName::UNKNOWN;
                }
                else
                {
                    $data[$key] = Entity::UNKNOWN;
                }
            }
        }
    }

    protected function getCustomProperties($id)
    {
        return  [
            'iin'           => $id,
            'merchant'      => $this->merchant->getId(),
        ];
    }

    public function disableMultipleIINFlows($input)
    {
        if (empty($input['iin']) === false) {
            $flows = $input['flows'] ?? [];

            if ((is_array($flows) === true) && (empty($flows) === false))
            {
                $editInput = [
                    'flows' => []
                ];

                foreach ($flows as $flow)
                {
                    $editInput['flows'][$flow] = '0';
                }

                return $this->editIin($input['iin'], $editInput);
            }
        }

        return [];
    }
}
