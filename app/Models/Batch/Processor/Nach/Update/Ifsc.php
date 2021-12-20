<?php

namespace RZP\Models\Batch\Processor\Nach\Update;

use RZP\Models\Batch;
use RZP\Trace\TraceCode;
use RZP\Models\Customer\Token;
use RZP\Models\Payment\Method;
use RZP\Models\Batch\Processor\Emandate\Base as BaseProcessor;

class Ifsc extends BaseProcessor
{
    const TOKEN_ID = 'token_id';
    const OLD_IFSC = 'old_ifsc';
    const NEW_IFSC = 'new_ifsc';

    protected function processEntry(array & $entry)
    {
        $tokenId = trim($entry[self::TOKEN_ID]);
        $oldIfsc = trim($entry[self::OLD_IFSC]);
        $newIfsc = trim($entry[self::NEW_IFSC]);

        if (\Razorpay\IFSC\IFSC::validate($newIfsc) === false)
        {
            $this->trace->critical(TraceCode::NACH_DATA_UPDATE_ERROR, [
                'new_ifsc' => $newIfsc,
            ]);

            $entry[Batch\Header::STATUS] = Batch\Status::FAILURE;

            return;
        }

        $token = $this->getToken($tokenId);

        $tokenMethod = $token->getMethod();

        $tokenIfsc = $token->getIfsc();

        if (in_array($tokenMethod, [Method::NACH, Method::EMANDATE]) == false)
        {
            $this->trace->critical(TraceCode::INVALID_NACH_TOKEN_FOR_UPDATE, [
                'old_ifsc'     => $oldIfsc,
                'new_ifsc'     => $newIfsc,
                'token_id'     => $tokenId,
                'token_method' => $tokenMethod
            ]);

            $entry[Batch\Header::STATUS] = Batch\Status::FAILURE;

            return;
        }

        if ($tokenIfsc !== $oldIfsc)
        {
            $this->trace->critical(TraceCode::NACH_DATA_UPDATE_ERROR, [
                'file_ifsc' => $newIfsc,
                'db_ifsc'   => $oldIfsc,
            ]);

            $entry[Batch\Header::STATUS] = Batch\Status::FAILURE;

            return;
        }

        $token->setIfsc($newIfsc);

        $token->saveOrFail();

        $entry[Batch\Header::STATUS] = Batch\Status::SUCCESS;
    }

    protected function getToken(string $tokenId): Token\Entity
    {
        return $this->repo->token->findOrFailPublic($tokenId);
    }
}
