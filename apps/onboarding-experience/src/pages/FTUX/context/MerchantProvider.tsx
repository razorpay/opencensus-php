import React, { ReactNode, useReducer, useEffect } from 'react';
import useMerchant from 'apps/onboarding-experience/src/common/hooks/useMerchant';
import useMerchantOnboardingData from 'apps/onboarding-experience/src/common/hooks/useMerchantOnboardingData';
import { WORKFLOW_TYPES } from 'apps/onboarding-experience/src/common/types/merchant';
import { FTUX_FEATURE_FLAGS, FTUX_REQUIRED_DATA_REQUEST } from '@FTUX/constants/homepage';
import {
  MerchantContext,
  merchantReducer,
  initialState,
  MerchantContextType,
} from './MerchantContext';

interface MerchantProviderProps {
  children: ReactNode;
}

/**
 * Provider that manages merchant and onboarding data fetching and state
 * Exposes data, loading states, and refetch methods through context
 */
const MerchantProvider: React.FC<MerchantProviderProps> = ({ children }) => {
  const [state, dispatch] = useReducer(merchantReducer, initialState);

  // Fetch merchant data
  const {
    data: merchantData,
    isLoading: isLoadingMerchant,
    refetch: refetchMerchantData,
  } = useMerchant();

  // Fetch merchant onboarding data
  const {
    data: onboardingData,
    isLoading: isLoadingOnboardingData,
    refetch: refetchOnboardingData,
  } = useMerchantOnboardingData({
    defaultWorkflow: WORKFLOW_TYPES.BUSINESS_WEBSITE,
    defaultFeatureFlags: FTUX_FEATURE_FLAGS,
    requestedData: FTUX_REQUIRED_DATA_REQUEST,
  });

  // Update state when data changes
  useEffect(() => {
    if (merchantData) {
      dispatch({ type: 'SET_MERCHANT_DATA', payload: merchantData });
    }
    dispatch({ type: 'SET_LOADING_MERCHANT', payload: isLoadingMerchant });
  }, [merchantData, isLoadingMerchant]);

  useEffect(() => {
    if (onboardingData) {
      dispatch({ type: 'SET_ONBOARDING_DATA', payload: onboardingData });
    }
    dispatch({ type: 'SET_LOADING_ONBOARDING_DATA', payload: isLoadingOnboardingData });
  }, [onboardingData, isLoadingOnboardingData]);

  // Helper to refetch all data at once
  const refetchAllData = () => {
    return Promise.all([refetchMerchantData(), refetchOnboardingData()]);
  };

  const contextValue: MerchantContextType = {
    ...state,
    refetchMerchantData,
    refetchOnboardingData,
    refetchAllData,
  };

  return <MerchantContext.Provider value={contextValue}>{children}</MerchantContext.Provider>;
};

export default MerchantProvider;
