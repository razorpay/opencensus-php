<?php

namespace RZP\Http\Controllers\P2p;

class CustomerController extends Controller
{
    public function startVerification()
    {
        $input = $this->request()->all();

        return $this->response([
            'handle'            => $input['handle'],
            'token'             => str_random(10),
            'action'            => 'verify',
            'method'            => 'sms',
            'sms_destination'   => '+919876543210',
            'sms_content'       => 'VERIFY ME'
        ]);
    }

    public function verificationStatus()
    {
        $token = $this->request()->route('token');

        return $this->response([
            'status'        => 'pending',
            'status_url'    => url($token),
            'expire_at'     => time() + 900,
        ]);
    }

    public function create()
    {
        $input = $this->request()->all();

        return $this->response([
            'id'            => 'cust_bahuthuyehumen',
            'contact'       => '+919876543210',
            'email'         => $input['email'],
            'active'        => true,
            'notes'         => $input['notes'],
            'created_at'    => time(),
        ]);
    }
}
