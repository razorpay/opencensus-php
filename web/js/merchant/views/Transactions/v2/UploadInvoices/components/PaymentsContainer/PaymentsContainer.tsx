import React from 'react';
import PaymentsList from 'merchant/views/Transactions/v2/UploadInvoices/components/PaymentsList';

import ErrorBoundary, { Teams, Ranks } from 'common/new-ui/ErrorBoundary';

const PaymentsContainer = () => {
  return (
    <ErrorBoundary resetOnProps rank={Ranks.P1} team={Teams.CROSS_BORDER}>
      <PaymentsList />
    </ErrorBoundary>
  );
};

export default PaymentsContainer;
