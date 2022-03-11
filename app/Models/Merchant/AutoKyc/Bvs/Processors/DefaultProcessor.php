<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\Processors;

use App;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Core;
use RZP\Exception\LogicException;
use RZP\Exception\AssertionException;
use RZP\Exception\IntegrationException;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Merchant\AutoKyc\Response;
use RZP\Models\Merchant\AutoKyc\Bvs\BvsClient;
use RZP\Models\Merchant\M2MReferral\Constants;
use RZP\Services\Segment\EventCode as SegmentEvent;
use RZP\Models\Merchant\AutoKyc\Bvs\Config\BvsConfig;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
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
        Constant::GSTIN    => RazorxTreatment::GSTIN_SYNC,
        Constant::CIN      => RazorxTreatment::CIN_SYNC,
        Constant::LLP_DEED => RazorxTreatment::LLPIN_SYNC
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
    public function Process(): Response
    {
        $validation = $this->getCreateValidationArray();

        if ($this->requestMode() == Constant::SYNC)
        {
            try
            {
                $response = (new BvsClient\BvsValidationClientV2($this->merchant, true))->createValidation($validation);

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
            Constant::VALIDATION_ID            => $validationId,
            Constant::ENRICHMENT_DETAIL_FIELDS => $this->bvsRuleConfig->getEnrichmentDetails()
        ];

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

    /**
     * @return array
     * @throws AssertionException
     */
    public function getEnrichments(): array
    {
        return $this->bvsRuleConfig->getEnrichment();
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

    protected function getCreateValidationArray(): array
    {
        $validation = [];

        $validation[Constant::ARTEFACT] = $this->getArtefact();

        $validation[Constant::ENRICHMENTS] = $this->getEnrichments();

        $validation[Constant::RULES] = $this->getRules();

        return $validation;
    }

    protected function requestMode()
    {
        if (empty($this->merchant) === true or
            empty($this->merchant->getMerchantId()) === true or
            empty($this->experimentMap) === true or
            empty($this->configName) === true or
            array_key_exists($this->configName, $this->experimentMap) === false or
            empty($this->experimentMap[$this->configName]) === true or
            (new Core)->isRegularMerchant($this->merchant) === false
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
}
