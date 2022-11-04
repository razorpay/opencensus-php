<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Models\Partner;

/**
 * Class PartnershipsAdhocMigrationsController
 * This class can be used as a adhoc migration controller for partnerships team.
 * we can implement all new migration endpoints in this controller
 *
 * @package RZP\Http\Controllers
 */
class PartnershipsAdhocMigrationsController extends Controller
{
    public function regenerateReferralLinks()
    {
        $input = Request::all();

        return (new Partner\Service())->regeneratePartnerReferralLinks($input);
    }
}
