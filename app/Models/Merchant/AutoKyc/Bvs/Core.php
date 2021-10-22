<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs;

use App;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Detail\Metric;
use RZP\Models\Merchant\BvsValidation;
use RZP\Models\Merchant\Store\ConfigKey;
use RZP\Models\Merchant\Store\Constants;
use RZP\Models\Merchant\AutoKyc\Response;
use RZP\Models\Merchant\Store\Core as StoreCore;
use RZP\Models\Merchant\Detail\Constants as DetailConstants;
use RZP\Models\Merchant\AutoKyc\Bvs\BvsClient\BvsProbeClient;
use RZP\Models\Merchant\AutoKyc\Bvs\ProbeMocks\CompanySearchMock;
use RZP\Models\Merchant\AutoKyc\Bvs\ProbeMocks\GetGstDetailsMock;
use RZP\Models\Merchant\AutoKyc\Bvs\BaseResponse\CompanySearchBaseResponse;
use RZP\Models\Merchant\AutoKyc\Bvs\BaseResponse\GetGstDetailsBaseResponse;

class Core extends Base\Core
{
    public function fetchValidationDetails(string $merchantId, array $input, $validationId = null)
    {
        if (is_null($validationId) === true)
        {
            $validationObj = (new BvsValidation\Core)->getLatestArtefactValidation(
                $merchantId, $input[Constant::ARTEFACT_TYPE], $input[Constant::VALIDATION_UNIT]);

            if (empty($validationObj) === true) {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_ID);
            }

            $validationId = $validationObj->getValidationId();
        }

        $processor = (new Factory())->getProcessor($input);

        $response = $processor->FetchDetails($validationId);

        return $response->getResponseData();
    }

    /**
     * All BVS Artefact verification should be triggered from this function.
     * This function triggers request to bvs and creates new entry in bvs_validation table if no error.
     * Return null if verification failed because of any reason.
     *
     * ownerId will be merchantId for PG request and bankingAccountId for bankingRequest
     *
     * @param string $ownerId
     * @param array  $input
     *
     * @return BvsValidation\Entity|null
     */
    public function verify(string $ownerId, array $input): ?BvsValidation\Entity
    {
        $input[Constant::OWNER_ID] = $ownerId;

        $this->trace->info(TraceCode::BVS_VERIFICATION_REQUEST, ['input' => $input]);

        $validation                  = null;
        $validationTriggeringSuccess = true;

        try
        {
            $processor = (new Factory())->getProcessor($input);

            $response = $processor->Process();

            $validationObject = $this->getValidationObject($input, $response);

            $bvsCore = new BvsValidation\Core();

            $validation = $bvsCore->create($validationObject);

            $bvsCore->setCustomCallbackHandlerIfApplicable($validation, $input);
        }
        catch (\Exception $ex)
        {
            $validationTriggeringSuccess = false;

            $this->trace->traceException($ex);
        }
        finally
        {
            $dimension = [
                Constant::ARTEFACT_TYPE => $input[Constant::ARTEFACT_TYPE] ?? '',
                Constant::SUCCESS       => $validationTriggeringSuccess,
            ];

            $this->trace->count(Metric::BVS_ARTEFACT_VERIFICATION_TRIGGER, $dimension);
        }

        return $validation;
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
     * @param string $merchantId
     *
     * @return string
     */
    public function getCompanySearchRateLimiterKey(string $merchantId): string
    {
         return DetailConstants::COMPANY_SEARCH_ATTEMPT_COUNT_REDIS_KEY_PREFIX .
                                        $merchantId;
    }

    /**
     * @param string $merchantId
     *
     * @return int
     */
    public function getCompanySearchAttempts(string $merchantId): int
    {
        $companySearchAttemptRedisKey = $this->getCompanySearchRateLimiterKey($merchantId);

        $companySearchCount = $this->app['cache']->get($companySearchAttemptRedisKey) ?? 0;

        return $companySearchCount;
    }

    /**
     * @param string $merchantId
     */
    public function increaseCompanySearchAttempt(string $merchantId)
    {
        $companySearchAttemptRedisKey = $this->getCompanySearchRateLimiterKey($merchantId);

        $companySearchAttempt = $this->getCompanySearchAttempts($merchantId) + 1;

        $this->app['cache']->put($companySearchAttemptRedisKey,
                                 $companySearchAttempt,
                                 DetailConstants::COMPANY_SEARCH_ATTEMPT_COUNT_TTL_IN_SEC);
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

        if (array_key_exists(Constant::OWNER_TYPE, $input) === true)
        {
            $validationObject[BvsValidation\Entity::OWNER_TYPE] = $input[BvsValidation\Entity::OWNER_TYPE];
        }

        $validationObject = array_merge($validationObject, $response->getResponseData());

        return $validationObject;
    }


    /**
     * @param string $pan
     *
     * @return array
     * @throws \RZP\Exception\IntegrationException
     */
    public function probeGetGstDetails(string $pan): array
    {
        $this->trace->info(TraceCode::BVS_GET_GST_DETAILS_REQUEST, ['input' => $pan]);

        $app = App::getFacadeRoot();

        $mock = $app['config']['services.bvs.mock'];

        $response = null;

        if ($mock === true)
        {
            //
            // This config is not defined in application config , this is used in test case only
            //
            $mockStatus = $app['config']['services.bvs.response'] ?? Constant::SUCCESS;

            $getGstDetailsMock = new GetGstDetailsMock($pan, $mockStatus);

            $response = $getGstDetailsMock->getResponse();

            $getGstDetailsBase = new GetGstDetailsBaseResponse($response);

            return $getGstDetailsBase->geGstDetailsResponse();
        }
        else
        {
            $keys = [
                ConfigKey::GST_DETAILS_FROM_PAN
            ];

            $data = (new StoreCore())->fetchValuesFromStore($pan,
                                                            ConfigKey::ONBOARDING_NAMESPACE,
                                                            $keys,
                                                            Constants::INTERNAL);

            $this->trace->info(TraceCode::MERCHANT_STORE_GET_DETAILS, ['data' => $data]);

            if (empty($data[ConfigKey::GST_DETAILS_FROM_PAN]))
            {
                $response = (new BvsProbeClient())->getGstDetails($pan);

                $getGstDetailsBase = new GetGstDetailsBaseResponse($response);

                $gstDetails= $getGstDetailsBase->geGstDetailsResponse();

                $data = [
                    Constants::NAMESPACE => ConfigKey::ONBOARDING_NAMESPACE,
                    ConfigKey::GST_DETAILS_FROM_PAN                 => json_encode($gstDetails),
                ];

                $data = (new StoreCore())->updateMerchantStore($pan, $data, Constants::INTERNAL);
            }

            return json_decode($data[ConfigKey::GST_DETAILS_FROM_PAN]);

        }
    }

}
