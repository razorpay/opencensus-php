import React from 'react';
import PaymentsList from 'merchant/views/Transactions/v2/UploadInvoices/components/PaymentsList';

import ErrorBoundary, { Teams, Ranks } from 'common/new-ui/ErrorBoundary';
import ContextWrapper from 'merchant/views/Transactions/v2/UploadInvoices/context/ContextWrapper';
import InvoiceStats from 'merchant/views/Transactions/v2/UploadInvoices/components/InvoiceStats';
import ModalWrapper from 'merchant/views/Transactions/v2/UploadInvoices/components/PaymentsContainer/ModalWrapper';

const PaymentsContainer = () => {
  return (
    <ErrorBoundary resetOnProps rank={Ranks.P1} team={Teams.CROSS_BORDER}>
      <ContextWrapper>
        <InvoiceStats />
        <PaymentsList />
        <ModalWrapper />
      </ContextWrapper>
    </ErrorBoundary>
  );
};

export default PaymentsContainer;
