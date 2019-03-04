<?php

namespace RZP\Models\Partner\Commission;

use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\Transaction;
use RZP\Models\Partner\Config as PartnerConfig;

class Core extends Base\Core
{
    public function build(
        Base\PublicEntity $source,
        Merchant\Entity $partner,
        PartnerConfig\Entity $partnerConfig,
        array $input = [],
        Transaction\Entity $txn = null): Entity
    {
        $commission = new Entity;

        $commission->build($input);

        $commission->source()->associate($source);

        $commission->partner()->associate($partner);

        $commission->partnerConfig()->associate($partnerConfig);

        $commission->transaction()->associate($txn);

        return $commission;
    }

    /**
     * Creates partner commission entities from a captured payment
     *
     * @param Payment\Entity $payment
     *
     * @return array
     */
    public function createFromCapturedPayment(Payment\Entity $payment): array
    {
        $calculator = new Calculator($payment);

        if ($calculator->shouldCreateCommission() === false)
        {
            return [];
        }

        $calculator->calculateAndSaveCommission();

        return $calculator->getCommissions();
    }

    /**
     * @param Merchant\Entity $merchant
     * @param array           $input
     *
     * @return Base\PublicCollection
     * @throws \RZP\Exception\BadRequestException
     */
    public function list(Merchant\Entity $merchant, array $input) : Base\PublicCollection
    {
        // resellers should not see transaction commissions data
        (new Merchant\Validator)->validateIsNotResellerPartner($merchant);

        // check to get only logged in partner's commission list
        $input[Entity::PARTNER_ID] = $merchant->getId();

        // add expands to fetch merchant details
        $input[Repository::EXPAND] = [Entity::SOURCE_MERCHANT];

        $commissions = $this->repo->commission->fetch($input);

        return $commissions;
    }
}
