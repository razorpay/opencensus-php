<?php

namespace App\Admin;

use Requests_Session;
use Sunra\PhpSimple\HtmlDomParser;
use Trace;
use App\Trace\TraceCode;

/**
 * Fetches company data from MCA website
 * Dataset is generated using the script at https://gist.github.com/captn3m0/28290674a042384d9c53ffb4d7b419a1
 */
class Company
{
    const PORTAL_BASE_URL = 'http://www.mca.gov.in';
    const INDEX_URL       = '/mcafoportal/viewCompanyMasterData.do';
    const INFO_URL        = '/mcafoportal/companyLLPMasterData.do';


    const NOT_FOUND_ERROR = 'Entered CIN/LLPIN/FLLPIN/FCRN is not found';

    const HEADERS = [
        'Content-Type'  =>  'application/x-www-form-urlencoded',
        'Accept'        =>  'text/html,application/xhtml+xml'
    ];

    const OPTIONS = [
        'timeout'       =>  20
    ];

    public function __construct($cin)
    {
        $this->cin = $cin;

        $this->defaultersCINList = file(__DIR__.'/mca/cin.txt', FILE_IGNORE_NEW_LINES);
        $this->defaultersDINList = file(__DIR__.'/mca/din.txt', FILE_IGNORE_NEW_LINES);

        $this->session = new Requests_Session(self::PORTAL_BASE_URL);
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

        $data['defaulter'] = $this->isCINDefaulter($this->cin);

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

            $panOrDin = trim($rowdata[0]->plaintext);

            $data[] = [
                'PAN_DIN'      =>  $panOrDin,
                'Name'         =>  trim($rowdata[1]->plaintext),
                'StartDate'    =>  trim($rowdata[2]->plaintext),
                'EndDate'      =>  trim($rowdata[3]->plaintext),
                'Defaulter'    =>  $this->isDINDefaulter($panOrDin)
            ];
        }

        return $data;
    }

    protected function isCINDefaulter($cin)
    {
        return in_array($cin, $this->defaultersCINList);
    }

    protected function isDINDefaulter($din)
    {
        return in_array($din, $this->defaultersDINList);
    }

    public function retry()
    {
        return substr($this->cin, 0, 6) . $this->switchState($this->cin) . substr($this->cin, 8);
    }

    /**
     * Switch the state
     */
    protected function switchState($cin)
    {
        $state = substr($cin, 6, 2);

        if ($state === 'AP')
        {
            return 'TG';
        }

        return $state;
    }

    protected function fetchData()
    {
        $data = [
            'companyName'   =>  '',
            'companyID'     =>  $this->cin
        ];

        $response = null;

        try
        {
            $response = $this->session->post(
                self::INFO_URL,
                self::HEADERS,
                $data,
                self::OPTIONS
            );
        }
        catch (\Exception $exception)
        {
            $errorMessage = $exception->getMessage();

            /*
             * We search for 28 error code in the error message, which denotes for a timout exception.
             * In that case, we just trace the exception with info level else throw the same exception
             * Reference => https://curl.haxx.se/libcurl/c/libcurl-errors.html
             */
            if (strpos($errorMessage, "cURL error 28:") !== false)
            {
                Trace::info(TraceCode::MISC_TRACE_CODE, [
                    'message'   => $exception->getMessage(),
                    'code'      => $exception->getCode(),
                    'stack'     => $exception->getTraceAsString(),
                ]);
            }
            else
            {
                throw $exception;
            }
        }

        if (empty($response) === true)
        {
            return $response;
        }

        return $response->body;
    }

    public function fetch()
    {
        $newCin = $this->retry();

        $res = $this->fetchData();

        if (empty($res) === true)
        {
            return [
                'company'     => [],
                'signatories' => []
            ];
        }

        if (strpos($res, self::NOT_FOUND_ERROR) !== false)
        {
            $newCin = $this->retry();
            if ($this->cin !== $newCin)
            {
                $this->cin = $newCin;
                $res = $this->fetchData();
            }
        }

        $dom = HtmlDomParser::str_get_html($res);

        return [
            'company'           =>  $this->parseCompanyDetails($dom),
            'signatories'       =>  $this->parseSignatories($dom)
        ];
    }
}
