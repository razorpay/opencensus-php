<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Models\Transaction;

/**
 * Class StatementController
 *
 * @package RZP\Http\Controllers
 */
class StatementController extends Controller
{
    protected $service = Transaction\Statement\Service::class;

    use Traits\HasCrudMethods;

    /**
     * {@inheritDoc}
     */
    public function get(string $id)
    {
        $transaction = $this->service()->fetch($id, $this->input);

        return ApiResponse::json($transaction);
    }

    public function listForBanking()
    {
        $input = Request::all();

        $transaction = $this->service()->fetchMultipleForBanking($input);

        return ApiResponse::json($transaction);
    }
}
