<?php

class PasswordController extends BaseController {

	/**
	 * Display the password reminder view.
	 *
	 * @return Response
	 */
	public function getRemind()
	{
		return View::make('password.remind');
	}

	/**
	 * Handle a POST request to remind a user of their password.
	 *
	 * @return Response
	 */
	public function postRemind()
	{
		switch ($response = Password::merchant()->remind(Input::only('email')))
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

		$response = Password::merchant()->reset($credentials, function($user, $password)
		{
			$user->password = Hash::make($password);

			$user->save();
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
