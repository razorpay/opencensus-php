<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;
use RZP\Models\Merchant;

class AccountController extends Controller
{
	protected $service;

    public function __construct()
    {
        parent::__construct();

        $this->service = new Merchant\Service;
    }

    public function getAccounts()
    {
    	$input = Request::all();

    	$accounts = $this->service->fetchAccountMultiple($input);

    	return ApiResponse::json($accounts);
    }

    public function getAccount(string $id)
    {
    	$account = $this->service->fetchAccount($id);

    	return ApiResponse::json($account);
    }

    public function postAccount()
    {
    	$input = Request::all();

    	$account = $this->service->create($input);

    	return ApiResponse::json($account);
    }

    public function postAccountFile(string $id, string $type)
    {
    	;
    }

    public function patchAccount()
    {
    	$input = Request::all();

    	$account = $this->service->edit($input);

    	return ApiResponse::json($account);
    }

    public function patchAccountFile(string $id, string $type)
    {
    	;
    }
}
