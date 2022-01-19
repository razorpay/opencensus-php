<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs;

use App;

use RZP\Error\ErrorCode;
use RZP\Exception\LogicException;
use RZP\Exception\IntegrationException;
use RZP\Models\Merchant\AutoKyc\Response;
use RZP\Models\Merchant\AutoKyc\Bvs\BvsClient;
use RZP\Models\Merchant\AutoKyc\Bvs\Config\BvsConfig;

class DefaultProcessor implements Processor
{
    protected $input;

    protected $trace;

    /**
     * @var BvsConfig
     */
    protected $bvsRuleConfig;

    protected $app;

    const BVS_CONFIG_NAME_SPACE = 'RZP\Models\Merchant\AutoKyc\Bvs\Config';


    /**
     * @param array  $input
     * @param string $configName
     *
     * @throws LogicException
     */
    public function __construct(array $input, string $configName=null)
    {
        $this->app = App::getFacadeRoot();

        $this->trace = $this->app['trace'];

        $this->input = $input;

        if (empty($configName) === false)
        {
            $configClass = $this->getConfigClass($configName);

            $this->bvsRuleConfig = new $configClass($this->input);
        }
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
     * @throws IntegrationException|\RZP\Exception\AssertionException
     */
    public function Process(): Response
    {
        $validation = $this->getCreateValidationArray();

        $response = (new BvsClient\BvsValidationClient())->createValidation($validation);

        return new BaseResponse\ValidationBaseResponse($response);
    }

    public function FetchDetails(string $validationId): Response
    {
        $payload = [
            Constant::VALIDATION_ID => $validationId
        ];

        if (empty($this->bvsRuleConfig) === false)
        {
            $payload[Constant::ENRICHMENT_DETAIL_FIELDS] = $this->bvsRuleConfig->getEnrichmentDetails();
        }

        $response = (new BvsClient\BvsValidationClient())->getValidation($payload);

        return new BaseResponse\ValidationDetailsResponse($response);
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
     * @throws \RZP\Exception\AssertionException
     */
    public function getRules(): array
    {
        return $this->bvsRuleConfig->getRule();
    }

    /**
     * @return array
     * @throws \RZP\Exception\AssertionException
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
}
