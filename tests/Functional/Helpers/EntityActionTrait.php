<?php

namespace RZP\Tests\Functional\Helpers;

use Requests;
use Symfony\Component\DomCrawler\Crawler;

use RZP\Models\Merchant\Account;
use RZP\Exception\BaseException;
use RZP\Tests\Functional\RequestResponseFlowTrait;

trait EntityActionTrait
{
    protected function deleteTerminal($mid, $tid)
    {
        $request = array(
            'url' => '/merchants/'.$mid.'/terminals/'.$tid,
            'method' => 'delete');

        $this->ba->getAdmin()->merchants()->attach($mid);

        $this->ba->adminAuth();

        return $this->makeRequestAndGetContent($request);
    }

    protected function deleteTerminal2($tid)
    {
        $request = array(
            'url' => '/terminals/'.$tid,
            'method' => 'delete');

        $this->ba->adminAuth();

        return $this->makeRequestAndGetContent($request);
    }

    protected function deleteTerminalv3($mid, $tid)
    {
        $request = array(
            'url' => '/merchants/'.$mid.'/terminals/'.$tid.'/v3',
            'method' => 'delete');

        $this->ba->getAdmin()->merchants()->attach($mid);

        $this->ba->adminAuth();

        return $this->makeRequestAndGetContent($request);
    }

    protected function validateDeleteTerminalv3($mid, $tid)
    {
        $request = array(
            'url' => '/merchants/'.$mid.'/terminals/'.$tid.'/validatedeletev3',
            'method' => 'post');

        $this->ba->getAdmin()->merchants()->attach($mid);

        $this->ba->adminAuth();

        return $this->makeRequestAndGetContent($request);
    }

    protected function restoreTerminal($tid)
    {
        $request = array(
            'url' => '/terminals/'.$tid.'/restore',
            'method' => 'put');

        $this->ba->adminAuth();

        return $this->makeRequestAndGetContent($request);
    }

    protected function editTerminal($tid, $input)
    {
        $request = array(
            'url' => '/terminals/'.$tid,
            'method' => 'put',
            'content' => $input);

        $this->ba->adminAuth();

        return $this->makeRequestAndGetContent($request);
    }

    protected function editTerminalExternalOrg($tid, $input)
    {
        $request = array(
            'url' => '/terminals/'.$tid . '/external_org',
            'method' => 'put',
            'content' => $input);

        return $this->makeRequestAndGetContent($request);
    }
    protected function copyTerminal($tid, $mid, $input)
    {
        $request = array(
            'url' => '/merchants/'. $mid .'/terminals/'. $tid .'/copy',
            'method' => 'post',
            'content' => $input);

        $this->ba->adminAuth();

        return $this->makeRequestAndGetContent($request);
    }

    protected function addAmountCredits(array $input = array(), $mid = Account::TEST_ACCOUNT)
    {
        $input['type'] = 'amount';

        $request = array(
            'url' => '/merchants/'.$mid.'/credits_log',
            'method' => 'POST',
            'content' => $input);

        $this->ba->adminAuth();

        return $this->makeRequestAndGetContent($request);
    }

    protected function addFeeCredits(array $input = array(), $mid = Account::TEST_ACCOUNT)
    {
        $input['type'] = 'fee';

        $request = array(
            'url' => '/merchants/'.$mid.'/credits_log',
            'method' => 'POST',
            'content' => $input);

        $this->ba->adminAuth();

        return $this->makeRequestAndGetContent($request);
    }

    protected function addCredits(array $input = [], $mid = Account::TEST_ACCOUNT, $mode = 'test')
    {
        $defaultInput = [
            'type'     => 'amount',
            'value'    => 25,
            'campaign' => 'silent-ads',
        ];

        $input = array_merge($defaultInput, $input);

        $request = array(
            'url' => '/merchants/'.$mid.'/credits_log',
            'method' => 'POST',
            'content' => $input);

        $this->ba->adminAuth($mode, null);

        return $this->makeRequestAndGetContent($request);
    }

    protected function editCredits($creditsId, array $input = array(), $mid = '10000000000000')
    {
        $request = array(
            'url' => '/merchants/'.$mid.'/credits/'.$creditsId,
            'method' => 'PUT',
            'content' => $input);

        $this->ba->adminAuth();

        return $this->makeRequestAndGetContent($request);
    }

    protected function merchantAssignPricingPlan($planId, $id = '10000000000000')
    {
        $request = [
            'url' => '/merchants/'.$id.'/pricing',
            'method' => 'POST',
            'content' => ['pricing_plan_id' => $planId]
        ];

        return $this->makeRequestAndGetContent($request);
    }

    protected function fetchMerchantPricingPlan( $id = '10000000000000')
    {
        $request = [
            'url' => '/merchants/'.$id.'/pricing',
            'method' => 'GET',
        ];

        return $this->makeRequestAndGetContent($request);
    }

    protected function merchantEditCredits($id, $credits)
    {
        $request = array(
            'url' => '/merchants/'.$id.'/credits',
            'method' => 'post',
            'content' => ['credits' => $credits]);

        $this->ba->adminAuth();

        return $this->makeRequestAndGetContent($request);
    }

    protected function fetchReport($entity, $content, $id = Account::TEST_ACCOUNT)
    {
        $request = array(
            'url' => '/reports/'.$entity,
            'method' => 'get',
            'content' => $content);

        $this->ba->proxyAuth();

        return $this->makeRequestAndGetContent($request);
    }

    protected function fetchMonthlyTransactionsReport($content, $id = Account::TEST_ACCOUNT)
    {
        $request = array(
            'url' => '/transactions/report',
            'method' => 'get',
            'content' => $content);

        $this->ba->proxyAuth();

        return $this->makeRequestAndGetContent($request);
    }

    protected function fetchReportAsFile($entity, $content, $id = Account::TEST_ACCOUNT)
    {
        $request = array(
            'url' => '/reports/'.$entity.'/file',
            'method' => 'get',
            'content' => $content);

        $this->ba->proxyAuth();

        return $this->makeRequestAndGetContent($request);
    }

    protected function fetchBalance($mid = Account::TEST_ACCOUNT)
    {
        return $this->getEntityById('balance', $mid, true);
    }

    protected function fetchInvoice(array $input)
    {
        $request = [
            'url'       => '/reports/invoice',
            'method'    => 'GET',
            'content'   => $input
        ];

        $this->ba->proxyAuth();

        return $this->makeRequestAndGetContent($request);
    }

    protected function createOrder(array $input = [])
    {
        $defaultInput = [
            'amount'        => 50000,
            'currency'      => 'INR',
            'receipt'       => random_int(1000, 99999),
        ];

        $input = array_merge($defaultInput, $input);

        $request = [
            'url'       => '/orders',
            'method'    => 'POST',
            'content'   => $input
        ];

        $this->ba->privateAuth();

        return $this->makeRequestAndGetContent($request);
    }

    protected function getPaymentMethods()
    {
        $request = [
            'url' => '/methods',
            'method' => 'get',
        ];

        return $this->makeRequestAndGetContent($request);
    }

    protected function setPaymentMethods($methods, $merchantId = '10000000000000')
    {
        $this->ba->adminAuth();

        $admin = $this->ba->getAdmin();

        $admin->merchants()->attach($merchantId);

        $request = [
            'url' => '/merchants/'.$merchantId.'/methods',
            'method' => 'put',
            'content' => $methods
        ];

        return $this->makeRequestAndGetContent($request);
    }

    protected function generateEntityReport($entity, $content)
    {
        $request = [
            'url' => '/reports/' .$entity. '/generate',
            'method' => 'post',
            'content' => $content
        ];

        $this->ba->proxyAuth();

        return $this->makeRequestAndGetContent($request);
    }

    protected function fetchReports($content)
    {
        $request = array(
            'url' => '/reports',
            'method' => 'get',
            'content' => $content);

        $this->ba->proxyAuth();

        return $this->makeRequestAndGetContent($request);
    }
}
