<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use RZP\Constants\HyperTrace;
use RZP\Models\Merchant;
use RZP\Trace\Tracer;
use RZP\Constants\Entity as E;

class AccountController extends Controller
{
    use Traits\HasCrudMethods;

    protected $service = Merchant\Account\Service::class;

    public function createLinkedAccount()
    {
        $input = Request::all();

        $entity = $this->service()->createLinkedAccount($input);

        return ApiResponse::json($entity);
    }

    /**
     * Return all settlement destinations.
     * Currently, bank accounts are the only settlement destinations, later,
     * more types (e.g. wallet, upi etc) can come.
     *
     * @param string $accountId
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function fetchSettlementDestinations(string $accountId)
    {
        $response = $this->service()->fetchSettlementDestinations($accountId);

        return ApiResponse::json($response);
    }

    /**
     * Adds / updates a bank account that is linked to the merchant account
     *
     * @param string $accountId
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function createOrChangeBankAccount(string $accountId)
    {
        $response = $this->service()->createOrChangeBankAccount($accountId, $this->input);

        return ApiResponse::json($response);
    }

    public function listLinkedAccounts()
    {
        $input = Request::all();

        $response = $this->service()->listLinkedAccounts($input);

        return ApiResponse::json($response);
    }

    public function createAccount()
    {
        $input = Request::all();

        $response = Tracer::inspan(['name' => HyperTrace::CREATE_ACCOUNTS], function () use ($input) {

            return $this->service()->createAccount($input);
        });

        return ApiResponse::json($response);
    }

    /**
     * @param string $accountId
     * @param string $action
     *
     * @return mixed
     */
    public function performAction(string $accountId, string $action)
    {
        $response = $this->service()->performAction($accountId, $action);

        return ApiResponse::json($response);
    }

    public function fetchAccount(string $accountId)
    {
        $response = Tracer::inspan(['name' => HyperTrace::FETCH_ACCOUNTS], function () use ($accountId) {

            return $this->service()->fetchAccount($accountId);
        });

        return ApiResponse::json($response);
    }

    public function fetchByExternalId(string $externalId)
    {
        $response = Tracer::inspan(['name' => HyperTrace::FETCH_ACCOUNTS_BY_EXTERNAL_ID], function () use ($externalId) {

            return $this->service()->fetchAccountByExternalId($externalId);
        });

        return ApiResponse::json($response);
    }

    public function editAccount(string $accountId)
    {
        $input = Request::all();

        $response = Tracer::inspan(['name' => HyperTrace::EDIT_ACCOUNTS], function () use ($accountId, $input) {

            return $this->service()->editAccount($accountId, $input);
        });

        return ApiResponse::json($response);
    }

    public function listAccounts()
    {
        $input = Request::all();

        $response = Tracer::inspan(['name' => HyperTrace::LIST_ACCOUNTS], function () use ($input) {

            return $this->service()->listAccounts($input);
        });

        return ApiResponse::json($response);
    }

    public function fetchLinkedAccountsForMerchant(string $merchantId)
    {
        $input = Request::all();

        $response = $this->service()->fetchLinkedAccountsForMerchant($input, $merchantId);

        return ApiResponse::json($response);
    }

    public function createLinkedAccountReferenceData()
    {
        $input = Request::all();

        $response = $this->service(E::LINKED_ACCOUNT_REFERENCE_DATA)->createLinkedAccountReferenceData($input);

        return ApiResponse::json($response);
    }

    public function createAMCLinkedAccountViaAdmin()
    {
        $input = Request::all();

        $response = $this->service()->createAMCLinkedAccountViaAdmin($input);

        return ApiResponse::json($response);
    }

    public function updateLinkedAccountReferenceData(string $linkedAccountRefId)
    {
        $input = Request::all();

        $response = $this->service(E::LINKED_ACCOUNT_REFERENCE_DATA)->editLinkedAccountReferenceData($linkedAccountRefId, $input);

        return ApiResponse::json($response);
    }
}
