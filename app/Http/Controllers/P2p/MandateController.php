<?php

namespace RZP\Http\Controllers\P2p;

use RZP\Models\P2p;
use RZP\Exception\RuntimeException;

/**
 * @property  P2p\Mandate\Service $service
 */
class MandateController extends Controller
{
    public function fetch()
    {
        $input['id'] = $this->request()->route('mandate_id');

        $response = $this->service->fetch($input);

        return $this->response($response);
    }

    public function fetchAll()
    {
        $input = $this->request()->all();

        $response = $this->service->fetchAll($input);

        return $this->response($response);
    }

    public function initiateAuthorize()
    {
        $input = $this->request()->all();

        $input['id'] = $this->request()->route('mandate_id');

        $response = $this->service->initiateAuthorize($input);

        return $this->response($response);
    }

    public function authorizeMandate()
    {
        $input = $this->request()->all();

        $input['id'] = $this->request()->route('mandate_id');

        $response = $this->service->authorizeMandate($input);

        return $this->response($response);
    }

    public function initiateReject()
    {
        $input = $this->request()->all();

        $input['id'] = $this->request()->route('mandate_id');

        $response = $this->service->initiateReject($input);

        return $this->response($response);
    }

    public function rejectMandate()
    {
        $input = $this->request()->all();

        $input['id'] = $this->request()->route('mandate_id');

        $response = $this->service->rejectMandate($input);

        return $this->response($response);
    }

    public function initiatePause()
    {
        $input = $this->request()->all();

        $input['id'] = $this->request()->route('mandate_id');

        $response = $this->service->initiatePause($input);

        return $this->response($response);
    }


    public function pauseMandate()
    {
        $input = $this->request()->all();

        $input['id'] = $this->request()->route('mandate_id');

        $response = $this->service->pauseMandate($input);

        return $this->response($response);
    }

    public function initiateUnPause()
    {
        $input = $this->request()->all();

        $input['id'] = $this->request()->route('mandate_id');

        $response = $this->service->initiateUnPause($input);

        return $this->response($response);
    }

    public function unpauseMandate()
    {
        $input = $this->request()->all();

        $input['id'] = $this->request()->route('mandate_id');

        $response = $this->service->unpauseMandate($input);

        return $this->response($response);
    }

    public function initiateRevoke()
    {
        $input = $this->request()->all();

        $input['id'] = $this->request()->route('mandate_id');

        $response = $this->service->initiateRevoke($input);

        return $this->response($response);
    }

    public function revokeMandate()
    {
        $input = $this->request()->all();

        $input['id'] = $this->request()->route('mandate_id');

        $response = $this->service->revokeMandate($input);

        return $this->response($response);
    }
}
