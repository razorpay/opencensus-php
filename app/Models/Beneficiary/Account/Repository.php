<?php

namespace RZP\Models\Beneficiary\Account;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Beneficiary;

/**
 * Class Repository
 *
 * @package RZP\Models\Beneficiary\Account
 */
class Repository extends Base\Repository
{
    protected $entity = 'beneficiary_account';

    protected $expands = [
        Entity::ACCOUNT,
    ];

    public function findByPublicIdAndBeneficiaryAndMerchant(
        string $id,
        Beneficiary\Entity $bene,
        Merchant\Entity $merchant,
        array $input = null): Entity
    {
        Entity::verifyIdAndStripSign($id);

        $query = $this->getQueryForFindWithParams($input);

        $beneAccount = $query->merchantId($merchant->getId())
                             ->where(Entity::BENEFICIARY_ID, $bene->getId())
                             ->findOrFailPublic($id);

        return $beneAccount;
    }
}
