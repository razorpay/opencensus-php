<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use Lib\Formatters\Xml;
use RZP\Constants\HyperTrace;
use RZP\Trace\TraceCode;
use RZP\Base\JitValidator;
use RZP\Base\RuntimeManager;
use RZP\Models\Payment\Gateway;
use RZP\Models\VirtualAccount\Entity;
use RZP\Models\VirtualAccount\Provider;
use RZP\Models\VirtualAccount\Validator;
use RZP\Trace\Tracer;

class VirtualAccountController extends Controller
{
    use Traits\HasCrudMethods;

    public function createForOrder(string $id)
    {
        $input = Request::all();

        $response = $this->service()->createForOrder($id, $input);

        return ApiResponse::json($response);
    }

    public function closeVirtualAccount(string $id)
    {
        $response = $this->service()->closeVirtualAccount($id);

        return ApiResponse::json($response);
    }

    public function closeVirtualAccountsByCloseBy()
    {
        RuntimeManager::setTimeLimit(900);

        RuntimeManager::setMaxExecTime(900);

        RuntimeManager::setMemoryLimit('1024M');

        $response = $this->service()->closeVirtualAccountsByCloseBy();

        return ApiResponse::json($response);
    }

    public function getPayments(string $id)
    {
        $input = Request::all();

        $response = Tracer::inSpan(['name' =>HyperTrace::VIRTUAL_ACCOUNTS_FETCH_PAYMENTS], function() use($id, $input)
        {
            return $this->service()->fetchPayments($id, $input);
        });

        return ApiResponse::json($response);
    }

    public function editVirtualAccountBulk()
    {
        $input = Request::all();

        $response = $this->service()->editVirtualAccountBulk($input);

        return ApiResponse::json($response);
    }

    public function addReceivers(string $id)
    {
        $input = Request::all();

        $data = Tracer::inSpan(['name' => HyperTrace::VIRTUAL_ACCOUNTS_ADD_RECEIVER], function() use ($id, $input) {
            return $this->service()->addReceivers($id, $input);
        });

        return ApiResponse::json($data);
    }

    /**
     * @param string $id
     * @return mixed
     * This function is used to update the expiry of the VA
     */
    public function merchantVAupdate(string $id)
    {
        $input = Request::all();
        $response = $this->service()->editVirtualAccount($id, $input);

        return ApiResponse::json($response);
    }

    /**
     * This function is used for the offline payments
     * We create an order first and then create a VA using that
     */
    public function createOfflineQr()
    {
        $input = Request::all();

        $va = $this->service()->createOfflineQr($input);

        return ApiResponse::json($va);
    }

    public function getReceiverConfigs()
    {
        $data = $this->service()->getConfigsForVirtualAccount();

        return ApiResponse::json($data);
    }

    public function bulkMigrateYesbank()
    {
        $input = Request::all();

        $data = $this->service()->bulkMigrateYesbank($input);

        return ApiResponse::json($data);
    }

    public function createForBanking()
    {
        $input = Request::all();

        $data = $this->service()->createForBanking($input);

        return ApiResponse::json($data);
    }

    public function bulkCreateForBanking()
    {
        $input = Request::all();

        $data = $this->service()->bulkCreateForBanking($input);

        return ApiResponse::json($data);
    }

    public function bulkCloseForBanking()
    {
        $input = Request::all();

        $data = $this->service()->bulkCloseForBanking($input);

        return ApiResponse::json($data);
    }

    public function validateVpa(string $gateway, string $vpaRoot)
    {
        $requestContent = Request::getContent();

        $this->trace->info(
            TraceCode::VIRTUAL_ACCOUNT_ECOLLECT_VALIDATE_VPA,
            [
                'request' => $requestContent,
            ]
        );

        $response = null;
        $vpa      = '';
        $input    = [];

        try
        {
            switch ($gateway)
            {
                case Gateway::UPI_ICICI:
                {
                    $input = (array) simplexml_load_string($requestContent);

                    (new JitValidator)->setStrictFalse()->rules(Validator::$validateVpaIciciRules)->caller($this)->validate($input);

                    $vpa = $vpaRoot . '.' . $input['SubscriberId'] . '@' . Provider::VPA_HANDLE[$gateway];

                    break;
                }
            }

            $data = $this->service()->ecollectValidateVpa($vpa, $input);
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::VIRTUAL_ACCOUNT_ECOLLECT_VALIDATE_VPA_FAILED,
                [
                    'request' => $requestContent,
                ]);

            $data['valid'] = false;
        }

        switch ($gateway)
        {
            case Gateway::UPI_ICICI:
            {
                $responseArray = [
                    'ActCode' => 1,
                    'Message' => 'INVALID',
                ];

                $valid = $data['valid'];

                if ($valid === true)
                {
                    $responseArray['CustName'] = $data['merchantName'];
                    $responseArray['ActCode']  = 0;
                    $responseArray['Message']  = 'VALID';
                    $responseArray['TxnId']    = $input['TxnId'];
                }

                $xml = Xml::create('XML', $responseArray);

                $response = \Response::make($xml);

                $response->headers->set('Content-Type', 'application/xml; charset=UTF-8');
                $response->headers->set('Cache-Control', 'no-cache');

                break;
            }
            default:
            {
                $response = ApiResponse::json($data);
            }
        }

        $this->trace->info(
            TraceCode::VIRTUAL_ACCOUNT_ECOLLECT_VALIDATE_VPA_RESPONSE,
            [
                'vpa'      => $vpa,
                'data'     => $data,
                'response' => $response,
            ]
        );

        return $response;
    }

    public function bulkCloseVirtualAccount()
    {
        $input = Request::all();

        $data = $this->service()->bulkCloseVirtualAccount($input);

        return ApiResponse::json($data);
    }

    public function debugVA()
    {
        $input = Request::all();

        return ApiResponse::json(
            ['msg' => 'Virtual Account debug route. Use this route for debugging/data corrections via dark',
                'input' => $input]
        );
    }

    public function createForInternal()
    {
        $input = Request::all();

        $entity = $this->service()->createForInternal($input);

        return ApiResponse::json($entity);
    }

    public function addDefaultVirtualAccountExpiryForMerchant()
    {
        $input = Request::all();

        $response = $this->service()->addDefaultVirtualAccountExpiry($input);

        return ApiResponse::json($response);
    }

    public function getMerchantDefaultVirtualAccountExpiry()
    {
        $response = $this->service()->getMerchantDefaultVirtualAccountExpiry();

        return ApiResponse::json($response);
    }

    public function addAllowedPayer(string $id)
    {
        $input = Request::all();

        $data = Tracer::inSpan(['name' => HyperTrace::VIRTUAL_ACCOUNTS_ADD_ALLOWED_PAYER], function() use ($id, $input) {
            return $this->service()->addAllowedPayer($id, $input);
        });

        return ApiResponse::json($data);
    }

    public function deleteAllowedPayer(string $virtualAccountId, string $tpvId)
    {
        Tracer::inSpan(['name' => HyperTrace::VIRTUAL_ACCOUNTS_DELETE_ALLOWED_PAYER], function() use ($virtualAccountId, $tpvId) {
            $this->service()->deleteAllowedPayer($virtualAccountId, $tpvId);
        });
        return ApiResponse::json([], 204);
    }
}
