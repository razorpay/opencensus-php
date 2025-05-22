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
import useWebsitePlugin from 'apps/onboarding-experience/src/common/hooks/useWebsitePlugin';
import { AddMerchantSelectedPluginResponse } from 'apps/onboarding-experience/src/common/types/onboarding';
import { TwoFaAuthProps } from '@FTUX/types/common';

interface MerchantProviderProps {
  children: ReactNode;
  triggerTwoFaAuth: (data: TwoFaAuthProps) => void;
}

/**
 * Provider that manages merchant and onboarding data fetching and state
 * Exposes data, loading states, and refetch methods through context
 */
const MerchantProvider: React.FC<MerchantProviderProps> = ({ children, triggerTwoFaAuth }) => {
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

  // Add merchant plugin mutation
  const { mutateAsync: addMerchantPluginMutation, isLoading: isAddingWebsitePlugin } =
    useWebsitePlugin();

  // Function to add merchant plugin
  const addMerchantWebsitePlugin = ({
    websiteUrl,
    pluginName,
  }: {
    websiteUrl: string;
    pluginName: string;
  }) => {
    return addMerchantPluginMutation(
      { websiteUrl, pluginName },
      {
        onSuccess: (data) => {
          console.log('data', data);
          // Update the state with the new selected plugins
          dispatch({
            type: 'UPDATE_SELECTED_PLUGINS',
            payload: (data as AddMerchantSelectedPluginResponse).addMerchantSelectedPlugin.plugins,
          });
        },
      },
    );
  };

  // Function to trigger two factor authentication and return boolean based on result
  const initiateTwoFaAuth = async (): Promise<boolean> => {
    return new Promise((resolve) => {
      triggerTwoFaAuth({
        modes: ['test', 'live'],
        enforceVerifyOtp: true,
        // nosemgrep : ssc-adb055b9-fed0-4d70-a57d-eb9825b09449
        onUserTwoFaVerified: () => {
          resolve(true);
        },
        // nosemgrep : ssc-adb055b9-fed0-4d70-a57d-eb9825b09449
        onFlowTermination: () => {
          resolve(false);
        },
      });
    });
  };

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
    addMerchantWebsitePlugin,
    isAddingWebsitePlugin,
    initiateTwoFaAuth,
  };

  return <MerchantContext.Provider value={contextValue}>{children}</MerchantContext.Provider>;
};

export default MerchantProvider;
