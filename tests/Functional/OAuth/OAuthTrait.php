<?php

namespace RZP\Tests\Functional\OAuth;

use Razorpay\OAuth\Application;
use Razorpay\OAuth\Client;

trait OAuthTrait
{
    public function createOAuthApplication(array $attributes = [])
    {
        // Create Application
        $application = factory(Application\Entity::class)->create($attributes);

        // Create dev Client for the Application
        factory(Client\Entity::class)->create(
            [
                'application_id' => $application->id,
                'environment'    => 'dev'
            ]);

        // Create prod Client for the Application
        factory(Client\Entity::class)->create(
            [
                'application_id' => $application->id,
                'redirect_url'   => ['https://www.example.com'],
                'environment'    => 'prod'
            ]);
    }
}
