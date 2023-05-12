<?php

namespace RZP\Models\Merchant\InternationalIntegration;

use Carbon\Carbon;
use Razorpay\Trace\Logger;
use RZP\Constants\Mode;
use RZP\Constants\Timezone;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\BadRequestException;
use RZP\Exception\ServerErrorException;
use RZP\Jobs\CrossBorderCommonUseCases;
use RZP\Models\Base;
use RZP\Models\GenericDocument\ResponseHelper;
use RZP\Models\Merchant\Entity as MEntity;
use RZP\Models\Merchant\Detail\Entity as MDEntity;
use RZP\Models\Merchant\Document\Core as MDocCore;
use RZP\Models\Merchant\OwnerDetail\Entity as OEntity;
use RZP\Models\Payment\Gateway;
use RZP\Models\Payment\Processor\App;
use RZP\Services\Reminders;
use RZP\Services\TerminalsService;
use RZP\Trace\TraceCode;
use RZP\Jobs\MerchantCrossborderEmail;
use RZP\Models\Base\UniqueIdEntity;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\InternationalIntegration\Emerchantpay\EmerchantpayApmRequestFile;

class Service extends Base\Service
{

    /**
     * @var Reminders
     */
    protected $reminderService;

    /**
     * @var TerminalsService
     */
    protected $terminalService;

    const EMERCHANTPAY_APM_MUTEX = "EMERCHANTPAY_APM_ONBOARDING_REQUEST_";
    const SHARED_MERCHANT_ID = '100000razorpay';
    const HS_CODE_MUTEX = "HS_CODE_REQUEST_";

    public function __construct()
    {
        parent::__construct();

        $this->reminderService = $this->app['reminders'];
        $this->terminalService = $this->app['terminals_service'];
    }


    public function createMerchantInternationalIntegration($input)
    {
        return (new Core())->createMerchantInternationalIntegration($input);
    }

    public function getMerchantInternationalIntegrations(string $mid, array $input){

        $merchant = $this->repo->merchant->findOrFailPublic($mid);
        $integrations = $this->repo->merchant_international_integrations
            ->getByMerchantId($mid);

        return $integrations->toArrayAdmin();
    }

    public function deleteMerchantInternationalIntegrations($input)
    {
        return (new Core())->deleteMerchantInternationalIntegration($input);
    }

    public function fetchEmerchantpayRequestData()
    {
        $merchant = $this->auth->getMerchant();

        if($merchant->isInternational() === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INTERNATIONAL_NOT_ALLOWED_ON_MERCHANT,
                null, null, PublicErrorDescription::EMERCHANTPAY_INTERNATIONAL_DISABLED_DESC);
        }

        $mii = $this->repo->merchant_international_integrations
            ->getByMerchantIdAndIntegrationEntity($merchant->getId(), Gateway::EMERCHANTPAY);

        $owners = $this->repo->merchant_owner_details
            ->getByMerchantIdAndGateway($merchant->getId(), Gateway::EMERCHANTPAY);

        return $this->createEmerchantpayApmFormResponse($mii, $owners);
    }

    protected function createEmerchantpayApmFormResponse($mii, $owners)
    {
        $instruments = [];
        $merchantInfo = [];
        $ownerDetails = [];
        $nonEditable = [
            Constant::GST_NUMBER => $this->merchant->getGstin(),
            Constant::REGISTRATION_NUMBER => $this->merchant->getCompanyCin(),
            MEntity::PURPOSE_CODE => $this->merchant->getPurposeCode(),
            MDEntity::IEC_CODE => $this->merchant->getIecCode(),
        ];
        $submitted = false;

        if($mii !== null)
        {
            // Constructing instruments object

            $paymentMethods = $mii->getPaymentMethods();

            foreach($paymentMethods as $method)
            {
                array_push($instruments, $method['instrument']);
            }

            // Constructing merchant info object

            $merchantInfo = $mii->getNotes()->toArray();

            $submitted = $merchantInfo['submitted'] ?? false;
            unset($merchantInfo['submitted']);

        }

        foreach ($nonEditable as $key=>$value)
        {
            if($value === null)
            {
                $nonEditable[$key] = $merchantInfo[$key] ?? null;
                unset($nonEditable[$key]);
            }
        }

        // Constructing owner details object

        foreach ($owners as $owner)
        {
            $od = $owner->getOwnerDetails();
            $od['id'] = $owner->getId();
            array_push($ownerDetails, $od);
        }

        $data = [
            'instruments'   => $instruments,
            'non_editable'  => $nonEditable,
            'merchant_info' => $merchantInfo,
            'owner_details' => $ownerDetails,
            'submitted'     => $submitted
        ];

        return $data;
    }

    public function createOrUpdateEmerchantpayRequestData($input)
    {
        (new Validator)->validateInput('post_emerchantpay_request_data', $input);

        $merchant = $this->auth->getMerchant();
        if ($merchant->isInternational() === false) {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INTERNATIONAL_NOT_ALLOWED_ON_MERCHANT,
                null, null, PublicErrorDescription::EMERCHANTPAY_INTERNATIONAL_DISABLED_DESC);
        }

        $instruments = $input['instruments'] ?? [];
        $merchantInfo = $input['merchant_info'] ?? [];
        $ownerDetails = $input['owner_details'] ?? [];

        $merchantInfo['submitted'] = $input['submitted'] ?? false;

        $acquired = $this->app['api.mutex']->acquire(self::EMERCHANTPAY_APM_MUTEX . $merchant->getId());
        if($acquired === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS);
        }

        try
        {
            $owners = $this->createEmerchantpayOwnerDetails($ownerDetails);

            $requestedApm = [];
            $mii = $this->createEmerchantpayMerchantInfo($instruments, $merchantInfo, $requestedApm);
        }
        catch(\Throwable $e)
        {
            $this->trace->traceException($e);
            throw new BadRequestException(ErrorCode::BAD_REQUEST_ERROR);
        }

        $this->repo->beginTransaction();
        try
        {
            foreach($owners as $owner)
            {
                $this->repo->merchant_owner_details->saveOrFail($owner);
            }
            $this->repo->merchant_international_integrations->saveOrFail($mii);
            $this->repo->commit();
        }
        catch (\Throwable $e)
        {
            $this->repo->rollback();
            $this->trace->traceException($e);
            throw new ServerErrorException(PublicErrorDescription::SERVER_ERROR, ErrorCode::SERVER_ERROR);
        }

        $this->app['api.mutex']->release(self::EMERCHANTPAY_APM_MUTEX . $merchant->getId());

        foreach ($owners as $owner){
            $this->trace->info(TraceCode::MERCHANT_OWNER_DETAIL_SAVE, [
                'id'                      => $owner->getId(),
                'merchant_id'             => $owner->getMerchantId(),
                'gateway'                 => $owner->getGateway()
            ]);
        }

        $this->trace->info(TraceCode::MERCHANT_INTERNATIONAL_INTEGRATION_SAVE, [
            'id'                      => $mii->getId(),
            'merchant_id'             => $mii->getMerchantId(),
            'integration_entity'      => $mii->getIntegrationEntity(),
            'payment_methods'         => $mii->getPaymentMethods()
        ]);

        // On submission of form create terminals and set a reminder to send the files
        if($merchantInfo['submitted'] === true || $merchantInfo['submitted'] === '1')
        {
            $requestedPaymentMethods = $this->createEmerchantPayRequestedTerminals($merchant->getId(), $requestedApm);
            $this->setEmerchantpayInstrumentsRequested($merchant->getId(), $mii, $requestedPaymentMethods, 'terminal_request_sent');
            
            $splitzProperties = [
                'id'            => UniqueIdEntity::generateUniqueId(),
                'experiment_id' => $this->app['config']->get('app.emerchantpay_maf_generation_via_sqs_experiement_id'),
            ];

            if($this->isSplitzExperimentEnable($splitzProperties, 'variant_on')){
                $this->generateEmerchantpayMafInAsync($merchant->getId());
            }
            else{
                $this->createEmerchantpayFileGenerationReminder($merchant->getId());
            }
        }

        return $this->createEmerchantpayApmFormResponse($mii, $owners);
    }

    protected function createEmerchantpayOwnerDetails($ownerDetails)
    {
        $owners = [];
        foreach($ownerDetails as $detail)
        {
            if(isset($detail[OEntity::ID]) === false)
            {
                $owner_input = [];
                $owner_input[OEntity::MERCHANT_ID] = $this->merchant->getId();
                $owner_input[OEntity::GATEWAY] = Gateway::EMERCHANTPAY;
                $owner_input[OEntity::OWNER_DETAILS] = $detail;

                $owner = new OEntity;
                $owner->generateId();
                $owner->build($owner_input);
            }
            else
            {
                $owner = $this->repo->merchant_owner_details->findOrFail($detail[OEntity::ID]);
                $od = $owner->getOwnerDetails();
                $owner->setOwnerDetails(array_merge($od, $detail));
            }
            array_push($owners, $owner);
        }
        return $owners;
    }

    protected function createEmerchantpayMerchantInfo($instruments, $merchantInfo, &$reqApm)
    {
        $this->validateEmerchantpayInstruments($instruments);

        $mii = $this->repo->merchant_international_integrations
            ->getByMerchantIdAndIntegrationEntity($this->merchant->getId(), Gateway::EMERCHANTPAY);

        if(isset($mii))
        {
            $mii->setPaymentMethods(
                $this->constructEmerchantpayInstrumentsObject($instruments, $mii->getPaymentMethods(), $reqApm));
            $mii->setNotes(array_merge($mii->getNotes()->toArray(), $merchantInfo));
        }
        else
        {
            $mii_input = [];
            $mii_input[Entity::MERCHANT_ID] = $this->merchant->getId();
            $mii_input[Entity::INTEGRATION_ENTITY] = Gateway::EMERCHANTPAY;
            $mii_input[Entity::INTEGRATION_KEY] = Gateway::EMERCHANTPAY;
            $mii_input[Entity::PAYMENT_METHODS] =
                $this->constructEmerchantpayInstrumentsObject($instruments, [], $reqApm);
            $mii_input[Entity::NOTES] = $merchantInfo;

            $mii = new Entity;
            $mii->generateId();
            $mii->build($mii_input);
        }

        return $mii;
    }

    protected function constructEmerchantpayInstrumentsObject($instruments, $paymentMethods, &$reqApm)
    {
        $oldInstruments = [];
        foreach ($paymentMethods as $index => $paymentMethod)
        {
            if(in_array($paymentMethod['instrument'], $instruments) === false
                && $paymentMethod['terminal_request_sent'] === false)
            {
                unset($paymentMethods[$index]);
            }
            else
            {
                array_push($oldInstruments, $paymentMethod['instrument']);
            }
        }

        $newInstruments = array_values(array_diff($instruments, $oldInstruments));
        $newInstrumentsObject = [];
        foreach ($newInstruments as $newInstrument)
        {
            array_push($newInstrumentsObject, [
                'instrument'     => $newInstrument,
                'requested_at' => Carbon::now(Timezone::IST)->getTimestamp(),
                'file_request_sent'   => false,
                'terminal_request_sent' => false,
            ]);
        }

        $instrumentsObject =  array_merge($paymentMethods, $newInstrumentsObject);
        $reqApm = array_filter($instrumentsObject, function ($i) { return !$i['terminal_request_sent']; });

        return $instrumentsObject;
    }

    protected function validateEmerchantpayInstruments($instruments)
    {
        foreach ($instruments as $instrument)
        {
            if(in_array($instrument, App::$supportedApps[Gateway::EMERCHANTPAY]) === false)
            {
                throw new BadRequestException(ErrorCode::BAD_REQUEST_INPUT_VALIDATION_FAILURE,
                $instrument, null, PublicErrorDescription::EMERCHANTPAY_INSTRUMENT_INVALID_DESC);
            }
        }
    }

    /*
     * Not using initiateOnboarding wrapper in Onboarding/Service as it sends currency as null and is
     * hardwired for certain terminals.
     */
    protected function createEmerchantPayRequestedTerminals($merchantId, $paymentMethods)
    {
        $requestedPaymentMethods = [];
        foreach ($paymentMethods as $paymentMethod)
        {
            $instrument = $paymentMethod['instrument'];
            $this->trace->info(
                TraceCode::INITIATE_TERMINAL_ONBOARDING_REQUEST,
                [
                    'merchant_id'    => $merchantId,
                    'gateway'        => Gateway::EMERCHANTPAY,
                    'instrument'     => $instrument,
                ]);

            try
            {
                $response = $this->terminalService->initiateOnboarding($merchantId, Gateway::EMERCHANTPAY,
                    ['gateway_terminal_id' => 'em' . $instrument], null,
                    [Gateway::getSupportedCurrenciesByApp($instrument)[0]], []);

                if(count($response) !== 0)
                {
                    array_push($requestedPaymentMethods, $instrument);
                }

            }
            catch (\Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    Logger::ERROR,
                    TraceCode::TERMINALS_SERVICE_INTEGRATION_ERROR,
                    [
                        'merchant_id' => $merchantId,
                        'gateway' => Gateway::EMERCHANTPAY,
                        'instrument' => $instrument,
                    ]);
            }
        }

        return $requestedPaymentMethods;
    }

    protected function createEmerchantpayFileGenerationReminder($merchantId)
    {
        $request = $this->createApmRemindersRequest($merchantId);
        try
        {
            $this->reminderService->createReminder($request, self::SHARED_MERCHANT_ID);
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Logger::ERROR,
                TraceCode::REMINDERS_RESPONSE,
                [
                    'data'        => $request,
                    'merchant_id' => $merchantId,
                ]);
        }
    }

    protected function generateEmerchantpayMafInAsync($merchantId)
    {
        $payload = [
            'action' => CrossBorderCommonUseCases::EMERCHANTPAY_ONBOARDING_VIA_MAF,
            'mode' => $this->mode ?? Mode::LIVE,
            'body' => [
                'merchant_id' => $merchantId
            ]
        ];

        try
        {
            CrossBorderCommonUseCases::dispatch($payload)->delay(rand(60,1000) % 601);

            $this->trace->info(TraceCode::CROSS_BORDER_COMMON_USE_CASES_DISPATCHED,[
                'payload' => $payload,
            ]);
        }
        catch(\Exception $ex)
        {
            $this->trace->info(TraceCode::CROSS_BORDER_COMMON_USE_CASES_DISPATCH_FAILED,[
                'payload' => $payload,
            ]);
        }
    }

    protected function createApmRemindersRequest($merchantId)
    {

        $req_id = Entity::generateUniqueId();

        $reminderData = [
            'submitted_at' => Carbon::now(Timezone::IST)->getTimestamp(),
        ];

        $url = sprintf('merchant/international/apm_request/reminder/%s/%s',$this->mode, $merchantId);

        $request = [
            'namespace'     => Constant::APM_REQUEST_NAMESPACE,
            'entity_id'     => $req_id,
            'entity_type'   => Constant::APM_REQUEST_ENTITY,
            'reminder_data' => $reminderData,
            'callback_url'  => $url,
        ];

        return $request;
    }

    public function generateEmerchantpayMaf($mode, $mid)
    {
        $this->trace->info(
            TraceCode::EMERCHANTPAY_APM_REQUEST_MAF_GENERATE,
            [
                'mid' => $mid,
                'mode' => $mode,
            ]
        );

        $input['merchant_id'] = $mid;

        $fileProcessor = new EmerchantpayApmRequestFile;
        $ufhResponse = $fileProcessor->generate($input, null, null);
        if(count($ufhResponse) !== 0)
        {
            $fileProcessor->sendGifuFile();
        }

        $this->postProcessEmerchantpayMaf($mid);

        return $ufhResponse;
    }

    public function postProcessEmerchantpayMaf($mid)
    {
        $this->app['basicauth']->setMerchant($this->repo->merchant->findOrFailPublic($mid));
        $this->merchant = $this->app['basicauth']->getMerchant();

        $mii = $this->repo->merchant_international_integrations
            ->getByMerchantIdAndIntegrationEntity($mid, Gateway::EMERCHANTPAY);
        $owners = $this->repo->merchant_owner_details
            ->getByMerchantIdAndGateway($mid, Gateway::EMERCHANTPAY);

        // Save purpose code if required
        if($this->merchant->getPurposeCode() === null)
        {
            (new \RZP\Models\Merchant\Service())->patchMerchantPurposeCode([
                MEntity::PURPOSE_CODE => $mii->getNotes()[MEntity::PURPOSE_CODE],
                MDEntity::IEC_CODE    => $mii->getNotes()[MDEntity::IEC_CODE] ?? null,
            ]);
        }

        // Save collected documents in merchant document table
        $this->saveEmerchantpayMerchantDocuments($mii, $owners, $mid);

        // Mark the requested payment methods as processed
        $paymentMethods = array_filter($mii->getPaymentMethods(),
            function ($i) { return ($i['terminal_request_sent'] && !$i['file_request_sent']); });

        $paymentMethods = array_column($paymentMethods, 'instrument');
        $this->setEmerchantpayInstrumentsRequested($mid, $mii, $paymentMethods, 'file_request_sent');
    }

    protected function saveEmerchantpayMerchantDocuments($mii, $owners, $mid)
    {
        $core = new MDocCore;

        // Store MII document
        $documents = $mii->getNotes()['documents'];
        foreach ($documents as $document)
        {
            try
            {
                $id = ResponseHelper::getDocumentId($document['id'], Constant::DOCUMENT_ID_SIGN, Constant::FILE_ID_SIGN);
                $core->saveFileInMerchantDocument($id, Constant::$documentTypeMap[$document['key']], $mid, 'merchant', $mid);
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException($e, Logger::ERROR, TraceCode::DOCUMENT_SAVE_FAILED, [
                    'document_id' => $document['id'] ?? '',
                ]);
            }
        }

        // Store Owner documents
        foreach ($owners as $owner)
        {
            $documents = $owner->getOwnerDetails()['documents'];
            foreach ($documents as $document)
            {
                try
                {
                    $id = ResponseHelper::getDocumentId($document['id'], Constant::DOCUMENT_ID_SIGN, Constant::FILE_ID_SIGN);
                    $core->saveFileInMerchantDocument($id, Constant::$documentTypeMap[$document['key']], $mid, 'owner', $owner->getId());
                }
                catch (\Throwable $e)
                {
                    $this->trace->traceException($e, Logger::ERROR, TraceCode::DOCUMENT_SAVE_FAILED, [
                        'document_id' => $document['id'] ?? '',
                    ]);
                }
            }
        }
    }

    protected function setEmerchantpayInstrumentsRequested($mid, $mii, $paymentMethods, $updateKey)
    {

        $pms = $mii->getPaymentMethods();
        foreach ($pms as $key => $pm)
        {
            if(in_array($pm['instrument'], $paymentMethods) === true)
            {
                $pms[$key]['requested_at'] = Carbon::now(Timezone::IST)->getTimestamp();
                $pms[$key][$updateKey] = true;
            }
        }

        $mii->setPaymentMethods($pms);
        $acquired = $this->app['api.mutex']->acquire(self::EMERCHANTPAY_APM_MUTEX . $mid);
        if($acquired === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS,
                'payment_methods',
                ['mid' => $mid],
                TraceCode::MERCHANT_INTERNATIONAL_INTEGRATION_SAVE_FAILED);
        }
        $this->repo->merchant_international_integrations->saveOrFail($mii);
        $this->app['api.mutex']->release(self::EMERCHANTPAY_APM_MUTEX . $mid);

        $this->trace->info(TraceCode::MERCHANT_INTERNATIONAL_INTEGRATION_SAVE, [
            'id'                      => $mii->getId(),
            'merchant_id'             => $mii->getMerchantId(),
            'integration_entity'      => $mii->getIntegrationEntity()
        ]);
    }

    public function editMerchantInternationalIntegrations($input)
    {
        return (new Core())->editMerchantInternationalIntegrations($input);
    }

    public function getInternationalVirtualAccounts($input)
    {
        $merchantId = $this->merchant->getMerchantId();

        return (new Core())->getInternationalVirtualAccounts($merchantId);
    }

    public function getInternationalVirtualAccountByVACurrency($input, $va_currency)
    {
        $merchantId = $this->merchant->getMerchantId();

        return (new Core())->getInternationalVirtualAccountByVACurrency($input, $merchantId, $va_currency);
    }

    public function patchHsCode($input)
    {

        $merchant = $this->merchant;
        if($merchant !== null){
            $mid = $this->merchant->getId();
        }else{
            $mid = $input[Entity::MERCHANT_ID];
        }

        $acquired = $this->app['api.mutex']->acquire(self::HS_CODE_MUTEX . $mid);
        if($acquired === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS);
        }

        $mii_notes = [];
        $mii_notes[Constant::HS_CODE] = $input['hs_code'];

        $mii = $this->repo->merchant_international_integrations
            ->getByMerchantIdAndIntegrationEntity($mid, Constant::INTEGRATION_ENTITY_OPGSP_IMPORT);

        if(isset($mii))
        {
            $mii->setNotes(array_merge($mii->getNotes()->toArray(), $mii_notes));
        }
        else
        {
            $mii_input = [];
            $mii_input[Entity::MERCHANT_ID] = $mid;
            $mii_input[Entity::INTEGRATION_ENTITY] = Constant::INTEGRATION_ENTITY_OPGSP_IMPORT;
            $mii_input[Entity::INTEGRATION_KEY] = Constant::INTEGRATION_ENTITY_OPGSP_IMPORT;
            $mii_input[Entity::NOTES] = $mii_notes;

            $mii = new Entity;
            $mii->generateId();
            $mii->build($mii_input);
        }

        $this->repo->merchant_international_integrations->saveOrFail($mii);

        $this->trace->info(TraceCode::MERCHANT_INTERNATIONAL_INTEGRATION_CREATE, [
            'merchant_id'             => $mid,
            'integration_entity'      => $mii,
        ]);

        $this->app['api.mutex']->release(self::HS_CODE_MUTEX . $mid);

        return ['success' => true];
    }

    public function getMerchantHsCode(string $merchantId = null)
    {
        $merchant = $this->merchant;

        if($merchant !== null){
            $mid = $this->merchant->getId();
        }else{
            $mid = $merchantId;
        }

        $mii_notes = [];

        $mii = $this->repo->merchant_international_integrations
            ->getByMerchantIdAndIntegrationEntity($mid, Constant::INTEGRATION_ENTITY_OPGSP_IMPORT);

        if(isset($mii))
        {
            $mii->setNotes(array_merge($mii->getNotes()->toArray(), $mii_notes));
            $mii_notes[Constant::HS_CODE] = $mii->getNotes()[Constant::HS_CODE];
        }

        return $mii_notes;
    }

    public function sendInvoiceRemindersForInternationalIntegration($input)
    {
        $response = [];

        try {

            $merchantIntegrations = (new Core())
                ->getByIntegrationKey($input['integration_entity']);

            foreach ($merchantIntegrations as $mii) {

                $data = [
                    'merchant_id' => $mii[Entity::MERCHANT_ID],
                    'action'      => MerchantCrossborderEmail::OPGSP_IMPORT_INVOICE_REMINDER,
                    // this is required when someone wants to manually trigger the cron
                    // default is 15 days.
                    'prev_days' => $input['prev_days'] ?? null,
                ];

                MerchantCrossborderEmail::dispatch($data)->delay(rand(60, 1000) % 601);
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e);
            throw $e;
        }

        $response['success'] = true;
        return $response;

    }

    public function isSplitzExperimentEnable(array $properties, string $checkVariant, string $traceCode = null): bool
    {
        try
        {
            $response = $this->app['splitzService']->evaluateRequest($properties);

            $variant = $response['response']['variant']['name'] ?? null;

            $this->trace->info(TraceCode::SPLITZ_RESPONSE, $response);

            if ($variant === $checkVariant)
            {
                return true;
            }
        }
        catch (\Exception $e)
        {
            $id = $properties['id'] ?? null;

            $traceCode = $traceCode ?? TraceCode::SPLITZ_ERROR;

            $this->trace->traceException($e, Trace::ERROR, $traceCode, ['id' => $id]);
        }

        return false;
    }
}
