<?php

namespace RZP\Models\Card\TokenisedIIN;

use RZP\Constants\Environment;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Services\BinService;
use RZP\Tests\Functional\Fixtures\Entity\Token;
use RZP\Trace\TraceCode;
use RZP\Models\Base;
use RZP\Models\Card\TokenisedIIN;
use Razorpay\Trace\Logger as Trace;

class Service extends Base\Service
{
    public function createIin($input)
    {
        $input[TokenisedIIN\Entity::TOKEN_IIN_LENGTH] = $this->getIINLength($input[TokenisedIIN\Entity::LOW_RANGE]);

        $tokenisedIin = (new Entity)->build($input);

        $this->repo->saveOrFail($tokenisedIin);

        return $tokenisedIin->toArrayAdmin();
    }

    public function createMapping($card_iin, $token_iin)
    {
        $input[TokenisedIIN\Entity::HIGH_RANGE] = $token_iin;
        $input[TokenisedIIN\Entity::LOW_RANGE] = $token_iin;
        $input[TokenisedIIN\Entity::IIN] = $card_iin;
        $input[TokenisedIIN\Entity::TOKEN_IIN_LENGTH] = $this->getIINLength($token_iin);

        $tokenisedIin = (new Entity)->build($input);

        $this->repo->saveOrFail($tokenisedIin);

        return $tokenisedIin->toArrayPublic();
    }

    public function updateIin($iin , $input)
    {
        $iin = $this->repo->tokenised_iin->findByIin($iin);

        if(isset($input[Entity::LOW_RANGE]) === true)
        {
            $input[Entity::TOKEN_IIN_LENGTH] = $this->getIINLength($input[Entity::LOW_RANGE]);
        }

        $iin->edit($input);

        $this->repo->saveOrFail($iin);

        return $iin->toArrayAdmin();
    }

    public function update($input)
    {
        if(isset($input['high_range'])){

            $iin = $this->repo->tokenised_iin->findbyHighRange($input['high_range']);
        }
        elseif(isset($input['low_range'])){

            $iin = $this->repo->tokenised_iin->findbyLowRange($input['low_range']);
        }
        else{
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_IIN_NOT_EXISTS,
                null,
                [
                    'input'  => $input
                ]);
        }

        if(!isset($iin)){
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_IIN_NOT_EXISTS,
                null,
                [
                    'input'  => $input
                ]);
        }

        $iin_input = $input['iin'];

        $iin->edit($iin_input);

        $this->repo->saveOrFail($iin);

        return $iin->toArrayAdmin();
    }

    public function addOrUpdate($iin) : array
    {
        $token_iin_high = $this->repo->tokenised_iin->findbyHighRange($iin['high_range']) ;

        $token_iin_low = $this->repo->tokenised_iin->findbyLowRange($iin['low_range']);

        if(isset($token_iin_high)){

            $response = $this->updateIin($token_iin_high, $iin);

        }
        elseif(isset($token_iin_low)){

            $response = $this->updateIin($token_iin_high , $iin);

        }
        else{

            $response = $this->createIin($iin);

        }

        return $response;
    }

    public function addTokenMapping($input) : array
    {
        $response = array();
        $iin = $input['actual_iin'];
        $tokenIin = $input['token_iin'];
        if(isset($iin) === true && isset($tokenIin) === true){
            $response = $this->addMapping($iin, $tokenIin);
        }
        return $response;
    }

    public function addMapping($iin, $tokenIin) : array
    {
        $response = array();
        $token_iin_high = $this->repo->tokenised_iin->findHighRange($tokenIin) ;

        $token_iin_low = $this->repo->tokenised_iin->findLowRange($tokenIin);

        if(isset($token_iin_high) === false && isset($token_iin_low) === false){

            $response = $this->createMapping($iin, $tokenIin);
            //adding bin service update for dual write
            if ($this->shouldDualWriteToken() === true)
            {
                $this->updateBinServiceTokenData($iin, $tokenIin);
            }

        }

        return $response;
    }

    public function fetchIin($iin)
    {
        $iin = $this->repo->tokenised_iin->findbyIin($iin);

        if(isset($iin)){

            $response =  $this->getBasicDetails($iin);

            return $response;
        }

        return null;
    }

    public function fetchbyTokenIin($tokenIin){

        $iin = $this->repo->tokenised_iin->findbyTokenIin($tokenIin);

        if(isset($iin)){

            $response =  $this->getBasicDetails($iin);

            return $response;

        }

        return null;
    }

    protected function getBasicDetails(Entity $iins)
    {
        $response = [
            Entity::IIN                  => $iins->getIin(),
            ENTITY::HIGH_RANGE           => $iins->getHighRange(),
            ENTITY::LOW_RANGE            => $iins->getLowRange(),
            Entity::TOKEN_IIN_LENGTH     => $iins->getIINLength(),
        ];

        return $response;
    }

    public function addIinBulk($input): array
    {

        $failedIds = [];
        $successCount = $failedCount = 0;
        $existingTokens = [];
        $returnData = [];

        foreach ($input['iins'] as $iin) {

            try {

                $token_iin_high = $this->repo->tokenised_iin->findbyHighRange($iin['high_range']) ;

                $token_iin_low = $this->repo->tokenised_iin->findbyLowRange($iin['low_range']);

                if(isset($token_iin_high->iin)){

                    $this->trace->info(TraceCode::TOKEN_IIN_ALREADY_EXISTS,
                        [
                            'iin_high'    => $token_iin_high
                        ]
                    );
                    $existingTokens[] =  $token_iin_high;

                }
                elseif(isset($token_iin_low->iin)){

                    $this->trace->info(TraceCode::TOKEN_IIN_ALREADY_EXISTS,
                        [
                            'iin_low'    => $token_iin_low
                        ]
                    );
                    $existingTokens[] =  $token_iin_low;
                }
                else{

                    $resp = $this->createIin($iin);

                    $returnData[$iin['iin']] = $resp;
                }

                $successCount++;

            }
            catch (\Exception $e){

                $returnData[$iin['iin']] = $e->getMessage();

                $this->trace->traceException($e,
                    Trace::ERROR,
                    TraceCode::TOKENISED_IINS_BULK_UPLOAD_FAILED,
                    [
                        'iin' => $iin,
                        'error' => $e->getMessage(),
                    ]
                );
                $failedCount++;

                $failedIds[] = $iin;

            }

        }

        $response = [
            'total_success'      => $successCount,
            'total_fail'         => $failedCount,
            'failed_iins'         => $failedIds,
            'success_iins'        => $returnData,
            'existing_iins'       => $existingTokens
        ];

        return $response;
    }


    public function deleteIin($id)
    {
        $iin = $this->repo->tokenised_iin->findById($id);

        $this->trace->info(TraceCode::TOKEN_IIN_DELETE_BULK,
            [
                'iin'    => $iin
            ]
        );

        if(!isset($iin)){
            return null;
        }

        return $this->repo->deleteOrFail($iin);
    }

    public function deleteBulk($input)
    {

        $failedIds = [];
        $deletedIds = [];
        $failedCount = 0;

        for($val = 0; $val < 10 ;$val++){

            $id = $input + $val;
            try
            {

                $this->deleteIin($id);
                $deletedIds[] = ($id);
                $this->trace->info(TraceCode::TOKEN_IIN_DELETE_BULK,
                [
                    'id'    => ($id)
                ]
                );

            }
            catch (\Exception $e)
            {
                $this->trace->error($e,
                    TraceCode::TOKEN_IIN_DELETE_BULK_FAILED,
                    [
                        'id'    => ($id),
                        'error'  => $e->getMessage(),
                    ]
                );

                $failedCount++;

                $failedIds[] = ($id);
            }

        }

        $response = [
            'failed'    => $failedCount,
            'failedIds' => $failedIds,
            'deletedIds' => $deletedIds,
        ];

        return $response;

    }

    // helper functions

    public function getIINLength($tokenIIN)
    {
        return strlen($tokenIIN);
    }

    public function updateBinServiceTokenData($iin, $tokenIIN)
    {
        $binService = (new BinService());

        $originalIIN = $this->repo->iin->findOrFail($iin);

        $request = $this->formatRequest($originalIIN, $tokenIIN);

        $url = "ranges";

        $namespace = "RZP/".strtoupper($originalIIN["country"])."/".strtoupper($originalIIN["type"]);

        $binService->sendRequest($url, 'PUT', $request, $namespace, BinService::CREATE_TOKEN_RANGE);
    }

    private function formatRequest($iin, $tokenIIN)
    {
        $token_iin_length = strlen($tokenIIN);
        $padding_length = 21 - $token_iin_length;

        $range_min = $tokenIIN . str_repeat('0', $padding_length);
        $range_max = $tokenIIN . str_repeat('9', $padding_length);

        return [
            "iin" => $tokenIIN,
            "mapped_iin" => $iin['iin'],
            "rangeMin" => $range_min,
            "rangeMax" => $range_max
        ];
    }

    public function shouldDualWriteToken(): bool
    {
        $variant = $this->app->razorx->getTreatment(UniqueIdEntity::generateUniqueId(),RazorxTreatment::ALLOW_BIN_SERVICE_TOKEN_DUAL_WRITE, $this->mode);

        $this->trace->info(TraceCode::BIN_SERVICE_TOKEN_DUAL_WRITE_VARIANT, [
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

    public function shouldReadBinServiceInPrimaryMode($tokenIIN)
    {
        if (Environment::isTestingEnvironment($this->app['env']) === true ||
            Environment::isEnvironmentQA($this->app['env']) === true ||
            Environment::isEnvironmentItf($this->app['env']) === true)
        {
            return false;
        }

        $properties = [
            'id'            => $tokenIIN,
            'experiment_id' => $this->app['config']->get('app.read_token_iin_bin_service_primary'),
            'request_data' => json_encode([
                "bin" => $tokenIIN
            ]),
        ];

        return $this->checkIfSplitzExperimentIsEnabled($properties);
    }

    public function shouldReadFromBinServiceInShadowMode($tokenIIN)
    {
        if (Environment::isTestingEnvironment($this->app['env']) === true ||
            Environment::isEnvironmentQA($this->app['env']) === true ||
            Environment::isEnvironmentItf($this->app['env']) === true)
        {
            return false;
        }

        $properties = [
            'id'            => $tokenIIN,
            'experiment_id' => $this->app['config']->get('app.read_token_iin_bin_service_shadow'),
            'request_data' => json_encode([
                "bin" => $tokenIIN
            ]),
        ];

        return $this->checkIfSplitzExperimentIsEnabled($properties);
    }

    public function checkIfSplitzExperimentIsEnabled($properties): bool
    {

        try
        {
            $response = $this->app['splitzService']->evaluateRequest($properties);

            $variant = $response['response']['variant']['name'] ?? null;

            if ($variant === 'enable')
            {
                return true;
            }
        }
        catch (\Exception $e)
        {
            $id = $properties['id'] ?? null;
            $this->trace->traceException($e, Trace::ERROR, TraceCode::SPLITZ_ERROR, ['id' => $id]);
        }

        return false;
    }



    public function adaptBinServiceEntityToApiTokenisedIINEntity($binServiceEntity)
    {
        $rangeMin = $binServiceEntity['rangeMin'];
        $rangeMax = $binServiceEntity['rangeMax'];

        $rangeMin = str_pad($rangeMin, 21, '0');

        $rangeMax = str_pad($rangeMax, 21, '9');

        $reverseOffset = 0;

        while ($reverseOffset < 21 &&
            $rangeMin[20 - $reverseOffset] === '0' &&
            $rangeMax[20 - $reverseOffset] === '9') {
            $reverseOffset++;
        }

        $tokenIINLength = min(max(21 - $reverseOffset, 6), 9);
        $lowRange = substr($rangeMin, 0, $tokenIINLength);
        $highRange = substr($rangeMax, 0, $tokenIINLength);
        $iin = substr($binServiceEntity["mappedIin"], 0, 6);

        return [
            "iin" => $iin,
            "low_range" => $lowRange,
            "high_range" => $highRange,
            "token_iin_length" => $tokenIINLength,
        ];
    }

    public function compareBinServiceEntityAndApiServiceEntity($repoTokenisedIINEntity, $adaptedBinServiceResponse, $extraTraceData)
    {
        foreach (Constants::COMPARISON_FIELDS_TOKENISED_IIN_SHADOW_RAMP as $field)
        {
            $apiServiceEntityValue = "";
            $binServiceEntityValue = "";

            if (isset($repoTokenisedIINEntity[$field])){
                $apiServiceEntityValue = $repoTokenisedIINEntity[$field];
            }

            if (isset($adaptedBinServiceResponse[$field])){
                $binServiceEntityValue = $adaptedBinServiceResponse[$field];
            }

            if ($apiServiceEntityValue !== $binServiceEntityValue)
            {
                $this->trace->info(TraceCode::API_BIN_SERVICE_TOKEN_IIN_DATA_MISMATCH, [
                    'field'                 => $field,
                    'token_iin'             => $extraTraceData['iin'],
                    'method'                => $extraTraceData['method_name'],
                    'apiServiceEntityValue' => $apiServiceEntityValue,
                    'binServiceEntityValue' => $binServiceEntityValue,
                ]);
            }
        }

    }

}
