<?php

namespace Tests\Functional\Helpers\Payment;

use Config;
use Requests;
use Symfony\Component\DomCrawler\Crawler;
use Tests\Functional\TestCase;

class PaymentStateRecorder
{
    public $callbackUrl;
}