<?php

namespace RZP\Models\SimilarWeb;

use RZP\Models\Merchant\Entity as MerchantEntity;

class SimilarWebRequest
{
    public $domain;

    public $start_date;

    public $end_date;

    public $country;

    public $granularity;

    public $main_domain_only;

    public $format;

    public $show_verified;

    public $mtd;

    public function __construct(MerchantEntity $merchant)
    {
        $businessWebsite = $merchant->merchantDetail->getWebsite() ?? $merchant->getWebsite();
        $businessWebsite = trim($businessWebsite);
        $this->domain = parse_url(strtolower($businessWebsite), PHP_URL_HOST);

        $this->start_date = date('Y-m',strtotime('-2 month'));

        $this->end_date = $this->start_date;

        $this->country = 'in';

        $this->granularity = 'monthly';

        $this->main_domain_only = false;

        $this->format = 'json';

        $this->show_verified = false;

        $this->mtd = false;

    }

    public function getPath()
    {
        return $this->domain."/total-traffic-and-engagement/visits";
    }
}