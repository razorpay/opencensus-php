<?php

namespace RZP\Models\Payout\DataMigration;

use App;
use Illuminate\Foundation\Application;

use RZP\Models\Payout\Entity;
use RZP\Base\RepositoryManager;
use RZP\Models\Reversal\Entity as ReversalEntity;

class Reversal
{
    const FEES         = 'fees';
    const PAYOUT_ID    = 'payout_id';

    /**
     * The application instance.
     *
     * @var Application
     */
    protected $app;

    /**
     * Repository manager instance
     *
     * @var RepositoryManager
     */
    protected $repo;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->repo = $this->app['repo'];
    }

    public function getPayoutServiceReversalForApiPayout(Entity $payout)
    {
        /** @var ReversalEntity $reversal */
        $reversal = $payout->reversal;

        if ($reversal === null)
        {
            return [];
        }

        return [$this->createPayoutServiceReversal($reversal)];
    }

    protected function createPayoutServiceReversal(ReversalEntity $reversal)
    {
        $reversal = [
            ReversalEntity::ID             => $reversal->getId(),
            ReversalEntity::MERCHANT_ID    => $reversal->getMerchantId(),
            ReversalEntity::PAYOUT_ID      => $reversal->getEntityId(),
            ReversalEntity::BALANCE_ID     => $reversal->getBalanceId(),
            ReversalEntity::AMOUNT         => $reversal->getAmount(),
            self::FEES                     => $reversal->getFee(),
            ReversalEntity::TAX            => $reversal->getTax(),
            ReversalEntity::CURRENCY       => $reversal->getCurrency(),
            ReversalEntity::CHANNEL        => $reversal->getChannel(),
            ReversalEntity::NOTES          => $reversal->getNotesJson(),
            ReversalEntity::TRANSACTION_ID => $reversal->getTransactionId(),
            ReversalEntity::CREATED_AT     => $reversal->getCreatedAt(),
            ReversalEntity::UPDATED_AT     => $reversal->getUpdatedAt(),
            ReversalEntity::UTR            => $reversal->getUtr(),
        ];

        return $reversal;
    }
}
