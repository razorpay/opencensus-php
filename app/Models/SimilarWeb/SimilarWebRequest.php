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

    public function __construct(string $website)
    {
        $this->domain = $this->extractDomain($website);

        $this->start_date = date('Y-m',strtotime('-2 month'));

        $this->end_date = $this->start_date;

        $this->country = 'in';

        $this->granularity = 'monthly';

        $this->main_domain_only = false;

        $this->format = 'json';

        $this->show_verified = false;

        $this->mtd = false;

    }

    protected function extractDomain($url) {
        // Remove "http://" or "https://" from the beginning of the URL
        $url = preg_replace('#^https?://#', '', $url);

        // Remove "www." from the beginning of the URL
        $url = preg_replace('#^www\.#', '', $url);

        // Remove any path or query string after the domain
        $url = strtok($url, '/?');

        return $url;
    }

    public function getPath()
    {
        return $this->domain."/total-traffic-and-engagement/visits";
    }
}