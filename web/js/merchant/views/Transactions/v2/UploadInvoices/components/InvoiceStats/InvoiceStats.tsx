import React, { useContext } from 'react';
import { Box } from '@razorpay/blade/components';

import { OnboardingDetailsContext } from 'merchant/views/Transactions/v2/UploadInvoices/context/OnboardingDetailsContext';
import { InvoiceStatsContext } from 'merchant/views/Transactions/v2/UploadInvoices/context/InvoiceStatsContext';

import OnboardingCard from './OnboardingCard';
import StatsCard from './StatsCard';
import { INVOICE_STATUS, TABS } from './constant';
import { Tab } from './types';
import { isMerchantFullyOnboarded } from './utils';

const InvoiceStats = () => {
  const { invoiceStats, isInvoiceStatsLoading } = useContext(InvoiceStatsContext);
  const { onboardingData, isOnboardingDataLoading } = useContext(OnboardingDetailsContext);

  const isMerchantOnboarded = isMerchantFullyOnboarded(onboardingData);

  return (
    <Box
      display="flex"
      flexDirection="row"
      backgroundColor="surface.background.gray.intense"
      marginTop="spacing.4"
      marginBottom="spacing.4"
      overflow={{ base: 'scroll', m: 'hidden' }}
    >
      {TABS.map(({ type, name, tooltipText }: Tab): JSX.Element | null => {
        if (type === INVOICE_STATUS.INVOICE_AUTO_SYNCED && !isMerchantOnboarded)
          return (
            <OnboardingCard
              key={type}
              onboardingDetails={onboardingData}
              isLoading={isInvoiceStatsLoading || isOnboardingDataLoading}
            />
          );
        // eslint-disable-next-line consistent-return
        if (type === INVOICE_STATUS.INVOICE_AUTO_SYNCED && !isOnboardingDataLoading) return null;
        return (
          <StatsCard
            key={type}
            type={type}
            name={name}
            tooltipText={tooltipText}
            count={invoiceStats?.[type]}
            isLoading={isInvoiceStatsLoading}
          />
        );
      })}
    </Box>
  );
};

export default InvoiceStats;
