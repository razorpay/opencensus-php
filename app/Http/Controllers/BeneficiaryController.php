<?php

namespace RZP\Http\Controllers;

use ApiResponse;

use RZP\Models\Beneficiary;

/**
 * Class BeneficiaryController
 *
 * @package RZP\Http\Controllers
 */
class BeneficiaryController extends Controller
{
    use Traits\HasCrudMethods;

    protected $service = Beneficiary\Service::class;
}
