<?php

namespace RZP\Models\Merchant\AccountV2;

use RZP\Constants\HyperTrace;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Account;
use RZP\Models\Merchant\Account\Action;
use RZP\Models\Merchant\Account\Entity;
use RZP\Trace\TraceCode;
use RZP\Trace\Tracer;

class Service extends Merchant\Service
{
    protected $response;

    public function createAccountV2(array $input): array
    {
        $account = Tracer::inspan(['name' => HyperTrace::CREATE_ACCOUNT_V2_CORE], function () use ($input) {

            return $this->core()->createAccountV2($this->merchant, $input);
        });

        return $this->getResponseObject()->getAccountResponse($this->merchant, $account);
    }

    public function fetchAccountV2(string $accountId): array
    {
        $account = Tracer::inspan(['name' => HyperTrace::FETCH_ACCOUNT_V2_CORE], function () use ($accountId) {

            return $this->core()->fetchAccountV2($accountId);
        });

        return $this->getResponseObject()->getAccountResponse($this->merchant, $account);
    }

    public function editAccountV2(string $accountId, array $input): array
    {
        $account = Tracer::inspan(['name' => HyperTrace::EDIT_ACCOUNT_V2_CORE], function () use ($accountId, $input) {

            return $this->core()->editAccountV2($this->merchant, $accountId, $input);
        });

        return $this->getResponseObject()->getAccountResponse($this->merchant, $account);
    }

    public function deleteAccountV2(string $accountId)
    {
        $accountCoreV1 = new Merchant\Account\Core();

        $accountCoreV1->validatePartnerAccess($this->merchant, $accountId);

        $input = [
            Merchant\Entity::ACTION => Action::validateInputAndGetAccountAction(Account\Action::DISABLE),
        ];

        $this->trace->info(TraceCode::ACCOUNT_DELETE_ACTION,
            [
                'account_id' => $accountId,
                'input'      => $input,
            ]);

        Entity::verifyIdAndStripSign($accountId);

        $account = $this->repo->merchant->findOrFail($accountId);

        $account = Tracer::inspan(['name' => HyperTrace::ACCOUNT_V2_DISABLE], function () use ($accountCoreV1, $account, $input) {

            return $accountCoreV1->action($account, $input, false);
        });

        $merchantDetails = $account->merchantDetail;

        $dimensions = [
            'partner_type'          => $this->merchant->getPartnerType(),
            'account_type'          => ($merchantDetails->merchant->isLinkedAccount() === true) ? Type::ROUTE : Type::STANDARD,
            'submerchant_business_type' => $merchantDetails->getBusinessType()
        ];

        $this->trace->count(Metric::ACCOUNT_V2_DELETE_SUCCESS_TOTAL, $dimensions);

        return $this->getResponseObject()->getAccountResponse($this->merchant, $account);
    }

    protected function getResponseObject()
    {
        if ($this->response === null)
        {
            return new Response;
        }

        return $this->response;
    }
}
