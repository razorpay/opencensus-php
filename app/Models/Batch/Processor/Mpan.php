<?php

namespace RZP\Models\Batch\Processor;


use RZP\Models\Batch;
use RZP\Models\Card\NetworkName;
use RZP\Models;

class Mpan extends Base
{
    protected $core;

    const BATCH_HEADER_NETWORK_CODE_MAP = [
        Batch\Header::MPAN_VISA_PAN          => NetworkName::VISA,
        Batch\Header::MPAN_MASTERCARD_PAN    => NetworkName::MC,
        Batch\Header::MPAN_RUPAY_PAN         => NetworkName::RUPAY,
    ];
    public function __construct(Batch\Entity $batch)
    {
        parent::__construct($batch);

        $this->core = new Models\Mpan\Core;
    }
    protected function processEntry(array & $entry)
    {
        $entry[Batch\Header::STATUS] = Batch\Status::FAILED;

        /*
         * The entry has to be processed in a transaction block because:
         * the input file is in column format
         * Batch processing is done row wise
         * If Mpan creation fails for any reason(existing mpan, invalid mpan etc), then entire row is failed
         * This is to enable easier retry later
         */
        $this->repo->transaction(function () use ($entry)
        {
            foreach (self::BATCH_HEADER_NETWORK_CODE_MAP as $batchHeader => $networkCode)
            {

                if (isset($entry[$batchHeader])  === false)
                {
                    continue;
                }

                // decrypt only if its non-empty, we also do not encrypt empty values
                if (empty($entry[$batchHeader]) === false)
                {
                    $aesCrypto =  new AESCrypto();

                    $mpan = $aesCrypto->decryptString($entry[$batchHeader]);    
                }
                else
                {
                    $mpan = $entry[$batchHeader];
                }

                $mpanCreationInput = [
                    Models\Mpan\Entity::MPAN         => $mpan,
                    Models\Mpan\Entity::NETWORK      => $networkCode,
                ];

                $this->core->create($mpanCreationInput);
            }

            $entry[Batch\Header::STATUS] = Batch\Status::SUCCESS;

        });
    }
}
