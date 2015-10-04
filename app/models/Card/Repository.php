<?php

namespace Models\Card;

use Models\Base;
use Models\Card;
use Models\Payment;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'Card';

    protected $appFetchParamRules = array(
        Entity::IIN             => 'sometimes|integer|digits:6',
        Entity::LAST4           => 'sometimes|integer|digits:4',
        Entity::MERCHANT_ID     => 'sometimes|alpha_num',
        Entity::NETWORK         => 'sometimes|alpha_space',
        Payment\Entity::STATUS  => 'sometimes|string',
    );

    public function retrieveIinDetails($iin)
    {
        if (strlen($iin) > 6)
        {
            $iin = intval(substr($iin, 0, 6));
        }

        //
        // retrieve iin details
        //
        return Card\Detail::find($iin);
    }

    protected function addQueryParamStatus($query, $params)
    {
        $status = $params[Entity::STATUS];
        $status = explode(',', $status);

        Payment\Validator::validateStatusArray($status);

        $query->join(
            Card\Entity::getTableName(),
            function ($join) use ($params)
            {
                $paymentCardId = Payment\Entity::getAttributeWithTableName(Payment\Entity::CARD_ID);
                $cardId = Methods\Entity::getAttributeWithTableName(Card\Entity::ID);

                $join->on($paymentCardId, '=', $cardId)
                     ->whereIn(Payment\Entity::STATUS, $status);
            });

        $query->select($query->getModel()->getTable().'.*');
    }
}