<?php

namespace Tests\Functional\Helpers\Payment;

use Config;
use Requests;
use Symfony\Component\DomCrawler\Crawler;
use Tests\Functional\TestCase;

trait PaymentAxisMigsTrait
{
    protected function runPaymentCallbackFlowAxisGenius($response, &$callback = null)
