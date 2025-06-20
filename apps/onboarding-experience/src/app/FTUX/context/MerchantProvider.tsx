import React, { ReactNode, useReducer, useEffect } from 'react';
import errorService from '@razorpay/universe-cli/errorService';
import useMerchant from '@OnboardingExperienceCommons/hooks/useMerchant';
import useMerchantOnboardingData from '@OnboardingExperienceCommons/hooks/useMerchantOnboardingData';
import { ApiKeys, WORKFLOW_TYPES } from '@OnboardingExperienceCommons/types/merchant';
import { FTUX_FEATURE_FLAGS, FTUX_REQUIRED_DATA_REQUEST } from '@FTUX/constants/homepage';
import {
  MerchantContext,
  merchantReducer,
  initialState,
  MerchantContextType,
} from './MerchantContext';
import useWebsitePlugin from '@OnboardingExperienceCommons/hooks/useWebsitePlugin';
import { AddMerchantSelectedPluginResponse } from '@OnboardingExperienceCommons/types/onboarding';
import { TwoFaAuthProps } from '@FTUX/types/common';
import useMerchantApiKeys from '@OnboardingExperienceCommons/hooks/useMerchantApiKeys';
import { ApiKeyDelay } from '@OnboardingExperienceCommons/types/apiKeys';

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
    isRefetching: isRefetchingMerchant,
    isError: isMerchantError,
    error: merchantError,
  } = useMerchant();

  // Fetch merchant onboarding data
  const {
    data: onboardingData,
    isLoading: isLoadingOnboardingData,
    refetch: refetchOnboardingData,
    isRefetching: isRefetchingOnboardingData,
    isError: isOnboardingError,
    error: onboardingError,
  } = useMerchantOnboardingData({
    defaultWorkflow: WORKFLOW_TYPES.BUSINESS_WEBSITE,
    defaultFeatureFlags: FTUX_FEATURE_FLAGS,
    requestedData: FTUX_REQUIRED_DATA_REQUEST,
  });

  if (isMerchantError || merchantError || (!isLoadingMerchant && !merchantData?.merchantById?.id)) {
    errorService.captureError(merchantError || new Error('Unable to fetch merchant data!'), {
      tags: { module: 'FTUX_HOMEPAGE' },
      // P2 error since, this will be catched by graphql also
      rank: errorService.ErrorRank.P2,
      extra: {
        info: merchantError,
      },
    });
  }

  if (
    isOnboardingError ||
    onboardingError ||
    (!isLoadingOnboardingData && !onboardingData?.merchantOnboardingData)
  ) {
    errorService.captureError(
      onboardingError || new Error('Unable to fetch merchant onboarding data!'),
      {
        tags: { module: 'FTUX_HOMEPAGE' },
        // P2 error since, this will be catched by graphql also
        rank: errorService.ErrorRank.P2,
        extra: {
          info: onboardingError,
        },
      },
    );
  }

  // Add merchant plugin mutation
  const { mutateAsync: addMerchantPluginMutation } = useWebsitePlugin();

  const { generateApiKeyMutation, regenerateApiKeyMutation } = useMerchantApiKeys();

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
          // Update the state with the new selected plugins
          dispatch({
            type: 'UPDATE_SELECTED_PLUGINS',
            payload: (data as AddMerchantSelectedPluginResponse).addMerchantSelectedPlugin.plugins,
          });
        },
      },
    );
  };

  const generateApiKey = () => {
    return generateApiKeyMutation(undefined, {
      onSuccess: (response) => {
        if (response.merchantApiKeysCreate?.id)
          dispatch({
            type: 'UPDATE_MERCHANT_API_KEY',
            payload: response.merchantApiKeysCreate as ApiKeys,
          });
      },
    });
  };

  const regenerateApiKey = ({
    keyRollDelay,
    oldApiKeyId,
  }: {
    keyRollDelay: ApiKeyDelay;
    oldApiKeyId: string;
  }) => {
    return regenerateApiKeyMutation(
      {
        keyRollDelay,
        oldApiKeyId,
      },
      {
        onSuccess: (response) => {
          if (response.merchantApiKeyRegenerate?.newApiKey?.id)
            dispatch({
              type: 'UPDATE_MERCHANT_API_KEY',
              payload: response.merchantApiKeyRegenerate.newApiKey as ApiKeys,
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
    initiateTwoFaAuth,
    generateApiKey,
    regenerateApiKey,
    isRefetchingAllData: isRefetchingMerchant || isRefetchingOnboardingData,
  };

  return <MerchantContext.Provider value={contextValue}>{children}</MerchantContext.Provider>;
};

export default MerchantProvider;
