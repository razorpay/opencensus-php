import React, { createContext, useMemo } from 'react';
import { useQuery } from '@tanstack/react-query';

import { fetchOnboardingStatus } from 'merchant/views/Transactions/v2/UploadInvoices/services';
import { OnboardingDetailsContextType } from 'merchant/views/Transactions/v2/UploadInvoices/types';
import { REACT_QUERY_CONFIG } from 'merchant/views/Transactions/v2/UploadInvoices/constants';

export const OnboardingDetailsContext = createContext<OnboardingDetailsContextType>({
  onboardingData: undefined,
  isOnboardingDataLoading: true,
  refetchOnboardingData: () => {},
});

const OboardingDetailsProvider = ({ children }) => {
  const {
    data: onboardingData,
    isLoading: isOnboardingDataLoading,
    refetch: refetchOnboardingData,
  } = useQuery({
    queryKey: ['OnboardingStatus'],
    queryFn: () => fetchOnboardingStatus(),
    ...REACT_QUERY_CONFIG,
  });

  const onboardingDetailsValues = useMemo(
    () => ({
      onboardingData,
      isOnboardingDataLoading,
      refetchOnboardingData,
    }),
    [onboardingData, isOnboardingDataLoading, refetchOnboardingData],
  );

  return (
    <OnboardingDetailsContext.Provider value={onboardingDetailsValues}>
      {children}
    </OnboardingDetailsContext.Provider>
  );
};

export default OboardingDetailsProvider;
