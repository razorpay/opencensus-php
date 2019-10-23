<?php

namespace RZP\Services\Mock\FTS;

use RZP\Services\FTS\Constants;
use RZP\Services\FTS\FundTransfer as BaseFundTransfer;
use RZP\Models\FundTransfer\Attempt as FundTransferAttempt;

class FundTransfer extends BaseFundTransfer
{
    protected $FTACore;

    /**
     * @var FundTransferAttempt\Entity
     */
    protected $fta;

    public function requestFundTransfer():array
    {
        $mockResponse = [
            Constants::STATUS           => Constants::STATUS_CREATED,
            Constants::MESSAGE          => 'fund transfer sent to fts.',
            Constants::FUND_TRANSFER_ID => random_integer(2),
        ];

        $this->FTACore = new FundTransferAttempt\Core;

        $this->updateFTAMock($mockResponse);
    }

    protected function updateFTAMock(array $responseBody)
    {
        $ftsTransferId = $responseBody[Constants::FUND_TRANSFER_ID];

        $responseBody[Constants::STATUS] = strtolower($responseBody[Constants::STATUS]);

        if(strcasecmp($responseBody[Constants::STATUS], Constants::STATUS_CREATED) === 0)
        {
            $responseBody[Constants::STATUS] = Constants::STATUS_INITIATED;
        }

        $this->FTACore->updateFTA($this->fta, $ftsTransferId, $responseBody[Constants::STATUS]);
    }

    public function initialize(string $ftaId)
    {
        $this->fta = $this->FTACore->getFTAEntity($ftaId);
    }

    public function shouldAllowTransfersViaFts()
    {
        return [true, 'Dummy'];
    }
}