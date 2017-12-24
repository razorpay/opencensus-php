<?php

namespace RZP\Models\Batch\Processor\Emandate\Register;

 use RZP\Exception;
 use RZP\Models\Customer\Token;
 use RZP\Models\Payment\Gateway;
 use RZP\Gateway\Netbanking\Hdfc\EMandateRegisterFileHeadings as Headings;

 class Hdfc extends Base
 {
     const GATEWAY   = Gateway::NETBANKING_HDFC;

     const SUCCESS   = 'success';
     const REJECT    = 'reject';

     protected static $statusMap = [
        self::SUCCESS => Token\RecurringStatus::CONFIRMED,
        self::REJECT  => Token\RecurringStatus::REJECTED,
     ];

     protected function getDataFromRow(array & $entry): array
     {
        $tokenId = $entry[Headings::MANDATE_ID];

        $status = $entry[Headings::STATUS];
        $status = $this->getTokenStatus($status);

        $remark = $entry[Headings::REMARK];

        $accountNumber = $entry[Headings::CUSTOMER_ACCOUNT_NUMBER];

        return [
            'token_id'       => $tokenId,
            'status'         => $status,
            'remark'         => $remark,
            'account_number' => $accountNumber,
        ];
     }

     protected function getTokenStatus(string $gatewayTokenStatus): string
     {
         $gatewayTokenStatus = strtolower($gatewayTokenStatus);

         if (isset(self::$statusMap[$gatewayTokenStatus]) === false)
         {
             throw new Exception\LogicException('Unrecognized gateway status: ', $gatewayTokenStatus);
         }

         return self::$statusMap[$gatewayTokenStatus];
     }
 }