<?php

namespace RZP\Tests\Functional\Helpers;

use RZP\Models;
use RZP\Models\PaymentsUpi;

trait PaymentsUpiTrait
{
    public function createUpiPaymentsGlobalCustomerVpa($attributes = [])
    {
        $this->app['basicauth']->setModeAndDbConnection('test');

        $vpa = new PaymentsUpi\Vpa\Entity();

        $vpa->forceFill(array_merge([
            'id'            => '1000000000gupi',
            'username'      => 'globaluser',
            'handle'        => 'icici',
            'name'          => 'globaluser',
        ], $attributes));

        $vpa->save();

        return $vpa;
    }

    public function createUpiPaymentsLocalCustomerVpa($attributes = [])
    {
        $this->app['basicauth']->setModeAndDbConnection('test');

        $vpa = new PaymentsUpi\Vpa\Entity();

        $vpa->forceFill(array_merge([
            'id'            => '10000000000vpa',
            'username'      => 'localuser',
            'handle'        => 'icici',
            'name'          => 'localuser',
        ], $attributes));

        $vpa->save();

        return $vpa;
    }

    /************************* HELPERS ****************************/

    protected function validateVpa($vpa, $success = true)
    {
        $this->fixtures->merchant->addFeatures(['enable_vpa_validate']);

        $request = [
            'url'       => '/payment/validate/vpa',
            'method'    => 'post',
            'content'   => [
                'vpa' => $vpa,
            ]
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestAndGetContent($request);

        if (is_bool($success) === true)
        {
            $this->assertSame($success, $response['success']);
        }
    }
}
