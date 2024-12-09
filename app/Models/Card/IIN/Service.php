<?php

namespace RZP\Models\Card\IIN;

use App;
use RZP\Diag\EventCode;
use RZP\Error\Error;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Card;
use RZP\Models\Card\IIN;
use RZP\Error\ErrorCode;
use RZP\Constants\Environment;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Payment\Processor\HeadlessOtp;
use RZP\Services\BinService;
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

    public function editIin($id, $input, $editSource='manual', $editReason='manual')
    {
        $iin = $this->repo->iin->findOrFailAPIEntity($id);

        if(($iin['flows'] > 511 && $iin['flows'] < 1024) && $input['flows']['headless_otp'] === "1"){
            $this->trace->info(TraceCode::FORBIDDEN_HEADLESS_BIN, [
                'headless_forbidden_enabled'     =>  $input['flows']['headless_otp'],
            ]);
            return [];
        }

        $this->formatEditInput($iin, $input, $editSource, $editReason);

        $iin->edit($input);

        $this->repo->saveOrFail($iin);

        //adding bin service update for dual write
        if ($this->shouldDualWrite() === true)
        {
            $this->updateBinServiceData($iin, $input);
        }


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
            $data['dcc_blacklisted']      = $iinEntity->isDCCBlacklisted();

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

                // adding bellow MID level check as temporary solution for nykaa and with bin service it will be automatically handled
                // https://razorpay.slack.com/archives/C7WEGELHJ/p1732601001804189?thread_ts=1732275937.700689&cid=C7WEGELHJ
                if($this->merchant->getId() != "4uObL8AHBqFNnP") {
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_INVALID_IIN,
                        null,[],'The requested IIN is a token IIN & should be 9 digits long.');
                }
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

        //adding bin service update for dual write
        if ($this->shouldDualWrite() === true) {
            $this->updateBinServiceFlows($iin, $flow, "disable");
        }
        $iin->disableFlow($flow);

        $this->repo->saveOrFail($iin);

        return $iin->toArrayAdmin();
    }

    public function enableIinFlow($id, $flow)
    {
        $iin = $this->repo->iin->findOrFail($id);

        //adding bin service update for dual write
        if ($this->shouldDualWrite() === true) {
            $this->updateBinServiceFlows($iin, $flow, "enable");
        }
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

    protected function formatEditInput(Entity $iin, array & $input, $editSource, $editReason)
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

                $this->pushIINFlowEventIfApplicable($existingValues, $mergedValues, $key, $iin, $editSource, $editReason);

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

    protected function pushIINFlowEventIfApplicable(array $enabledFlows, $newValues, $key, Entity $iin, $editSource, $editReason)
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
                    if($$editReason === 'manual'){
                        $this->app['diag']->trackIINEvent(
                            EventCode::BIN_HEADLESS_DISABLED,
                            $iin,
                            null,
                            [
                                'iin' => $iin->getIin(),
                                'disable_reason' => $editSource,
                                'reason_code' => $editReason
                            ]);
                    } else {
                        $this->app['diag']->trackIINEvent(
                            EventCode::BIN_HEADLESS_DISABLED,
                            $iin,
                            null,
                            [
                                'iin' => $iin->getIin(),
                                'disable_reason' => $editSource,
                                'reason_code' => $editReason
                            ]);
                    }
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
                    'enabled_via' => $editSource
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
                if(empty($input['id']) === false){
                    $editReason = $input['id'];
                } else {
                    $editReason = "UNPROCESSABLE_ENTITY";
                }
                $editSource = 'automatic';
                return $this->editIin($input['iin'], $editInput, $editSource, $editReason);
            }
        }

        return [];
    }

    public function enableMultipleIINFlows($input)
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
                    $editInput['flows'][$flow] = '1';
                }

                $editSource = 'cron';

                return $this->editIin($input['iin'], $editInput, $editSource);
            }
        }

        return [];
    }

    public function updateBinServiceFlows($iin, $input, $type)
    {
        $binService = (new BinService());
        $flows = $iin->getFlows();
        $existingFlowData = Flow::getEnabledFlows($flows);
        // Disable flows present in $input

        if($type === "disable"){
            $this->disableFlows($existingFlowData, $input);
        } else {
            $this->enableFlows($existingFlowData, $input);
        }

        $url = "iins/".$iin['iin']."/features";
        $enabled = $iin->isEnabled();
        $locked = $iin->isLocked();
        $emi = $iin->isEmiAvailable();
        $recurring = $iin->isRecurring();
        $request = [
            'enabled' => $enabled,
            'locked' => $locked,
            'emi' => $emi,
            'recurring' => $recurring,
            'features' => $existingFlowData,
        ];

        $namespace = "RZP/".strtoupper($iin->getCountry())."/".strtoupper($iin->getType());
        $binService->sendRequest($url, 'PATCH', $request, $namespace, BinService::UPDATE_IIN);
    }

    public function disableFlows(&$existingFlowData, $flowsToDisable): void
    {
        $keyToRemove = array_search($flowsToDisable, $existingFlowData);
        if ($keyToRemove !== false) {
            unset($existingFlowData[$keyToRemove]);
        }
    }

    public function enableFlows(&$existingFlowData, $flowsToEnable): void
    {
        if (!in_array($flowsToEnable, $existingFlowData)) {
                $existingFlowData[] = $flowsToEnable;
        }
    }

    public function updateBinServiceData($iin, $input)
    {
        $binService = (new BinService());

        $request = $this->formatRequest($iin, $input);

        $url = "iins/".$iin['iin'];

        $namespace = "RZP/".strtoupper($request["country"])."/".strtoupper($request["type"]);

        $binService->sendRequest($url, 'PATCH', $request, $namespace, BinService::UPDATE_IIN);
    }

    private function formatRequest($iin, $input)
    {
        $originalIIN = $this->repo->iin->findOrFail($iin['iin']);
        $request = [
            "iin" => $iin['iin'] ?? "",
            "network" => $input["network"] ?? $originalIIN["network"],
            "issuer" => $input["issuer"] ?? $originalIIN["issuer"],
            "issuerName" => $input["issuer_name"] ?? $originalIIN["issuer_name"],
            "country" => $input["country"] ?? $originalIIN["country"],
            "type" => $input["type"] ?? $originalIIN["type"],
//            "productCode" => $input["type"] ?? $originalIIN["product_code"],
            "subType" => $input["sub_type"] ?? $originalIIN["sub_type"],
            "messageType" => $input["message_type"] ?? $originalIIN["message_type"],
            "category" => $input["category"] ?? $originalIIN["category"],
            "iinLength" => strlen($iin['iin']),
            "trivia" => $iin['trivia'],
            "features" => $this->getFlows($input, $originalIIN)
        ];

        $mandates = [];
        if (empty($input['mandate_hubs']) === true){
            $input['mandate_hubs'] = $originalIIN['mandate_hubs'];
        }
        foreach ($input['mandate_hubs'] as $key => $value) {
            if ($value === "1") {
                $mandates[] = $key;
            }
        }
        $request['mandate_hubs'] = $mandates;

        return $request;
    }

    private function getFlows($input, $originalIIN)
    {
        $features = [];
        if(empty($input['flows']) === true) {
            $features = Flow::getEnabledFlows($originalIIN['flows']);
            $this->trace->info(TraceCode::BIN_SERVICE_REQUEST, [
                'message' => 'Empty Features',
                'features' => $features
            ]);
        } else {
            foreach ($input['flows'] as $key => $value) {
                if ($value === "1") {
                    $features[] = $key;
                }
            }
        }

        if (isset($input['recurring']) === false){
            $input['recurring'] = $originalIIN['recurring'];
        }
        if (isset($input['enabled']) === false){
            $input['enabled'] = $originalIIN['enabled'];
        }
        if (isset($input['emi']) === false){
            $input['emi'] = $originalIIN['emi'];
        }
        if (isset($input['locked']) === false){
            $input['locked'] = $originalIIN['locked'];
        }
        if ($input['recurring'] == '1' || $input['recurring']) {
            $data['recurring'] = true;
        } else {
            $data['recurring'] = false;
        }
        if ($input['enabled'] == '1' || $input['enabled']) {
            $data['enabled'] = true;
        } else {
            $data['enabled'] = false;
        }
        if ($input['emi'] == '1' || $input['emi']) {
            $data['emi'] = true;
        } else {
            $data['emi'] = false;
        }
        if ($input['locked'] == '1' || $input['locked']) {
            $data['locked'] = true;
        } else {
            $data['locked'] = false;
        }

        $data['features'] = $features;
        return $data;
    }

    public function shouldDualWrite(): bool
    {
        $variant = $this->app->razorx->getTreatment(UniqueIdEntity::generateUniqueId(),RazorxTreatment::ALLOW_BIN_SERVICE_DUAL_WRITE, $this->mode);

        $this->trace->info(TraceCode::BIN_SERVICE_DUAL_WRITE_VARIANT, [
            'razorx_variant' => $variant,
            'mode' => $this->mode,
            'env' => $this->app['env'],
        ]);

        if (strtolower($variant) === 'on')
        {
            return true;
        }
        return false;
    }

    public function compareBinServiceEntityAndApiServiceEntity($apiServiceEntity, $binServiceEntity, $extraTraceData)
    {
        $binServiceEntity = $this->convertBinServiceFieldsToHexForComparison($binServiceEntity);

        // matches the api service IIN entity fields with bin service entity
        foreach (Constants::COMPARABLE_FIELDS_BETWEEN_IIN_ENTITY_AND_BIN_SERVICE as $field)
        {
            $apiServiceEntityValue = "";
            $binServiceEntityValue = "";

            if (isset($apiServiceEntity[$field]) && isset($binServiceEntity[$field]))
            {
                if ($apiServiceEntity[$field] !== $binServiceEntity[$field])
                {
                        $apiServiceEntityValue = $apiServiceEntity[$field];
                        $binServiceEntityValue  = $binServiceEntity[$field];
                }
            }
            else
            {
                if (isset($apiServiceEntity[$field]) || isset($binServiceEntity[$field]))
                {
                    $apiServiceEntityValue = $apiServiceEntity[$field] ?? "";
                    $binServiceEntityValue  = $binServiceEntity[$field] ?? "";
                }
            }

            if ($apiServiceEntityValue !== $binServiceEntityValue)
            {
                $this->trace->info(TraceCode::API_BIN_SERVICE_IIN_DATA_MISMATCH, [
                    'field'                  => $field,
                    'iin'                   => $extraTraceData['iin'],
                    'method'                => $extraTraceData['method_name'],
                    'apiServiceEntityValue' => $apiServiceEntityValue,
                    'binServiceEntityValue' => $binServiceEntityValue,
                    'api_entity_country'    => $apiServiceEntity['country'],
                    'bin_entity_country'    => $binServiceEntity['country'],
                    'mapped_iin'            => $binServiceEntity['mappedIin'] ?? "",
                ]);
            }
        }
    }

    public function convertBinServiceFieldsToHexForComparison($entity)
    {
        $flows = $entity[Entity::FLOWS];

        $entity[Entity::FLOWS] = !empty($flows) ? Flow::getHexValue($flows) : 0;

        $mandateHubs = $entity[Entity::MANDATE_HUBS];

        $entity[Entity::MANDATE_HUBS] = !empty($mandateHubs) ? MandateHub::getHexValue($mandateHubs) : 0;

        return $entity;
    }

    public function transformBinServiceEntityToApiServiceEntity($entity)
    {
        foreach (Constants::BIN_SERVICE_ENTITY_TO_API_IIN_ENTITY_KEY_MAPPING as $binServiceKeyName => $apiServiceKeyName)
        {
            $entity[$apiServiceKeyName] = $entity[$binServiceKeyName];

            unset($entity[$binServiceKeyName]);
        }

        if(isset($entity[Constants::FEATURES]) and !empty($entity[Constants::FEATURES]))
        {
            // emi, locked, enabled, recurring are now part of features objects in bin service entity
            $features = $entity[Constants::FEATURES];

            // flows is now part of features.features object in bin service entity
            $flows = $features[Constants::FEATURES];

            unset($entity[Constants::FEATURES]);

            unset($features[Constants::FEATURES]);

            foreach($flows as $flow)
            {
                $flows[$flow] = '1';
            }

            $entity[Entity::FLOWS] = $flows;

            foreach($features as $key => $value)
            {
                $entity[$key] = isset($value) ? $value : false;
            }
        }

        $mandateHubs = $entity[Entity::MANDATE_HUBS];

        foreach($mandateHubs as $mandateHub)
        {
            $mandateHubs[$mandateHub] = '1';
        }

        $entity[Entity::MANDATE_HUBS] = $mandateHubs;

        foreach($entity as $key => $value)
        {
            $entity[$key] = $this->transformEmptyStringToNullIfApplicable($value);
        }

        // otp_read is deprecated in bin service, making it default as false to avoid key read
        // failures in code
        $entity[Entity::OTP_READ] = false;

        return $entity;
    }

    private function transformEmptyStringToNullIfApplicable($val)
    {
        if ($val === '')
        {
            return null;
        }

        return $val;
    }

    public function shouldReadFromBinServiceInShadowMode() : bool
    {
        if (Environment::isTestingEnvironment($this->app['env']) === true ||
            Environment::isEnvironmentQA($this->app['env']) === true ||
            Environment::isEnvironmentItf($this->app['env']) === true )
        {
            return false;
        }

        $variant = $this->app->razorx->getTreatment(UniqueIdEntity::generateUniqueId(), RazorxTreatment::ALLOW_BIN_SERVICE_SHADOW_READS, $this->mode);

        if (strtolower($variant) === 'on')
        {
            return true;
        }

        return false;
    }

    public function shouldReadBinServiceInPrimaryMode(string $iin) : bool
    {
        if (Environment::isTestingEnvironment($this->app['env']) === true ||
            Environment::isEnvironmentQA($this->app['env']) === true ||
            Environment::isEnvironmentItf($this->app['env']) === true)
        {
            return false;
        }

        $variant = $this->app->razorx->getTreatment($iin, RazorxTreatment::BIN_SERVICE_IIN_FETCH_PRIMARY, $this->mode);

        if (strtolower($variant) === 'on')
        {
            return true;
        }

        return false;
    }
}
