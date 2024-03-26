<?php

namespace RZP\Models\Card\TokenisedIIN;

use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Services\BinService;
use RZP\Tests\Functional\Fixtures\Entity\Token;
use RZP\Trace\TraceCode;
use RZP\Models\Base;
use RZP\Models\Card\TokenisedIIN;

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

}
