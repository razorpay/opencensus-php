<?php

namespace RZP\Http\Controllers\P2p;

use RZP\Models\P2p;
use RZP\Services\Pspx;

/**
 * @property  P2p\Preferences\Service $service
 */
class PreferencesController extends Controller
{
    public function getPreferences()
    {
        $input = $this->request()->all();

        $response = $this->service->getPreferences($input);

        return $this->response($response);
    }
}
