<?php

namespace RZP\Services;

use Requests;
use RZP\Exception;
use RZP\Trace\TraceCode;

class SmartRouting
{
    const X_RAZORPAY_TASKID  = 'X-Razorpay-TaskId';

    const REQUEST_TIMEOUT    = 20;

    const MAX_RETRY_COUNT    = 1;

    const SUCCESS            = 'success';

    const ERROR              = 'error';

    protected $config;

    protected $baseUrl;

    protected $trace;

    protected $request;

    protected $app;

    const CREATE_GATEWAY_RULE  = [
        'url'       =>  "/rule",
        'method'    =>  "POST",
    ];

    const UPDATE_GATEWAY_RULE  = [
        'url'       =>  "/rule",
        'method'    =>  "PUT",
    ];

    const DELETE_GATEWAY_RULE  = [
        'url'       =>  "/rule/:id",
        'method'    =>  "DELETE",
    ];

    const SEND_PAYMENT_DATA  = [
        'url'       =>  "/route",
        'method'    =>  "POST",
    ];

    public function __construct($app)
    {
        $this->app = $app;

        $this->trace = $app['trace'];

        $this->config = $app['config']->get('applications.smart_routing');

        $this->baseUrl = $this->config['url'];

        $this->request = $app['request'];
    }

    public function sendPaymentData($data)
    {

        //return $this->sendRequest(self::SEND_PAYMENT_DATA, $data);
        $response = '[
    {
        "id": "8vJjChzYB2yExC",
        "merchant_id": "100000Razorpay",
        "gateway": "hdfc",
        "card": true,
        "category": "1234",
        "currency": "INR",
        "network_category": "ecommerce",
        "netbanking": false,
        "upi": false,
        "bank_transfer": false,
        "aeps": false,
        "emandate": false,
        "emi": false,
        "capability": 0,
        "emi_duration": 0,
        "emi_subvention": "",
        "shared": true,
        "international": true,
        "tpv": 0,
        "gateway_merchant_id": "123456",
        "gateway_merchant_id_2": "",
        "gateway_terminal_id": "12345678",
        "gateway_acquirer": "hdfc",
        "mc_mpan": "",
        "visa_mpan": "",
        "rupay_mpan": "",
        "network_mpan": "",
        "vpa": "",
        "type": [
            "non_recurring"
        ],
        "enabled_types": null,
        "mode": 3,
        "corporate": 0,
        "expected": false,
        "enabled_banks": null,
        "card_less_emi": false,
        "direct": false,
        "applicable_token": false,
        "bank": {
            "name": "",
            "emandate": false,
            "npci": false,
            "retail": false,
            "corp": false,
            "tpv": false,
            "emi": false
        },
        "network": {
            "name": "VISA",
            "is_headless": true,
            "baharat_qr": false,
            "recurring": false
        },
        "issuer": {
            "name": "",
            "acquirer": ""
        },
        "gateway_info": {
            "name": "hdfc",
            "auth_type": "",
            "is_tpv": false,
            "supported_networks": null,
            "supported_net_banking": null,
            "issuers": null,
            "recurring": true,
            "upi": false,
            "mcc": true,
            "subscription": false,
            "card": false,
            "netbanking": false,
            "direct_netbanking": false,
            "only_auth_gateway": false
        }
    },
    {
        "id": "7mDR87v5nncYMJ",
        "merchant_id": "7MQGJTOQfD1mL1",
        "gateway": "aeps_icici",
        "card": true,
        "category": "",
        "currency": "INR",
        "network_category": "",
        "netbanking": false,
        "upi": false,
        "bank_transfer": false,
        "aeps": false,
        "emandate": false,
        "emi": false,
        "capability": 0,
        "emi_duration": 0,
        "emi_subvention": "",
        "shared": false,
        "international": false,
        "tpv": 0,
        "gateway_merchant_id": "12345",
        "gateway_merchant_id_2": "",
        "gateway_terminal_id": "",
        "gateway_acquirer": "",
        "mc_mpan": "",
        "visa_mpan": "",
        "rupay_mpan": "",
        "network_mpan": "",
        "vpa": "",
        "type": [
            "non_recurring"
        ],
        "enabled_types": null,
        "mode": 3,
        "corporate": 0,
        "expected": false,
        "enabled_banks": null,
        "card_less_emi": false,
        "direct": true,
        "applicable_token": false,
        "bank": {
            "name": "",
            "emandate": false,
            "npci": false,
            "retail": false,
            "corp": false,
            "tpv": false,
            "emi": false
        },
        "network": {
            "name": "",
            "is_headless": false,
            "baharat_qr": false,
            "recurring": false
        },
        "issuer": {
            "name": "",
            "acquirer": ""
        },
        "gateway_info": {
            "name": "",
            "auth_type": "",
            "is_tpv": false,
            "supported_networks": null,
            "supported_net_banking": null,
            "issuers": null,
            "recurring": false,
            "upi": false,
            "mcc": false,
            "subscription": false,
            "card": false,
            "netbanking": false,
            "direct_netbanking": false,
            "only_auth_gateway": false
        }
    },
    {
        "id": "CtqNMkffHVa74V",
        "merchant_id": "7MQGJTOQfD1mL1",
        "gateway": "billdesk",
        "card": false,
        "category": "",
        "currency": "INR",
        "network_category": "pvt_education",
        "netbanking": true,
        "upi": false,
        "bank_transfer": false,
        "aeps": false,
        "emandate": false,
        "emi": false,
        "capability": 0,
        "emi_duration": 0,
        "emi_subvention": "",
        "shared": false,
        "international": false,
        "tpv": 0,
        "gateway_merchant_id": "7MQGJTOQfD1mL1",
        "gateway_merchant_id_2": "",
        "gateway_terminal_id": "",
        "gateway_acquirer": "hdfc",
        "mc_mpan": "",
        "visa_mpan": "",
        "rupay_mpan": "",
        "network_mpan": "",
        "vpa": "",
        "type": [
            "non_recurring"
        ],
        "enabled_types": null,
        "mode": 3,
        "corporate": 0,
        "expected": false,
        "enabled_banks": [
            "ABPB",
            "ANDB",
            "AUBL",
            "BACB",
            "BBKM",
            "BDBL",
            "BKDN",
            "BKID",
            "CBIN",
            "CIUB",
            "CNRB",
            "COSB",
            "DBSS",
            "DCBL",
            "DCBL",
            "DEUT",
            "DLXB",
            "ESAF",
            "IBKL",
            "IDIB",
            "IOBA",
            "JAKA",
            "JSBP",
            "KARB",
            "KCCB",
            "KJSB",
            "KVBL",
            "MAHB",
            "MSNU",
            "NESF",
            "NKGS",
            "PMCB",
            "PSIB",
            "SBBJ",
            "SBHY",
            "SBIN",
            "SBMY",
            "SBTR",
            "SCBL",
            "SIBL",
            "SRCB",
            "STBP",
            "SURY",
            "SVCB",
            "SYNB",
            "TBSB",
            "TJSB",
            "TMBL",
            "TNSC",
            "UBIN",
            "UCBA",
            "UTBI",
            "VARA",
            "VIJB",
            "YESB",
            "ZCBL",
            "ANDB_C",
            "BARB_C",
            "DLXB_C",
            "IBKL_C",
            "LAVB_C",
            "LAVB_R",
            "PUNB_C",
            "PUNB_R",
            "RATN_C",
            "SVCB_C",
            "YESB_C"
        ],
        "card_less_emi": false,
        "direct": true,
        "applicable_token": false,
        "bank": {
            "name": "",
            "emandate": false,
            "npci": false,
            "retail": false,
            "corp": false,
            "tpv": false,
            "emi": false
        },
        "network": {
            "name": "",
            "is_headless": false,
            "baharat_qr": false,
            "recurring": false
        },
        "issuer": {
            "name": "",
            "acquirer": ""
        },
        "gateway_info": {
            "name": "billdesk",
            "auth_type": "",
            "is_tpv": false,
            "supported_networks": null,
            "supported_net_banking": null,
            "issuers": null,
            "recurring": false,
            "upi": false,
            "mcc": false,
            "subscription": false,
            "card": false,
            "netbanking": true,
            "direct_netbanking": false,
            "only_auth_gateway": false
        }
    },
    {
        "id": "CtsHsPvbnOm6Ak",
        "merchant_id": "7MQGJTOQfD1mL1",
        "gateway": "wallet_olamoney",
        "card": false,
        "category": "",
        "currency": "INR",
        "network_category": "",
        "netbanking": false,
        "upi": false,
        "bank_transfer": false,
        "aeps": false,
        "emandate": false,
        "emi": false,
        "capability": 0,
        "emi_duration": 0,
        "emi_subvention": "",
        "shared": false,
        "international": false,
        "tpv": 0,
        "gateway_merchant_id": "7MQGJTOQfD1mL1",
        "gateway_merchant_id_2": "",
        "gateway_terminal_id": "",
        "gateway_acquirer": "zestmoney",
        "mc_mpan": "",
        "visa_mpan": "",
        "rupay_mpan": "",
        "network_mpan": "",
        "vpa": "",
        "type": [
            "non_recurring"
        ],
        "enabled_types": null,
        "mode": 3,
        "corporate": 0,
        "expected": false,
        "enabled_banks": null,
        "card_less_emi": false,
        "direct": true,
        "applicable_token": false,
        "bank": {
            "name": "",
            "emandate": false,
            "npci": false,
            "retail": false,
            "corp": false,
            "tpv": false,
            "emi": false
        },
        "network": {
            "name": "",
            "is_headless": false,
            "baharat_qr": false,
            "recurring": false
        },
        "issuer": {
            "name": "",
            "acquirer": ""
        },
        "gateway_info": {
            "name": "",
            "auth_type": "",
            "is_tpv": false,
            "supported_networks": null,
            "supported_net_banking": null,
            "issuers": null,
            "recurring": false,
            "upi": false,
            "mcc": false,
            "subscription": false,
            "card": false,
            "netbanking": false,
            "direct_netbanking": false,
            "only_auth_gateway": false
        }
    },
    {
        "id": "CtsRyqFhqlG80d",
        "merchant_id": "7MQGJTOQfD1mL1",
        "gateway": "hdfc",
        "card": true,
        "category": "",
        "currency": "INR",
        "network_category": "govt_education",
        "netbanking": true,
        "upi": false,
        "bank_transfer": false,
        "aeps": false,
        "emandate": false,
        "emi": false,
        "capability": 0,
        "emi_duration": 0,
        "emi_subvention": "",
        "shared": false,
        "international": true,
        "tpv": 0,
        "gateway_merchant_id": "122212",
        "gateway_merchant_id_2": "",
        "gateway_terminal_id": "23423343",
        "gateway_acquirer": "hdfc",
        "mc_mpan": "",
        "visa_mpan": "",
        "rupay_mpan": "",
        "network_mpan": "",
        "vpa": "",
        "type": [
            "non_recurring"
        ],
        "enabled_types": null,
        "mode": 3,
        "corporate": 0,
        "expected": false,
        "enabled_banks": null,
        "card_less_emi": false,
        "direct": true,
        "applicable_token": false,
        "bank": {
            "name": "",
            "emandate": false,
            "npci": false,
            "retail": false,
            "corp": false,
            "tpv": false,
            "emi": false
        },
        "network": {
            "name": "VISA",
            "is_headless": true,
            "baharat_qr": false,
            "recurring": false
        },
        "issuer": {
            "name": "",
            "acquirer": ""
        },
        "gateway_info": {
            "name": "hdfc",
            "auth_type": "",
            "is_tpv": false,
            "supported_networks": null,
            "supported_net_banking": null,
            "issuers": null,
            "recurring": true,
            "upi": false,
            "mcc": true,
            "subscription": false,
            "card": false,
            "netbanking": false,
            "direct_netbanking": false,
            "only_auth_gateway": false
        }
    },
    {
        "id": "CtuRl6rlPkXu4o",
        "merchant_id": "7MQGJTOQfD1mL1",
        "gateway": "wallet_payzapp",
        "card": false,
        "category": "",
        "currency": "INR",
        "network_category": "",
        "netbanking": false,
        "upi": false,
        "bank_transfer": false,
        "aeps": false,
        "emandate": false,
        "emi": false,
        "capability": 0,
        "emi_duration": 0,
        "emi_subvention": "",
        "shared": false,
        "international": false,
        "tpv": 0,
        "gateway_merchant_id": "etryuhnjiuhjnhgfdserf",
        "gateway_merchant_id_2": "",
        "gateway_terminal_id": "23444345",
        "gateway_acquirer": "zestmoney",
        "mc_mpan": "",
        "visa_mpan": "",
        "rupay_mpan": "",
        "network_mpan": "",
        "vpa": "",
        "type": [
            "non_recurring"
        ],
        "enabled_types": null,
        "mode": 3,
        "corporate": 0,
        "expected": false,
        "enabled_banks": null,
        "card_less_emi": false,
        "direct": true,
        "applicable_token": false,
        "bank": {
            "name": "",
            "emandate": false,
            "npci": false,
            "retail": false,
            "corp": false,
            "tpv": false,
            "emi": false
        },
        "network": {
            "name": "",
            "is_headless": false,
            "baharat_qr": false,
            "recurring": false
        },
        "issuer": {
            "name": "",
            "acquirer": ""
        },
        "gateway_info": {
            "name": "",
            "auth_type": "",
            "is_tpv": false,
            "supported_networks": null,
            "supported_net_banking": null,
            "issuers": null,
            "recurring": false,
            "upi": false,
            "mcc": false,
            "subscription": false,
            "card": false,
            "netbanking": false,
            "direct_netbanking": false,
            "only_auth_gateway": false
        }
    }
]';
        return json_decode($response, true);
    }

    public function createGateway($data)
    {
        return $this->sendRequest(self::CREATE_GATEWAY_RULE, $data);
    }

    public function updateGateway($data)
    {
        return $this->sendRequest(self::UPDATE_GATEWAY_RULE, $data);
    }

    public function deleteGateway($id, $group)
    {
        $params = null;

        if (empty($group) === false)
        {
            $params = ['group' => $group];
        }

        return $this->sendRequest(self::DELETE_GATEWAY_RULE, null, $id, $params);
    }

    protected function sendNonBlockingRequest($action, $data = null, $id = null)
    {
        $url = $this->getUrl($action, $id);

        if ($data === null)
        {
            $data = '';
        }

        $headers['Content-Type'] = 'application/json';

        $headers['Accept'] = 'application/json';

        $headers[self::X_RAZORPAY_TASKID] = $this->request->getTaskId();

        $username = $this->app['config']->get('applications.smart_routing.username');

        $password = $this->app['config']->get('applications.smart_routing.password');

        $this->app->nonBlockingHttp->postRequest($url, $data, $headers, $username, $password);
    }


    protected function sendRequest($action, $data = null, $id = null, $params = null)
    {
        try
        {
            $url = $this->getUrl($action, $id, $params);

            if ($data === null)
            {
                $data = '';
            }

            $headers['Content-Type'] = 'application/json';

            $headers['Accept'] = 'application/json';

            $headers[self::X_RAZORPAY_TASKID] = $this->request->getTaskId();

            $authentication = [
                $this->app['config']->get('applications.smart_routing.username'),
                $this->app['config']->get('applications.smart_routing.password')
            ];

            $options = [
                'timeout' => self::REQUEST_TIMEOUT,
                'auth'    => $authentication

            ];

            $request = [
                'url'     => $url,
                'method'  => $action['method'],
                'headers' => $headers,
                'options' => $options,
                'content' => $data
            ];

            $response = $this->sendSmartRoutingRequest($request);

            $this->checkErrors($response);

            return json_decode($response->body, true);
        }
        catch (\Throwable $e)
        {
            $this->trace->error(
                TraceCode::SMART_ROUTING_SERVICE_ERROR,
                [
                    'response' => $e->getMessage(),
                    'action'   => $action,
                    'data'     => $data,
                ]);
            return null;
        }
    }

    protected function sendSmartRoutingRequest($request)
    {
        $method = $request['method'];

        $retryCount = 0;

        while (true)
        {
            try
            {
                if ($method === 'POST' or $method === 'PUT')
                {
                    $response = Requests::$method(
                        $request['url'],
                        $request['headers'],
                        json_encode($request['content']),
                        $request['options']);
                }
                else
                {
                    $response = Requests::$method(
                        $request['url'],
                        $request['headers'],
                        $request['options']);
                }

                break;
            }
            catch(\Requests_Exception $e)
            {
                // check curl error, increase retry count if timeout
                // throw the error if retry count reaches max allowed value
                if (($retryCount < self::MAX_RETRY_COUNT) and
                    (curl_errno($e->getData()) === CURLE_OPERATION_TIMEDOUT))
                {
                    $this->trace->info(
                        TraceCode::SMART_ROUTING_RETRY,
                        [
                            'message' => $e->getMessage(),
                            'type'    => $e->getType(),
                            'data'    => $e->getData()
                        ]);

                    $retryCount++;
                }
                else
                {
                    throw $e;
                }
            }
        }

        return $response;
    }

    protected function checkErrors($response)
    {
        $responseBody = json_decode($response->body, true);

        $this->trace->info(
            TraceCode::SMART_ROUTING_RESPONSE,
            [
                'response' => $responseBody
            ]);

        if ($response->status_code >= 400)
        {
            throw new Exception\RuntimeException('Smart routing request failed', $responseBody);
        }
    }

    private function getUrl($action, $id, $params = null) : string
    {
        $url = $this->baseUrl . str_replace_first(':id', $id, $action['url']);

        if (empty($params) == false)
        {
            $url = $url . '?';

            foreach ($params as $key => $value) {

                $url .= $key . '=' . $value . '&';
            }

            $url = rtrim($url, '&');
        }

        return $url;
    }
}
