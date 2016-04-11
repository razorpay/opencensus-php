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
    }

    public function parseCompanyDetails($dom)
    {
        $data  =[];
        $rows = $dom->find('div[id=companyMasterData] tr');
        foreach ($rows as $tr)
        {
            $key = trim(html_entity_decode($tr->first_child()->plaintext));
            $data[$key] = $tr->last_child()->plaintext;
        }

        return $data;
    }

    public function parseSignatories($dom)
    {
        $data = [];
        $rows = $dom->find('div[id=signatories] tr');
        foreach ($rows as $index => $tr)
        {
            // Skip the first row
            if ($index === 0)
            {
                continue;
            }

            $rowdata = $tr->find('td');

            $data[] = [
                'PAN'          =>  trim($rowdata[0]->plaintext),
                'Name'         =>  trim($rowdata[1]->plaintext),
                'StartDate'    =>  trim($rowdata[2]->plaintext),
                'EndDate'      =>  trim($rowdata[3]->plaintext),
            ];
        }
        return $data;
    }

    public function fetch()
    {
        $response = $this->session->post(self::INFO_URL, self::HEADERS, [
            'companyName'   =>  '',
            'companyID'     =>  $this->cin
        ]);

        $dom = HtmlDomParser::str_get_html($response->body);

        return [
            'company'           =>  $this->parseCompanyDetails($dom),
            'signatories'       =>  $this->parseSignatories($dom)
        ];
    }
}
