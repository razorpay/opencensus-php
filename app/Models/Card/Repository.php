<?php

namespace RZP\Models\Card;

use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Models\Payment;

class Repository extends Base\Repository
{
    protected $entity = 'card';

    protected $appFetchParamRules = array(
        Entity::IIN             => 'sometimes|integer|digits:6',
        Entity::LAST4           => 'sometimes|string|digits:4',
        Entity::MERCHANT_ID     => 'sometimes|alpha_num',
        Entity::NETWORK         => 'sometimes|alpha_space',
        Entity::INTERNATIONAL   => 'sometimes|in:0,1',
        Payment\Entity::STATUS  => 'sometimes|string',
        Entity::EXPIRY_MONTH    => 'sometimes|integer|digits_between:1,2|max:12|min:1',
        Entity::EXPIRY_YEAR     => 'sometimes|integer|digits:4|non_past_year',
        Entity::VAULT_TOKEN     => 'sometimes|alpha_num',
        Entity::VAULT           => 'required_with:token|in:tokenex',
        Entity::GLOBAL_CARD_ID  => 'sometimes|alpha_num',
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
        return Card\IIN\Entity::find($iin);
    }

    public function getByParams($params)
    {
        $repo = $this->repo;

        $query = (new $repo)->newQuery();

        foreach ($params as $key => $value)
        {
            $query = $query->where($key, '=', $value);
        }

        return $query->get();
    }

    protected function addQueryParamInternational($query, $params)
    {
        $international = Card\Entity::getAttributeWithTableName(Entity::INTERNATIONAL);

        $query->where($international, '=', $params[Entity::INTERNATIONAL]);
    }

    protected function addQueryParamStatus($query, $params)
    {
        $status = $params[Payment\Entity::STATUS];
        $status = explode(',', $status);

        Payment\Validator::validateStatusArray($status);

        $paymentCardId = Payment\Entity::getAttributeWithTableName(Payment\Entity::CARD_ID);
        $cardId = Card\Entity::getAttributeWithTableName(Card\Entity::ID);

        $query->join(Payment\Entity::getTableName(), $paymentCardId, '=', $cardId)
              ->whereIn(Payment\Entity::STATUS, $status);

        $query->select($query->getModel()->getTable().'.*');
    }
}