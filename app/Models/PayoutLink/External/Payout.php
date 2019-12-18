<?php

namespace RZP\Models\PayoutLink\External;

/**
 * Class Payout
 * This class will be used to interact with the Payouts Module.
 * Ideally these should be API calls, but as they are in the same repo, we will be making direct function calls
 * When this module moves out, we will replace function calls with API calls
 */

class Payout
{
    protected $trace;

    protected $repo;

    public function __construct()
    {
        $this->trace = App::getFacadeRoot()['trace'];

        $this->repo = App::getFacadeRoot()['repo'];
    }

    public function processPayout()
    {
    }
}
