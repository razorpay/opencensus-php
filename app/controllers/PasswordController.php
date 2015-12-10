<?php

class PasswordController extends BaseController
{
	/**
	 * Handle a POST request to remind a user of their password.
	 *
	 * @return Response
	 */
	public function postRemind()
	{	
		$response = Password::merchant()->remind(Input::only('email'), function($message){
			$message->subject('Razorpay - Password Reset Request'); 
		});

		switch ($response)
		{
			case Password::INVALID_USER:
				return Response::json(array('success' => false, 'errors' => array(Lang::get($response))));

			case Password::REMINDER_SENT:
				return Response::json(array('success' => true));
		}
	}


	/**
	 * Handle a POST request to reset a user's password.
	 *
	 * @return Response
	 */
	public function postReset()
	{
		$credentials = Input::only(
			'email', 'password', 'password_confirmation', 'token'
		);

		$response = Password::merchant()->reset($credentials, function($merchant, $password)
		{
			$merchant->password = $password;
			$merchant->save();

			if($merchant->hasUsers())
			{
				$user = $merchant->users()->first();
				$user->password = $merchant->password;
				$user->save();
			}
		});

		switch ($response)
		{
			case Password::INVALID_PASSWORD:
			case Password::INVALID_TOKEN:
			case Password::INVALID_USER:
				return Response::json(array('success' => false, 'errors' => array(Lang::get($response))));

			case Password::PASSWORD_RESET:
				return Response::json(array('success' => true));
		}
	}

}
