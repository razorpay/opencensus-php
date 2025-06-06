<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Illuminate\Http\JsonResponse;
use Request;
use RZP\Models\Customer\Token\Service;
use View;

class TokenController extends Controller
{
    public function create()
    {
        $input = Request::all();

        $data = $this->service()->createNetworkToken($input);

        return ApiResponse::json($data);
    }

    public function fetch()
    {
        $input = Request::all();

        $data = $this->service()->fetchNetworkToken($input);

        return ApiResponse::json($data);
    }

    public function fetchCryptoGram()
    {
        $input = Request::all();

        $data = $this->service()->fetchCryptoGram($input);

        return ApiResponse::json($data);
    }

    public function fetchCryptoGramInternal()
    {
        $input = Request::all();

        $data = $this->service()->fetchCryptoGramInternal($input);

        return ApiResponse::json($data);
    }

    public function fetchParValue()
    {
        $input = Request::all();

        $data = $this->service()->fetchParValue($input);

        return ApiResponse::json($data);
    }

    public function delete()
    {
        $input = Request::all();

        $data = $this->service()->deleteNetworkToken($input);

        return ApiResponse::json($data);
    }

    public function pauseNotSupportedCardTokens()
    {
        $input = Request::all();

        $data = $this->service()->pauseNotSupportedCardTokens($input);

        return ApiResponse::json($data);
    }

    public function tokensPush()
    {
        $input = Request::all();

        if( $input['requester']=='wibmo'){
            $data = $this->service()->hdfcPushProvTokens($input);
            return ApiResponse::json($data);
        }



        return $this->app['card.cardVault']->migrateToTokenizedCard($input);
       // $data = $this->service()->tokensPush($input);
        //

       // return ApiResponse::json($data);
    }
    public function rupaytokensPush()
    {
        $input = Request::all();

        $data = $this->service()->rupaytokensPush($input);

        return ApiResponse::json($data);
    }

    public function tokensList()
    {
        $input = Request::all();

        $data = $this->service()->fetchMerchantsWithTokenPresent($input);

        return ApiResponse::json($data);
    }

    public function tokensPushFetch($id)
    {
        $data = $this->service()->tokensPushFetch($id);

        return ApiResponse::json($data);
    }

    public function vcppTokensPush()
    {
        $input = Request::all();

        $data = $this->service()->vcppTokensPush($input);

        return ApiResponse::json($data);
    }

    public function updateStatus()
    {
        $input = Request::all();

        $data = $this->service()->updateStatus($input);

        return ApiResponse::json($data);
    }

    public function updateTokenOnAuthorized()
    {
        $input = Request::all();

        $data = $this->service()->updateTokenOnAuthorized($input);

        return ApiResponse::json($data);
    }

    public function recurringTokenPreDebitNotify($id)
    {
        $input = Request::all();

        $data = $this->service()->recurringTokenPreDebitNotify($id, $input);

        return ApiResponse::json($data);
    }

    public function localSavedCardAsyncTokenisation()
    {
        $data = $this->service()->localSavedCardAsyncTokenisation();

        return ApiResponse::json($data);
    }

    public function localSavedCardAsyncTokenisationRecurring()
    {
        $data = $this->service()->localSavedCardAsyncTokenisationRecurring();

        return ApiResponse::json($data);
    }

    public function localSavedCardBulkTokenisation()
    {
        $input = Request::all();

        $data = $this->service()->localSavedCardBulkTokenisation($input);

        return ApiResponse::json($data);
    }

    public function globalSavedCardAsyncTokenisation()
    {
        $input = Request::all();

        $data = $this->service()->globalSavedCardAsyncTokenisation($input);

        return ApiResponse::json($data);
    }

    public function migrateVaultTokenViaBatch()
    {
        $input = Request::all();

        $data = $this->service()->migrateVaultTokenViaBatch($input);

        return ApiResponse::json($data);
    }

    public function tokenHqChargeProcessingViaBatch()
    {
        $input = Request::all();

        $data = $this->service()->tokenHqChargeProcessingViaBatch($input);

        return ApiResponse::json($data);
    }


    public function tokenHqCron()
    {
        $input = Request::all();

        $data = $this->service()->tokenHqCron($input);

        return ApiResponse::json($data);
    }

    public function bulkCreateLocalTokensFromConsents()
    {
        $input = Request::all();

        $data = $this->service()->bulkCreateLocalTokensFromConsents($input);

        return ApiResponse::json($data);
    }

    public function globalCustomerLocalSavedCardAsyncTokenisation()
    {
        $input = Request::all();

        $data = $this->service()->globalCustomerLocalSavedCardAsyncTokenisation($input);

        return ApiResponse::json($data);
    }

    /**
     * @return JsonResponse
     *
     * @see Service::fetchLocalOrGlobalCustomerTokens()
     */
    public function fetchCustomerTokensInternal(): JsonResponse
    {
        $input = Request::all();

        $data = $this->service()->fetchLocalOrGlobalCustomerTokens($input);

        return ApiResponse::json($data);
    }

    public function internalTokenCreateForRearch()
    {
        $input = Request::all();

        $data = $this->service()->createTokenForRearch($input);

        return ApiResponse::json($data);

    }

    public function fetchTokenCardInternal()
    {
        $input = Request::all();

        $data = $this->service()->fetchTokenCardInternal($input);

        return ApiResponse::json($data);
    }

    public function createTokenOptimizerInternal()
    {
        $input = Request::all();

        $data = $this->service()->createTokenOptimizerInternal($input);

        return ApiResponse::json($data);
    }

    public function internalRecurringMethodDetailsFetch()
    {
        $input = Request::all();

        $data = $this->service()->internalRecurringMethodDetailsFetch($input);

        return ApiResponse::json($data);
    }
}
