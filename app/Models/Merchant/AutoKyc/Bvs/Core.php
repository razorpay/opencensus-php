<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs;

use App;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\BvsValidation;
use RZP\Models\Merchant\AutoKyc\Response;
use RZP\Models\Merchant\AutoKyc\Bvs\BvsClient\BvsProbeClient;
use RZP\Models\Merchant\AutoKyc\Bvs\ProbeMocks\CompanySearchMock;
use RZP\Models\Merchant\AutoKyc\Bvs\BaseResponse\CompanySearchBaseResponse;

class Core extends Base\Core
{
    /**
     * All BVS Artefact verification should be triggered from this function.
     * This function triggers request to bvs and creates new entry in bvs_validation table if no error.
     * Return null if verification failed because of any reason.
     *
     * @param string $merchantId
     * @param array $input
     * @return BvsValidation\Entity|null
     */
    public function verify(string $merchantId, array $input): ?BvsValidation\Entity
    {
        $this->trace->info(TraceCode::BVS_VERIFICATION_REQUEST, ['input' => $input]);

        $input[Constant::OWNER_ID] = $merchantId;

        try
        {
            $processor = (new Factory())->getProcessor($input);

            $response = $processor->Process();

            $validationObject = $this->getValidationObject($input, $response);

            return (new BvsValidation\Core())->create($validationObject);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException($ex);
        }

        return null;
    }

    /**
     * @param string $searchString
     *
     * @return array
     * @throws \RZP\Exception\IntegrationException
     */
    public function probeCompanySearch(string $searchString): array
    {
        $this->trace->info(TraceCode::BVS_COMPANY_SEARCH_REQUEST, ['input' => $searchString]);

        $app = App::getFacadeRoot();

        $mock = $app['config']['services.bvs.mock'];

        $response = null;

        if ($mock === true)
        {
            //
            // This config is not defined in application config , this is used in test case only
            //
            $mockStatus = $app['config']['services.bvs.response'] ?? Constant::SUCCESS;

            $companySearchMock = new CompanySearchMock($searchString, $mockStatus);

            $response = $companySearchMock->getResponse();
        }
        else
        {
            $response = (new BvsProbeClient())->companySearch($searchString);
        }

        $companySearchBase = new CompanySearchBaseResponse($response);

        return $companySearchBase->getCompanySearchResponse();
    }

    public static function getProbeDimension(string $probeType): array
    {
        $dimension = [
            Constant::CLIENT => $probeType
        ];

        return $dimension;
    }

    /**
     * This function return payload for creation of Bvs_Validation entity
     *
     * @param array    $input
     * @param Response $response
     *
     * @return array
     */
    private function getValidationObject(array $input, Response $response): array
    {
        $validationObject = [
            BvsValidation\Entity::OWNER_ID        => $input[Constant::OWNER_ID],
            BvsValidation\Entity::ARTEFACT_TYPE   => $input[Constant::ARTEFACT_TYPE],
            BvsValidation\Entity::VALIDATION_UNIT => $input[Constant::VALIDATION_UNIT],
            BvsValidation\Entity::OWNER_TYPE      => Constant::MERCHANT,
            BvsValidation\Entity::PLATFORM        => Constant::PG,
        ];

        $validationObject = array_merge($validationObject, $response->getResponseData());

        return $validationObject;
    }
}
