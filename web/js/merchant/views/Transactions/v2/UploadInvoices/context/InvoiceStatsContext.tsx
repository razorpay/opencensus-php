import React, { createContext, useMemo } from 'react';
import { useQuery } from '@tanstack/react-query';

import { fetchStats } from 'merchant/views/Transactions/v2/UploadInvoices/services';
import { InvoiceStatsContextType } from 'merchant/views/Transactions/v2/UploadInvoices/types';
import { REACT_QUERY_CONFIG } from 'merchant/views/Transactions/v2/UploadInvoices/constants';

export const InvoiceStatsContext = createContext<InvoiceStatsContextType>({
  invoiceStats: undefined,
  isInvoiceStatsLoading: true,
  refetchinvoiceStats: () => {},
});

const InvoiceStatsProvider = ({ children }) => {
  const {
    data: invoiceStats,
    isLoading: isInvoiceStatsLoading,
    refetch: refetchinvoiceStats,
  } = useQuery({
    queryKey: ['InvoiceStats'],
    queryFn: () => fetchStats(),
    ...REACT_QUERY_CONFIG,
  });

  const invoiceStatsValues = useMemo(
    () => ({
      invoiceStats,
      isInvoiceStatsLoading,
      refetchinvoiceStats,
    }),
    [invoiceStats, isInvoiceStatsLoading, refetchinvoiceStats],
  );

  return (
    <InvoiceStatsContext.Provider value={invoiceStatsValues}>
      {children}
    </InvoiceStatsContext.Provider>
  );
};

export default InvoiceStatsProvider;
