<?php

namespace RZP\Models\FundAccount;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Contact;

/**
 * Class Repository
 *
 * @package RZP\Models\FundAccount
 */
class Repository extends Base\Repository
{
    protected $entity = 'fund_account';

    protected $expands = [
        Entity::ACCOUNT,
    ];

    public function findByPublicIdAndBeneficiaryAndMerchant(
        string $id,
        Contact\Entity $contact,
        Merchant\Entity $merchant,
        array $input = null): Entity
    {
        Entity::verifyIdAndStripSign($id);

        $query = $this->getQueryForFindWithParams($input);

        $fundAccount = $query->merchantId($merchant->getId())
                             ->where(Entity::CONTACT_ID, $contact->getId())
                             ->findOrFailPublic($id);

        return $fundAccount;
    }
}
