<?php

namespace RZP\Models\PayoutLink\External;

use App;
use RZP\Trace\TraceCode;
use RZP\Exception\BadRequestException;
use RZP\Models\Contact\Entity as ContactEntity;
use RZP\Models\FundAccount\Core as FundAccountCore;
use RZP\Models\FundAccount\Entity as FundAccountEntity;
use RZP\Models\Merchant\Entity as MerchantEntity;

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
    protected $trace;

    protected $repo;

    public function __construct()
    {
        $this->trace = App::getFacadeRoot()['trace'];

        $this->repo = App::getFacadeRoot()['repo'];
    }

    public function processFundAccountInput(array $input,
                                            MerchantEntity $merchant,
                                            ContactEntity $contact): FundAccountEntity
    {
        $fundAccountId = array_pull($input, 'fund_account_id');

        if ($fundAccountId !== null)
        {
            $fundAccount = $this->repo->fund_account->findByIdAndMerchant($fundAccountId, $merchant);

            // verifying that the fund_account_id sent is same as the one associated with the payout-link
            // there is a possibility, that after token verification, one changes the fund_account_id just before add
            // and that fund_account doesn't belong to the intended contact. In this case we throw an exception
            if ($fundAccount->contact->getId() !== $contact->getId())
            {
                throw new BadRequestException(ErrorCode::BAD_REQUEST_AT_LEAST_ONE_OF_EMAIL_OR_PHONE_REQUIRED,
                                              null,
                                              [
                                                  'fund_account_id' => $fundAccountId,
                                                  'contact_id'      => $contact->getId()
                                              ]);
            }
        }
        else
        {
            $this->trace->info(TraceCode::PAYOUT_LINK_PROCESS_FUND_ACCOUNT_REQUEST,
                               $input);

            $fundAccount = (new FundAccountCore())->create($input, $merchant, $contact);

        }

        return $fundAccount;
    }

}
