<?php

class MerchantController extends BaseController {

	public function getIndex()
	{
		return View::make('merchants.getIndex');
	}

	public function getLogin()
	{
		return View::make('merchants.getLogin');
	}

	public function getRegister()
	{
		return View::make('merchants.getRegister');
	}

}
