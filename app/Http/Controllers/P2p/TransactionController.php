<?php

namespace Rzp\Http\Controllers\P2p;

use RZP\Models\P2p;

/**
 * @property $service P2p\Transaction\Service
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
        $input = $this->request()->all();

        $response = $this->service->fetch($input);

        return $this->response($response);
    }

    public function initiateAuthorize()
    {
        $input = $this->request()->all();

        $response = $this->service->initiateAuthorize($input);

        return $this->response($response);
    }

    public function authorize()
    {
        $input = $this->request()->all();

        $response = $this->service->authorize($input);

        return $this->response($response);
    }

    public function reject()
    {
        $input = $this->request()->all();

        $response = $this->service->reject($input);

        return $this->response($response);
    }
}
