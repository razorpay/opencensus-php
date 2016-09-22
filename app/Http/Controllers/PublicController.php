<?php

namespace RZP\Http\Controllers;

use View;

class PublicController extends Controller
{
    public function getAccount()
    {
        $prodCdnUrl = $this->config->get('url.cdn')['production'];

        $data = [
            'static'    => $prodCdnUrl.'/account',
            'checkout'  => $this->config->get('app.checkout'),
            'api'       => $this->config->get('app.url'),
        ];

        return View::make('public.account', $data);
    }
}
