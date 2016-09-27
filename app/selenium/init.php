<?php

use \App as App;
use \Mockery as Mockery;

$token = Mockery::mock('overload:OAuth\OAuth2\Token\StdOAuth2Token');
$token->shouldReceive('getAccessToken')->andReturn('token');
App::instance('OAuth\OAuth2\Token\StdOAuth2Token', $token);

$googleService = Mockery::mock('overload:OAuth\OAuth2\Service\Google');
$googleService->shouldReceive('getAuthorizationUri')->withAnyArgs()->andReturn('https://accounts.google.com');
$googleService->shouldReceive('requestAccessToken')->withAnyArgs()->andReturn($token);
$googleService->shouldReceive('request')->withAnyArgs()->andReturn('{
"id": "105723007478327237827",
"email": "testoauth@testcases.com",
"verified_email": true,
"name": "Test Login",
"given_name": "Test",
"family_name": "Login",
"picture": "https://lh3.googleusercontent.com/photo.jpg",
"locale": "en",
"hd": "razorpay.com"
}');

App::instance('OAuth\OAuth2\Service\Google', $googleService);

$oauth = Mockery::mock('overload:Artdarek\OAuth\OAuth');
$oauth->shouldReceive('consumer')->withAnyArgs()->andReturn($googleService);
App::instance('Artdarek\OAuth\OAuth', $oauth);

?>