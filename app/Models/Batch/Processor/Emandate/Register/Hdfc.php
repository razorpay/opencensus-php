<?php

namespace RZP\Models\Batch\Processor\Emandate\Register;

 use RZP\Exception;
 use RZP\Gateway\Base\Action;
 use RZP\Models\Payment;
 use RZP\Models\Customer\Token;
 use RZP\Models\Payment\Gateway;
 use RZP\Gateway\Base\Entity as GatewayEntity;
 use RZP\Gateway\Netbanking\Base\Entity as NetbankingEntity;
 use RZP\Gateway\Netbanking\Hdfc\EMandateRegisterFileHeadings as Headings;

 class Hdfc extends Base
 {
     const GATEWAY   = Gateway::NETBANKING_HDFC;

     const SUCCESS   = 'success';
     const REJECT    = 'reject';

     protected  $gatewayPaymentMapping = [
         Base::TOKEN_ID       => NetbankingEntity::SI_TOKEN,
         Base::STATUS         => NetbankingEntity::SI_STATUS,
         Base::REMARK         => NetbankingEntity::SI_MSG,
         Base::ACCOUNT_NUMBER => NetbankingEntity::ACCOUNT_NUMBER,
     ];

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
            Base::TOKEN_ID         => $tokenId,
            Base::GATEWAY_TOKEN_ID => $tokenId,
            Base::STATUS           => $status,
            Base::REMARK           => $remark,
            Base::ACCOUNT_NUMBER   => $accountNumber,
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

     protected function getGatewayPayment(Payment\Entity $payment)
     {
         return $this->repo->netbanking->findByPaymentIdAndAction($payment['id'], Action::AUTHORIZE);
     }
 }
