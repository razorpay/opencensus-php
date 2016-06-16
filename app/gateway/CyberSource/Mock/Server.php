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
        if(isset($request->payerAuthEnrollService))
        {
            return $this->getEnrollResponse($request);
        }
     }
 
     protected function getEnrollResponse($request)
     {
        $response = new \stdClass();

        if($request->card->accountNumber === '4000000000000002')
        {
            $response->merchantReferenceCode = 'razorpay';
            $response->decision = 'REJECT';
            $response->reasonCode = '475';
            $payerAuthEnrollReply = new \stdClass();
            $response->payerAuthEnrollReply = $payerAuthEnrollReply;
            $response->payerAuthEnrollReply->acsURL = 'https://testcustomer34.'.
                    'cardinalcommerce.com/merchantacsfrontend/pareq.jsp?vaa=b&'.
                    'gold=AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'.
                    'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'.
                    'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'.
                    'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'.
                    'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'.
                    'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'.
                    'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'.
                    'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'.
                    'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'.
                    'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'.
                    'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'.
                    'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'.
                    'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'.
                    'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'.
                    'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'.
                    'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'.
                    'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'.
                    'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'.
                    'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'.
                    'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'.
                    'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'.
                    'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'.
                    'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'.
                    'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'.
                    'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'.
                    'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'.
                    'AAAAAAAAAAAAAAAAAAAAAAA';
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
 
 }