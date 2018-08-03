<?php

namespace RZP\Models\Reversal;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Reversal;
use RZP\Models\Payment\Refund;

class Service extends Base\Service
{
    public function fetch(string $id): array
    {
        $reversal = $this->repo
                         ->reversal
                         ->findByPublicIdAndMerchant($id, $this->merchant);

        return $reversal->toArrayPublic();
    }

    public function fetchMultiple(array $input): array
    {
        $merchantId = $this->merchant->getId();

        $reversals = $this->repo->reversal->fetch($input, $merchantId);

        return $reversals->toArrayPublic();
    }

    public function fetchLaReversal(string $id): array
    {
        (new Merchant\Service)->validateLinkedAccount();

        $merchantId = $this->merchant->getId();

        $relations = ['reversal'];

        Reversal\Entity::verifyIdAndStripSign($id);

        $refund = $this->repo->refund->findByReversalIdAndMerchant($id, $merchantId, $relations);

        $reversal = $this->createReversalResponseFromRefund($refund);

        return $reversal;
    }

    public function fetchLaReversals(array $input): array
    {
        (new Merchant\Service)->validateLinkedAccount();

        $merchantId = $this->merchant->getId();

        $input['expand'] = ['reversal'];

        $refunds = $this->repo->refund->fetch($input, $merchantId);

        $reversals = [
            "count"  => count($refunds),
            "entity" => "collection",
            "items"  => $this->createReversalsResponse($refunds),
        ];

        return $reversals;
    }

    /**
     * Returns a list of transformed entities for la reversals.
     * @param $refunds
     *
     * @return array
     */
    private function createReversalsResponse($refunds): array
    {
        $reversals = [];
        foreach ($refunds as $refund)
        {
            $reversals[] = $this->createReversalResponseFromRefund($refund);
        }

        return $reversals;
    }

    /**
     * Replaces notes from refund entity to reversal entity.
     * @param $refund
     *
     * @return array
     */
    private function createReversalResponseFromRefund($refund): array
    {
        $result = $refund->toArrayPublic();

        $reversalData = $result[Refund\Entity::REVERSAL];
        $reversalData[Reversal\Entity::NOTES] = $result[Refund\Entity::NOTES];

        return $reversalData;
    }
}
