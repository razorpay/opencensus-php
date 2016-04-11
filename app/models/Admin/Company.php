<?php

namespace Models\Admin;

use Requests_Session;
use Sunra\PhpSimple\HtmlDomParser;

/** Fetches company data from MCA website */
class Company
{
    const PORTAL_BASE_URL = 'http://www.mca.gov.in';
    const INDEX_URL       = '/mcafoportal/viewCompanyMasterData.do';
    const INFO_URL        = '/mcafoportal/companyLLPMasterData.do';

    const HEADERS = [
        'Content-Type'  =>  'application/x-www-form-urlencoded',
        'Accept'        =>  'text/html,application/xhtml+xml'
    ];

    public function __construct($cin)
    {
        $this->cin = $cin;
        $this->session = new Requests_Session(self::PORTAL_BASE_URL);
        $res = $this->session->get(self::INDEX_URL);

        // sd($res->body);
    }

    public function fetch()
    {
        $response = $this->session->post(self::INFO_URL, self::HEADERS, [
            'companyName'   =>  '',
            'companyID'     =>  $this->cin
        ]);

        $dom = HtmlDomParser::str_get_html($response->body);
        return $dom->find('form[id=exportCompanyMasterData]', 0)->innertext;
    }
}
