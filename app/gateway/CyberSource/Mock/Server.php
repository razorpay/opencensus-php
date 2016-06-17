<?php
 
 namespace Gateway\Cybersource\Mock;
 
 
 use EE\Exception;
 use Gateway\Base;
 use Models\Card;
 use Models\Payment;
 use App;
 
 
class Server extends Base\Mock\Server
{
    protected $repo;
 
    public function getGatewayResponse($request)
    {
        if (isset($request->payerAuthEnrollService))
        {
            return $this->getEnrollResponse($request);
        }

        if (isset($request->payerAuthValidateService))
        {
            return $this->postAuthEnrolledRequest($request);
        }

        if (isset($request->ccAuthService))
        {
            return $this->postEnrollAuthorize($request);
        }

        if (isset($request->ccCaptureService))
        {
            return $this->getCaptureResponsse($request);
        }

        if (isset($request->ccCreditService))
        {
            return $this->getRefundResponse($request);
        }
    }

    public function postNotEnrolledAuthorize($input, $enrollResponse)
    {
        $response = new \stdClass();

    }

    public function getRefundResponse($request)
     {
        $response = new \stdClass();

        $response->decision = "ACCEPT";
        $response->reasonCode = 100;
        $response->requestID = "4661549029556297301014";
        $response->merchantReferenceCode = "razorpay";

        $ccCreditReply = new \stdClass();
        $ccCreditReply->reconciliationID = "razorpay";

        $response->ccCreditReply = $ccCreditReply;
        $response->ccCreditReply = $ccCreditReply;

        return $response;
     }

    public function getCaptureResponsse($request)
     {
        $response = new \stdClass();

        $response->decision = "ACCEPT";
        $response->reasonCode = 100;
        $response->requestID = "4661468455476856801016";

        $ccCaptureReply = new \stdClass();
        $ccCaptureReply->reconciliationID = "razorpay";

        $response->ccCaptureReply = $ccCaptureReply;

        return $response;
     }

     public function postAuthEnrolledRequest($request)
     {
        $response = new \stdClass();

        $response->decision = "ACCEPT";
        $response->reasonCode = 100;

        $payerAuthValidateReply = new \stdClass();
        $payerAuthValidateReply->eciRaw = "05";
        $payerAuthValidateReply->xid = "TktUb3hwZVp0eTMxcTh5UlZUODA=";
        $payerAuthValidateReply->paresStatus = "Y";
        $payerAuthValidateReply->commerceIndicator = "Internet";
        $payerAuthValidateReply->cavv = "1";

        $response->payerAuthValidateReply = $payerAuthValidateReply;

        return $response;
     }

    public function postEnrollAuthorize($request)
    {
        $response = new \stdClass();

        $response->decision = "ACCEPT";
        $response->reasonCode = 100;
        $response->requestID = "4661454138166750401020";

        $ccAuthReply = new \stdClass();
        $ccAuthReply->reconciliationID = "razorpay";

        $response->ccAuthReply = $ccAuthReply;

        return $response;
    }

 
    public function getEnrollResponse($request)
    {
        $response = new \stdClass();

        $payerAuthEnrollReply = new \stdClass();
        $response->payerAuthEnrollReply = $payerAuthEnrollReply;

        if($request->card->accountNumber === '4000000000000002')
        {
            $response->merchantReferenceCode = 'razorpay';
            $response->decision = 'REJECT';
            $response->reasonCode = '475';
            
            $params = array('gateway' => 'cybersource');
            $response->payerAuthEnrollReply->acsURL = 'http://localhost'.\URL::route('mockgateway_acs', $params, false);;
            $response->payerAuthEnrollReply->paReq = 'eNpVUttygjAQfc9XMP0AkiAw'.
                    '1VkzQ72iVijSVvvGQEaZkYsBWu3XN9xqm8nDnt3s5ZwN+CfB+XTHw0pwB'.
                    's+8KIIjV+Jo/JAfV9blZhNjc/gartOrOO7JAwPX8viFwScXRZyljKpE1Q'.
                    'D3EMkSIjwFackgCC9P9pbp5lCjJuAOIki4sKdsoFNd03SNtAdw60aQBgl'.
                    'nnvXheK51UPzZZLl1Ns7Cnu0U980H3MQRhFmVluLGBoas3QMElTizU1nm'.
                    'I4yLMhNcFcF3JvLgpoZZggHXcQT4PqVb1VYhyV/jiIWLaP2ynxs736L+/'.
                    'DxwXsuKL1fp+9QaA65fIIiCkjONUJOY1FSIOZLXkBo0fgRBUg/DvIISot'.
                    'bEOgeCvG5ltYi2pP+6JKdKCJ6GPakeIeDXPEu5fCPb/NqSxn34ybIWPCy'.
                    'lhLL045A0ije4SY+lOnJm2uTHjVS4zsHdPnG3emn9+xI/0sitvA==';
            $response->payerAuthEnrollReply->xid = 'cGdKQXF5STA1TFl3OUtueHJnWDA';
            $response->payerAuthEnrollReply->veresEnrolled = 'Y';

        }
        else if($request->card->accountNumber === '555555555555558')
        {
            $response->merchantReferenceCode = 'razorpay';
            $response->decision = 'ACCEPT';
            $response->reasonCode = '100';

            $response->payerAuthEnrollReply->veresEnrolled = "U";
            $response->payerAuthEnrollReply->commerceIndicator = "spa";
            $response->payerAuthEnrollReply->ucafCollectionIndicator = "1";
        }
        else
        {
            $response->merchantReferenceCode = 'razorpay';
            $response->decision = 'ACCEPT';
            $response->reasonCode = '100';
            $response->payerAuthEnrollReply->commerceIndicator = 'internet';
            $response->payerAuthEnrollReply->veresEnrolled = 'U';
        }
 
        return $response;
    }

    public function acs($input)
    {
        return array('PaRes' => "eNqdWNmS6jgSfSeCf6joeaS7vbB3UBUh7zbY4B385g0veMG78dePgKpb1bcrZu6MX5CS".
                            "1FFKmXky7Y0Wlr5Pqb7blP7bRvSryg78l8h7/Q3PmTVegSOeK9Z0Gh9KXEd/e9scgOJXD4XH".
                            "aNn5fKG5oU/6jdWt7MNkyJIkI2dC9Qq1W7+sojx7w/5E/8Q3yMcUblS6oZ3VbxvbLQheepst".
                            "1ji22CDv003qlzz15vlnu0nqDfKcbpDPdYfmPqqg0X3kvYnpmt1rwiAbwc3BBFscpELn+Juh".
                            "gdcNctfYeHbtv+EoukQX+PwFm/2FYn+hqw3ykG+udziQ5g3ExlD4bJCvog28n9LP3NvbdA6t".
                            "/DHb+P01z3yoAU/3Y7xBPq272tkb+vcHqt6lG+34tqmj9KtV67tV2HKDPOSbqrbrpno7bZD3".
                            "0ca12/YNAEAAk0lSGdyHsWKaFk0zATNM4GkfKhvfjd7QOTQK/j5WgSTIy6gO07upfxdskLsp".
                            "yMOhbxs1CjK4Wem/9GmSQTeGdX39C0G6rvuzm/6ZlwECDUYRdI1ABa+Kgn/99lzle3x2zv+n".
                            "ZaSd5Vnk2kk02DUMDdGvw9x7+WHbdzCackfCEIUm/4BQf7jYLPvjLkGn2BxiIt+DfjnZr+zy".
                            "s7FlZf9RhTZ23+AnoLeN4p/9e0T4L7rCv/72r1/JDSoK/Kr+f0z5MOMrwgeeYSeN/2aqQMq0".
                            "dpBRx7oNpIidtXbtH40ZQb9+rHtqbpAftr8f7OnFL7f1VNQCxFT8rcfsmh3b1e3FCLdGnzhD".
                            "f9xP59FkVeez1BF207PmBgd2fVJJ99j7xZHLkQi5ioa+avWqGcYje9egfkJZlytnZ2m4ZYub".
                            "Fhn71o6km6/2FTa9svHKoxkElYuyFKPjfkkX8bDkxe0h2FsqHfNC6u3Ho9Kp1VgshqOJG4o6".
                            "9FpOBqWBWOROJ/ZLsINpFHLVVpysgk4OAXEuttdrH/".
                            "rUSao1xlhdIl7agiVEml2wFavru4Ihhit6mFikFmtizunnrbqKUWUVNImcLQx5lp4TNaOShe".
                            "fDA+O+H5WlxJ/xtcwuWec6HsVHj78CGeUCkKJHsWV6AXiRnRQaY+/PtleyeyzVc4cIuCqaRr".
                            "4gmA0IXl+/RNW7Z7b+7emJ4xxdU3ZtP0dq48S+W0s2ZAhSeiXt0osyO3mh/DR/eTiw/".
                            "P1lr3/+AUgVCj7nZJ5CPnX93192ryLkqxzqq9rrPozy31/IV13dID/v89iY9Ms6OsO8goQp8".
                            "jxlDiQJKjUAHU+AgCeByneUfBK2ucWHrSsBmWYIGXRWTO9EcGEBptNEKJKGIfaUBnZEIBnjE".
                            "VwqEvQ6t81Vz8XAvUsJkIuMLoVuqlydlKlkfF07pnE74XoAxzeXolWRAA9EsusE8ZJcxiPLn".
                            "KO2aV1FRe7o4EQZskxRoNato4TynISdIoKSNeAzHdqLFH0TY7eXYjCXKMGGsttTJvfj0YdU5".
                            "FY9OQDhadNJA4mhiTLakd0Dnac75WCzSSvKq/vJ7zKO7izpbokz5SESG4PT+4k0+suJeFpqH".
                            "RNL3FRKREXv6Cfiju566nQULrY5Dx2SIGRM7DkNOPCeHiiixtBfUZTEMVeBjieZkxo36BWCj".
                            "4FEBJcivETsukOJuxcA2JNAXoH7/+MRGWzhjAYFPfWwSEiLMCE8nMcE4sDoC3fmnmKwaypTW".
                            "dO0pEz61FknwfF0qaWYYwM/".
                            "nxJ2X6sstGkxm3HYch3aQXtKxSj2Cd7tamShIj61a5S1uBapCTXtXNfd65BnPBBe+GtLywtT".
                            "u6Irea6jGZ8ty/EorPilXOwNj0e9owCGxJmFNX0D1Fa/".
                            "iQzvscltmXRMZSDxXkZlsSKIaKp2gk6pZhI3R3miCwfc3o1HeOBIR88hWuKC4VxQHCl+zp/8".
                            "/VUvWqxpzaNy9URhcukzEb0ZxYyvllOcACohrLKQOu1bJeOOQ8GNR6hPq2EXuqG4oE67SdEW".
                            "M5aMOKNbGRrZ7JpiV8mQlLf5frhUWL/nqlT2LnY4xxtE7HgKyIDIp1U3HvnaM4Y4RaRBDIBI".
                            "dixpkqwKWHZC8zAs5TMTYgeXNRodk0Se9pK7P09HJbFIgrKOwiOyx6MTzqCWBrwHmjyDRV/".
                            "WlYBV9Mgx5DbsSawEbSDXOyTALjbfcfe4UtCYIIKOyYF+5iMx8jB0PMJMdRVJhI/mYmhe0MV".
                            "2oL/LXio40UDShCONro7tqUS8+ZlHM2G/X68b4+DADG63eE0x0sX1lVI96TWNkenMn+3zocd".
                            "rc0ZV0rSYVyhi9nPzeojalnHOtKZ3WsqGQe5cmvIstJPlYjxCuOn0GmCGJXWDQ8/".
                            "TSsGu5NkiSVQRbHtyaQ5kY1mzaEGV62ItlrsDFjeXYDL0yMxD5VUZXHSLvJe4n/".
                            "nqOwIjUQoSWJx+IbBvr6DzqV8lMGq4J989UV0Rxl/iZErrxrQvEt2TrILOkHHmZrFJczJ7ys".
                            "GxGjo3tlSCGo+cqdCJCt/R4EkGFLjcVFO5o1yt9NIzGtDeaUCkGCVxp3Kgp+vWI/9ObOORqN".
                            "2pjR4kTfqkNk3+IRPZ038kNgo8iQ0iKbOOe9qzpQjjk5y+ENidzNwM9HQM5CdiJZKaIFgwnG".
                            "HmpjakPohE8I87AF23M1hjgFYPnskHMkp3gbX6+eaJZ/ChkLRYAeQsPPStw+YKJFXe3oMZ6x".
                            "KmzbHYoC8cOz/zyiHb1wybhNeiXXQ7m+3083kyv5yuS5FOb7wuNhwt2aBVLpGg1jdyDcv+Md".
                            "0vgqW3o1bbjMUW5Dor+3iVE7N0CmlJn6WT88ncs7BLSQ/5YgiJtiCq9jyspyEdbI/Sgva2Um".
                            "ME41FTDLW5ALt9p0sLhyi2sjq9BwYAbOwPxPw9fjy6k0kRgG4HTsKJt3hwcmZyQEsEsTI5fd".
                            "09ypimo12g4EYDYyP0WOMCbzhxH1GQxHdSEAn0gUYFskkQyirib8L0qBnW7sajzFINbow9Hm".
                            "laXooaOD/IQhVplgJmQCh0EB8QzHSC5GThLNtnpGkXWSR0MIZ+Kh36vXTAkhHIxKrZ6sh6uk".
                            "KM7ZxkdCGSEX1/tPGSu2E9mDoZhmF7cpEQi8qT66UUh7IbbrVop8lVMD8jhZpI2Hi0s05NOw".
                            "P8VttvLZotI6EA2kK4FJPtMawpdRbExZXJVvvYKgRlebZLWpl4znyOCGp7bbggdnrtvATjUd".
                            "pFMP8HShdmhtFEp+1KK5jkhhulwTGPhuqXSKCNIQlE/gcJCECg+sOETgwl3IoAZUm1YFXemV".
                            "KPkNSByAX/SB0Y3N8kz9eugAKfXcF/6nIgCfyXPuezy/nRNcAup392OYzQOpAU7ik1HnGhK0".
                            "H392JMT0Xt1EkauJl3WfyQoT9kMTET5aojn7aydCcY+kBLIqgeO8OOAXS0hodXZ6AP9zt5EG".
                            "HXsZp5T/D17VdJYjz6LzRhiIT4QRLiO0moDr5G4WoCEsH5wyPj0YdPYJACyAYSoEgikrdEIJ".
                            "Mzvzw7rSLT5LE8rRf1YSnvzYLUAB5nyDyerpEAKVBHjFyZVOBbBD45R8vLsL0RN7mwLtxwEu".
                            "arKQKO7VawO7RmNCSe0rEXXPzeuCYn1S/ow3yvU+IssNXzsuDEwDjANNnaeYa3Zcz1pk8aPR".
                            "PN+hmRe4mQnRMEqdl4ixYSNdTLCzKTfJUxb04Rye+dAgY6LX5Wdui7j9rO8wOyNtKZYLJeGF".
                            "ZWl6TFdRa5NP2P2v6d7nj0oU2Bh7ZGAL2D7iSQ4ftkD2QQMSuCTQimE31J8Ono1GW2DImp1r".
                            "kysmO9dhZzbLGqpfOVpelpOXAOMc/FlIYvfJXj1LxCNG14WKV14FsJlzQ7eUALZeLi+Tzp/P".
                            "Go75ZxcgZcUUAvqzG53XFNtUKkPFu6k2VUqEMtcyJh+M4SX3T2oWV550jvXMnM184hUApTx6".
                            "9WCDuG7c3i1uDbZEc+X5uQH69Sny9Zj69Jj89d9+8fXz+D/RtdcTck",
        'MD' => "5jC06vXpNmcicP",
        'TermUrl' => $input['TermUrl']);
    }
 
}