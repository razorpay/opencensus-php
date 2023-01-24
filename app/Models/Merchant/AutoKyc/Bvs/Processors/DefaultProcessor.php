<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\Processors;

use App;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Core;
use RZP\Models\Admin\Org\Entity;
use RZP\Exception\LogicException;
use RZP\Exception\AssertionException;
use RZP\Exception\IntegrationException;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Merchant\AutoKyc\Response;
use RZP\Models\Merchant\AutoKyc\Bvs\BvsClient;
use RZP\Models\Merchant\M2MReferral\Constants;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Services\Segment\EventCode as SegmentEvent;
use RZP\Models\DeviceDetail\Constants as DDConstants;
use RZP\Models\Merchant\AutoKyc\Bvs\Config\BvsConfig;
use RZP\Models\Merchant\AutoKyc\Bvs\BaseResponse\ValidationBaseResponse;
use RZP\Models\Merchant\AutoKyc\Bvs\BaseResponse\ValidationBaseResponseV2;
use RZP\Models\Merchant\AutoKyc\Bvs\BaseResponse\ValidationDetailsResponse;

class DefaultProcessor implements Processor
{
    protected $input;

    protected $trace;

    protected $merchant;

    protected $configName;

    protected $experimentMap = [
        Constant::GSTIN                                      => RazorxTreatment::GSTIN_SYNC,
        Constant::CIN                                        => RazorxTreatment::CIN_SYNC,
        Constant::LLP_DEED                                   => RazorxTreatment::LLPIN_SYNC,
        Constant::PERSONAL_PAN                               => RazorxTreatment::PERSONAL_PAN_SYNC,
        Constant::BUSINESS_PAN                               => RazorxTreatment::BUSINESS_PAN_SYNC,
        Constant::BANK_ACCOUNT_WITH_PERSONAL_PAN             => RazorxTreatment::BANK_SYNC,
        Constant::BANK_ACCOUNT_WITH_BUSINESS_PAN             => RazorxTreatment::BANK_SYNC,
        Constant::BANK_ACCOUNT_WITH_BUSINESS_OR_PROMOTER_PAN => RazorxTreatment::BANK_SYNC,
        Constant::AADHAAR                                    => RazorxTreatment::AADHAR_FRONT_BACK_SYNC,
        Constant::AADHAAR_WITH_PAN                           => RazorxTreatment::AADHAR_EKYC_SYNC,
        Constant::VOTERS_ID                                  => RazorxTreatment::VOTERS_ID_SYNC,
        Constant::PASSPORT                                   => RazorxTreatment::PASSPORT_SYNC

    ];

    protected $timeoutMap = [
        Constant::GSTIN                                      => 5,
        Constant::CIN                                        => 2,
        Constant::LLP_DEED                                   => 2,
        Constant::PERSONAL_PAN                               => 3,
        Constant::BUSINESS_PAN                               => 3,
        Constant::BANK_ACCOUNT_WITH_PERSONAL_PAN             => 12,
        Constant::BANK_ACCOUNT_WITH_BUSINESS_PAN             => 12,
        Constant::BANK_ACCOUNT_WITH_BUSINESS_OR_PROMOTER_PAN => 12,
        Constant::AADHAAR                                    => 2,
        Constant::AADHAAR_WITH_PAN                           => 2,
        Constant::VOTERS_ID                                  => 2,
        Constant::PASSPORT                                   => 2

    ];
    /**
     * @var BvsConfig
     */
    protected $bvsRuleConfig;

    const BVS_CONFIG_NAME_SPACE = 'RZP\Models\Merchant\AutoKyc\Bvs\Config';

    protected $app;


    /**
     * @param array       $input
     * @param             $configName
     *
     * @param             $merchant
     *
     * @throws LogicException
     */
    public function __construct(array $input, $configName, $merchant)
    {
        $this->app = App::getFacadeRoot();

        $this->merchant = $merchant;

        $this->trace = $this->app['trace'];

        $this->input = $input;


        if(empty($configName)===false)
        {
            $configClass = $this->getConfigClass($configName);

            $this->bvsRuleConfig = new $configClass($this->input);
        }

        $this->configName = $configName;
    }

    /**
     * @return array
     */
    public function getOwnerInput(): array
    {
        return [
            Constant::PLATFORM   => $this->input[Constant::PLATFORM] ?? Constant::PG,
            Constant::OWNER_ID   => $this->input[Constant::OWNER_ID] ?? '',
            Constant::OWNER_TYPE => Constant::MERCHANT,
        ];
    }

    /**
     * This function basically aggregates the payload together and push it to BVS client for creation of validation.
     *
     * @return Response
     * @throws \ErrorException
     * @throws IntegrationException|AssertionException
     */
    public function Process($sendEnrichmentDetails = false, $skipAsyncFlow = false): Response
    {
        $validation = $this->getCreateValidationArray($sendEnrichmentDetails);

        if ($sendEnrichmentDetails === true)
        {
            $response = (new BvsClient\BvsValidationClientV2($this->merchant, true, $this->getTimeout()))->createValidation($validation);

            return new ValidationBaseResponseV2($response);
        }

        if ($skipAsyncFlow === true)
        {
            $response = (new BvsClient\BvsValidationClientV2($this->merchant, true, $this->getTimeout()))->createValidation($validation);

            return new ValidationBaseResponseV2($response);
        }

        if ($this->requestMode() == Constant::SYNC)
        {
            try
            {
                $response = (new BvsClient\BvsValidationClientV2($this->merchant, true, $this->getTimeout()))->createValidation($validation);

                return new ValidationBaseResponseV2($response);
            }
            catch (\Exception $e)
            {
                $response = (new BvsClient\BvsValidationClient($this->merchant, false))->createValidation($validation);

                return new ValidationBaseResponse($response);
            }
        }
        else
        {
            $response = (new BvsClient\BvsValidationClient($this->merchant, false))->createValidation($validation);

            return new ValidationBaseResponse($response);
        }
    }

    public function FetchDetails(string $validationId): Response
    {
        $payload = [
            Constant::VALIDATION_ID            => $validationId
        ];

        if(empty($this->bvsRuleConfig)===false) $payload[Constant::ENRICHMENT_DETAIL_FIELDS] = $this->bvsRuleConfig->getEnrichmentDetails();

        $response = (new BvsClient\BvsValidationClient())->getValidation($payload);

        return new ValidationDetailsResponse($response);
    }

    /**
     * @return array
     */
    public function getArtefact(): array
    {
        $artefact = $this->getOwnerInput();

        $artefact[Constant::TYPE] = $this->input[Constant::ARTEFACT_TYPE] ?? '';

        $artefact[Constant::NOTES] = $this->input[Constant::NOTES] ?? [];

        $artefact[Constant::PROOFS] = $this->input[Constant::PROOFS] ?? [];

        $artefact[Constant::DETAILS] = $this->input[Constant::DETAILS] ?? [];

        return $artefact;
    }

    /**
     * @return array
     * @throws AssertionException
     */
    public function getRules(): array
    {
        return $this->bvsRuleConfig->getRule();
    }

    public function getFetchDetailsRule(): array
    {
        return $this->bvsRuleConfig->getFetchDetailsRule();
    }

    /**
     * @return array
     * @throws AssertionException
     */
    public function getEnrichments(): array
    {
        return $this->bvsRuleConfig->getEnrichment();
    }

    /**
     * @return array
     * @throws AssertionException
     */
    public function getEnrichmentsV2(): array
    {
        return $this->bvsRuleConfig->getEnrichmentV2();
    }

    /**
     * The config class name should be passed in input payload
     *
     * @param string $configName
     *
     * @return string
     * @throws LogicException
     */
    private function getConfigClass(string $configName)
    {
        $configClass = self::BVS_CONFIG_NAME_SPACE . '\\' . ucfirst(strtolower($configName));

        if (class_exists($configClass) === true)
        {
            return $configClass;
        }

        throw new LogicException(
            ErrorCode::SERVER_ERROR_BVS_CONFIG_FILE_MISSING_FOR_ARTEFACT_TYPE,
            null,
            [
                Constant::CONFIG_NAME => $configName,
            ]);
    }

    /**
     * @throws AssertionException
     */
    protected function getCreateValidationArray($sendEnrichmentDetails = false): array
    {
        $validation = [];

        $validation[Constant::ARTEFACT] = $this->getArtefact();

        $validation[Constant::ENRICHMENTS] = $this->getEnrichments();

        $validation[Constant::RULES] = $this->getRules();

        if ($sendEnrichmentDetails === true)
        {
            $validation[Constant::ENRICHMENTS] = $this->getEnrichmentsV2();

            $validation[Constant::RULES] = $this->getFetchDetailsRule();
        }

        return $validation;
    }

    protected function getTimeout()
    {
        if (empty($this->timeoutMap) === true or
            empty($this->configName) === true or
            array_key_exists($this->configName, $this->timeoutMap) === false or
            empty($this->timeoutMap[$this->configName]) === true
        )
        {
            return 2;
        }

        return $this->timeoutMap[$this->configName];
    }

    protected function requestMode()
    {
        if ($this->app['config']['services.bvs.sync.flow'] == true)
        {
            return Constant::SYNC;
        }

        if ((empty($this->merchant) === true) or
            (empty($this->merchant->getMerchantId()) === true) or
            (empty($this->experimentMap) === true) or
            (empty($this->configName) === true) or
            (array_key_exists($this->configName, $this->experimentMap) === false) or
            (empty($this->experimentMap[$this->configName]) === true) or
            ((new Core)->isRegularMerchant($this->merchant) === false) or
            ((in_array($this->configName, Constant::EXCLUDED_CONFIGS, true) === false) and
            ($this->merchant->isSignupCampaign(DDConstants::EASY_ONBOARDING) === false))
        )
        {
            return Constant::ASYNC;
        }

        $experiment = $this->experimentMap[$this->configName];

        $isRazorxExperimentEnabled = (new Core())->isRazorxExperimentEnable(
            $this->merchant->getMerchantId(),
            $experiment);

        $this->trace->info(TraceCode::RAZORX_EXPERIMENT_RESULT, ["merchant_id" => $this->merchant->getMerchantId(),
                                                                 $experiment   => $isRazorxExperimentEnabled]);

        if ($isRazorxExperimentEnabled === false)
        {
            return Constant::ASYNC;
        }

        $isRazorxExperimentEnabled = (new Core())->isRazorxExperimentEnable(
            $this->merchant->getMerchantId(),
            RazorxTreatment::BVS_IN_SYNC);

        $this->trace->info(TraceCode::RAZORX_EXPERIMENT_RESULT, ["merchant_id"                => $this->merchant->getMerchantId(),
                                                                 RazorxTreatment::BVS_IN_SYNC => $isRazorxExperimentEnabled]);

        if ($isRazorxExperimentEnabled === false)
        {
            return Constant::ASYNC;
        }

        $this->app['segment-analytics']->pushTrackEvent($this->merchant, [], SegmentEvent::BVS_IN_SYNC_ENABLED);

        return Constant::SYNC;
    }

    /**
     * @param array $input
     *
     * @return array
     * @throws IntegrationException
     */
    public function getVerificationUrl(array $input)
    {
        return (new BvsClient\ArtefactCuratorApiClient())->getVerificationUrl($input);
    }

    /**
     * @param array $input
     *
     * @return array
     * @throws IntegrationException
     */
    public function fetchVerificationDetails(array $input)
    {
        return (new BvsClient\ArtefactCuratorApiClient())->fetchVerificationDetails($input);
    }

}
