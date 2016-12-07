<?php

namespace RZP\Tests\Functional\Helpers;

use RZP\Http\BasicAuth\BasicAuth;
use RZP\Exception\BaseException;
use RZP\Exception;
use RZP\Error\ErrorCode;
use Mockery;
use Requests;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\EntityActionTrait;

trait HeimdallTrait
{
    use EntityActionTrait;

    use RequestResponseFlowTrait
    {
        sendRequest as makeRequestParent;
    }

    protected function deleteAdmin($orgId, $adminId)
    {
        $request = array(
            'url' => '/orgs/' . $orgId . '/admins/' . $adminId,
            'method' => 'DELETE');

        $this->ba->adminAuth();

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }
}
