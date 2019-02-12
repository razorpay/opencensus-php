<?php

namespace RZP\Models\Partner\Commission;

/**
 * An entity for which a commission entity can be created, must implement this interface
 *
 * Interface CommissionSourceInterface
 *
 * @package RZP\Models\Partner\Commission
 */
interface CommissionSourceInterface
{
    /**
     * Returns the name of the entity - payment, refund, transfer etc
     *
     * @return mixed
     */
    public function getEntity();

    /**
     * The entity must have a merchant relation defined
     *
     * @return mixed
     */
    public function merchant();

    /**
     * @return mixed
     */
    public function getCurrency();

    /**
     * The entity must have a transaction relation defined
     *
     * @return mixed
     */
    public function transaction();
}
