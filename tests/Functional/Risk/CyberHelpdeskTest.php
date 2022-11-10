<?php

namespace Functional\Risk;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Models\Admin\Permission\Name as PermissionName;

class CyberHelpdeskTest extends TestCase
{
    use RequestResponseFlowTrait;

    protected $client;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testProxyRequest()
    {
        $admin = $this->ba->getAdmin();

        $role = $admin->roles()->get()[0];

        $perm = $this->fixtures->create('permission', ['name' => PermissionName::CREATE_CYBER_HELPDESK_WORKFLOW]);

        $role->permissions()->attach($perm->getId());

        $this->ba->adminAuth();

        $request = [
            'content' => [
                "payment_requests" => [
                    [
                        "method"      => "upi",
                        "reference16" => "123456",
                        "vpa"         => "upi_payments@ybl",
                        "base_amount" => 100099,
                        "from"        => 1618161012,
                        "to"          => 1618191012
                    ],
                    [
                        "method"      => "netbanking",
                        "reference1"  => "123456",
                        "base_amount" => 100099,
                        "from"        => 1618161012,
                        "to"          => 1618191012
                    ]
                ],
                "files"            => [
                    [
                        "file_id"       => "file_Kdtpsn5MxCCBS4",
                        "document_type" => "fir"
                    ]
                ],
            ],
            'url'     => '/cyber_helpdesk/ticket',
            'method'  => 'post',
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals($request['content'], $response['json']);

        $this->assertEquals("superadmin@razorpay.com", $response['headers']['admin_email_id']);
    }
}
