<?php

namespace RZP\Http\Controllers\P2p;

use RZP\Models\P2p;

/**
 * @property  P2p\Transaction\Service $service
*/
class TransactionController extends Controller
{
    public function initiatePay()
    {
        $input = $this->request()->all();

        $response = $this->service->initiatePay($input);

        return $this->response($response);
    }

    public function initiateCollect()
    {
        $input = $this->request()->all();

        $response = $this->service->initiateCollect($input);

        return $this->response($response);
    }

    public function fetchAll()
    {
        $input = $this->request()->all();

        $response = $this->service->fetchAll($input);

        return $this->response($response);
    }

    public function fetch()
    {
        $input['id'] = $this->request()->route('transaction_id');

        $response = $this->service->fetch($input);

        return $this->response($response);
    }

    public function initiateAuthorize()
    {
        $input['id'] = $this->request()->route('transaction_id');

        $response = $this->service->initiateAuthorize($input);

        return $this->response($response);
    }

    public function authorizeTransaction()
    {
        $input = $this->request()->all();

        $input['id'] = $this->request()->route('transaction_id');

        $response = $this->service->authorizeTransaction($input);

        return $this->response($response);
    }

    public function initiateReject()
    {
        $input = $this->request()->all();

        $input['id'] = $this->request()->route('transaction_id');

        $response = $this->service->initiateReject($input);

        return $this->response($response);
    }

    public function reject()
    {
        $input = $this->request()->all();

        $input['id'] = $this->request()->route('transaction_id');

        $response = $this->service->reject($input);

        return $this->response($response);
    }

    public function raiseConcern()
    {
        $input = $this->request()->all();

        $input['id'] = $this->request()->route('transaction_id');

        $response = $this->service->raiseConcern($input);

        return $this->response($response);
    }

    public function concernStatus()
    {
        $input = $this->request()->all();

        $input['id'] = $this->request()->route('transaction_id');

        $response = $this->service->concernStatus($input);

        return $this->response($response);
    }

    public function fetchAllConcerns()
    {
        $input = $this->request()->all();

        $response = $this->service->fetchAllConcerns($input);

        return $this->response($response);
    }
}
