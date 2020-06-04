<?php


namespace RZP\Models\Mpan;

use Throwable;
use RZP\Models\Base;
use RZP\Error\PublicErrorCode;
use RZP\Exception\BaseException;
use RZP\Models\Batch\Processor\Mpan;
use RZP\Models\Batch\Processor\AESCrypto;

class Service extends Base\Service
{
    public function create(array $input)
    {
        $mpan = $this->core()->create($input);

        return $mpan->toArrayPublic();
    }

    public function issueMpans(array $input)
    {
        $validator = new Validator();

        $validator->validateInput(CONSTANTS::ISSUE_MPANS_OPERATION, $input);

        $mpans = $this->core()->issueMpans($input);

        return (new Base\PublicCollection($mpans))->toArrayPublic();
    }

    public function fetchMpans(array $input)
    {
        $mpans = $this->repo->mpan->fetch($input, $this->merchant->getId());

        return (new Base\PublicCollection($mpans))->toArrayPublic();
    }

    public function mpansBulk(array $input)
    {
        $response = new Base\PublicCollection;

        foreach ($input as $row)
        {
            $rowOutput = $this->processMpansBulkRow($row);

            // we can't do this inside processMpansBulkRow() because if one mpan raise error, then unmasking won't be done for remaining
            $this->maskOutputRow($rowOutput);

            $response->add($rowOutput);
        }

        return $response;
    }

    public function processMpansBulkRow(array $row)
    {
        $result = [
            Constants::BATCH_SUCCESS          => false,
            Constants::BATCH_HTTP_STATUS_CODE => 500,
            Constants::BATCH_ERROR => [
                Constants::BATCH_ERROR_CODE        => '',
                Constants::BATCH_ERROR_DESCRIPTION => '',
            ],
        ];

        $result = array_merge($result, $row);

        /*
        * The entry has to be processed in a transaction block because:
        * the input file is in column format
        * Batch processing is done row wise
        * If Mpan creation fails for any reason(existing mpan, invalid mpan etc), then entire row is failed
        * This is to enable easier retry later
        */
        try
        {

            $this->repo->transaction(function () use (& $result) {
                foreach (Mpan::BATCH_HEADER_NETWORK_CODE_MAP as $batchHeader => $networkCode) {

                    if (isset($result[$batchHeader]) === false) {
                        continue;
                    }

                    // decrypt only if its non-empty, we also do not encrypt empty values
                    if (empty($result[$batchHeader]) === false)
                    {
                        $aesCrypto =  new AESCrypto();

                        $mpan = $aesCrypto->decryptString($result[$batchHeader]);    
                    }
                    else
                    {
                        $mpan = $result[$batchHeader];
                    }

                    $input = [
                        Entity::MPAN => $mpan,
                        Entity::NETWORK => $networkCode,
                    ];

                    $this->core()->create($input);
                }

                $result[Constants::BATCH_SUCCESS] = true;

                $result[Constants::BATCH_HTTP_STATUS_CODE] = 201;
            });
        }
        catch (BaseException $exception)
        {
            $result[Constants::BATCH_ERROR] = [
                Constants::BATCH_ERROR_DESCRIPTION => $exception->getMessage(),
                Constants::BATCH_ERROR_CODE => $exception->getPublicError(),
            ];

            $result[Constants::BATCH_HTTP_STATUS_CODE] = $exception->getCode();
        }

        catch (Throwable $throwable)
        {
            $result[Constants::BATCH_ERROR] = [
                Constants::BATCH_ERROR_DESCRIPTION => $throwable->getMessage(),
                Constants::BATCH_ERROR_CODE => PublicErrorCode::SERVER_ERROR,
            ];

            $result[Constants::BATCH_HTTP_STATUS_CODE] = $throwable->getCode();
        }

        return $result;
    }

    protected function maskOutputRow(array & $row)
    {
        foreach (Mpan::BATCH_HEADER_NETWORK_CODE_MAP as $batchHeader => $networkCode) {

            if ((isset($row[$batchHeader]) === false) 
                or (empty($row[$batchHeader]) === true))
            {
                continue;
            }

            $aesCrypto =  new AESCrypto();

            $decryptedMpan = $aesCrypto->decryptString($row[$batchHeader]);
            
            $row[$batchHeader] = (new Entity)->getMaskedMpan($decryptedMpan);
        }
    }
}
