<?php


namespace RZP\Models\Base\Traits;

use RZP\Models\Card;
use RZP\Models\FundAccount\Entity;

/**
 * Trait ProcessAccountNumber
 *
 * @package RZP\Models\Base\Traits
 *
 */
trait SensitiviseCardDetails
{
    /**
     * Unset sensitive card details
     *
     * @param array $input
     * @return array
     */
    protected function unsetSensitiveCardDetails(array $input)
    {
        if ((isset($input[Entity::CARD]) === true) and
            (is_array($input[Entity::CARD]) === true))
        {
            if (empty($input[Entity::CARD][Card\Entity::NUMBER]) === false)
            {
                $input[Entity::CARD][Card\Entity::IIN] = substr($input[Entity::CARD][Card\Entity::NUMBER], 0, 6);
            }

            unset($input[Entity::CARD][Card\Entity::CVV]);
            unset($input[Entity::CARD][Card\Entity::NUMBER]);
        }

        return $input;
    }
}
