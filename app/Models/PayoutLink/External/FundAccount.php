<?php

namespace RZP\Models\PayoutLink\External;

use App;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\PayoutLink\Entity;
use RZP\Exception\BadRequestException;
use RZP\Models\Contact\Entity as ContactEntity;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\FundAccount\Core as FundAccountCore;
use RZP\Models\FundAccount\Entity as FundAccountEntity;

/**
 * This class will hold all interaction of PayoutLink with FundAccount.
 * It will act as a layer which should ideally be replaced with API calls, but as both modules
 * share the same repo, we will be making direct function calls.
 * On moving payout-links outside api, only this file will need changing
 *
 * Class FundAccount
 * @package RZP\Models\PayoutLink\External
 */
class FundAccount
{
    protected $repo;

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->repo = $app['repo'];
    }

    public function processFundAccountInput(array $input,
                                            MerchantEntity $merchant,
                                            ContactEntity $contact): FundAccountEntity
    {
        // Fund_account_id will not be present when a new fund-account needs to be created before it
        // can be linked to the payout-link.
        // When a pre-existing fund-account is selected, then we get the fund-account-id
        if (array_key_exists(Entity::FUND_ACCOUNT_ID, $input) === true)
        {
            $fundAccountId = $input[Entity::FUND_ACCOUNT_ID];

            $fundAccount = $this->repo->fund_account->findByPublicIdAndMerchant($fundAccountId, $merchant);

            // verifying that the fund_account_id sent is same as the one associated with the payout-link
            // there is a possibility, that after OTP verification, one changes the fund_account_id just before add
            // and that fund_account doesn't belong to the intended contact. In this case we throw an exception
            if ($fundAccount->contact->getId() !== $contact->getId())
            {
                throw new BadRequestException(ErrorCode::BAD_REQUEST_FUND_ACCOUNT_DOESNT_BELONG_TO_INTENDED_CONTACT,
                                              null,
                                              [
                                                  Entity::FUND_ACCOUNT_ID => $fundAccountId,
                                                  Entity::CONTACT_ID      => $contact->getId()
                                              ]);
            }
        }
        else
        {
            $fundAccount = (new FundAccountCore())->create($input, $merchant, $contact);
        }

        return $fundAccount;
    }
}
