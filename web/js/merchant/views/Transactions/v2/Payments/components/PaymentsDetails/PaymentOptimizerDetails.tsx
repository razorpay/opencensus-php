import React from 'react';
import { Box, Text, Divider } from '@razorpay/blade/components';

import SettlementInfo from 'merchant/views/Settlements/components/SettlementInfo';
import PaymentOptimizerProvider from 'merchant/views/Transactions/v1/Payments/components/PaymentOptimizerProvider';

import { IPaymentDetails, IPaymentIdRefundDetail } from './types';

interface PaymentOptimizerDetailsProps {
  payment: IPaymentDetails | IPaymentIdRefundDetail;
  terminalProviders: { [key: string]: any }[];
  page: string;
}

const INTEGRATED_GATEWAYS = ['payu', 'paytm', 'billdesk_optimizer', 'cashfree'];

const PaymentOptimizerDetails = ({
  payment,
  terminalProviders,
  page,
}: PaymentOptimizerDetailsProps) => {
  return (
    <Box display="flex" flexDirection="column" gap="spacing.5" flexGrow="1">
      <Box display="flex" flexDirection="column" gap="spacing.2">
        <Text weight="semibold">Processed by</Text>
        <PaymentOptimizerProvider
          terminal_id={payment.optimizer_provider}
          settled_by={'settled_by' in payment ? payment.settled_by : undefined}
          terminalProviders={terminalProviders}
          isDetailView={true}
        />
      </Box>
      {('transaction' in payment ? payment.transaction : undefined) &&
      payment.optimizer_provider !== 'Razorpay' ? (
        <>
          <Divider />
          <Box display="flex" flexDirection="column" gap="spacing.2">
            <Text weight="semibold">Settlement details</Text>
            <SettlementInfo
              // eslint-disable-next-line @typescript-eslint/ban-ts-comment
              // @ts-ignore
              data={payment}
              integratedGateways={INTEGRATED_GATEWAYS}
              page={page}
              isTransactionsV2={true}
            />
          </Box>
        </>
      ) : null}
    </Box>
  );
};

export default PaymentOptimizerDetails;
