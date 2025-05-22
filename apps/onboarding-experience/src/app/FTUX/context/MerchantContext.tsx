import { createContext, useContext } from 'react';
import { deepClone } from '@libs/shared-utils';
import { MerchantResponseType } from 'apps/onboarding-experience/src/common/types/merchant';
import {
  MerchantOnboardingDataResponseType,
  SelectedPlugin,
} from 'apps/onboarding-experience/src/common/types/onboarding';

// State type definition
export interface MerchantState {
  merchantData: MerchantResponseType | undefined;
  onboardingData: MerchantOnboardingDataResponseType | undefined;
  isLoadingMerchant: boolean;
  isLoadingOnboardingData: boolean;
  initiateTwoFaAuth?: () => Promise<boolean>;
}

// Action type definitions
export type MerchantAction =
  | { type: 'SET_MERCHANT_DATA'; payload: MerchantResponseType }
  | { type: 'SET_ONBOARDING_DATA'; payload: MerchantOnboardingDataResponseType }
  | { type: 'SET_LOADING_MERCHANT'; payload: boolean }
  | { type: 'SET_LOADING_ONBOARDING_DATA'; payload: boolean }
  | { type: 'UPDATE_SELECTED_PLUGINS'; payload: SelectedPlugin[] };

// Reducer function
export const merchantReducer = (state: MerchantState, action: MerchantAction): MerchantState => {
  switch (action.type) {
    case 'SET_MERCHANT_DATA':
      return { ...state, merchantData: action.payload };
    case 'SET_ONBOARDING_DATA':
      return { ...state, onboardingData: action.payload };
    case 'SET_LOADING_MERCHANT':
      return { ...state, isLoadingMerchant: action.payload };
    case 'SET_LOADING_ONBOARDING_DATA':
      return { ...state, isLoadingOnboardingData: action.payload };
    case 'UPDATE_SELECTED_PLUGINS':
      const updatedState = deepClone(state);
      if (updatedState?.onboardingData?.merchantOnboardingData?.selectedPlugins) {
        updatedState.onboardingData.merchantOnboardingData.selectedPlugins = action.payload;
      }
      return updatedState || initialState;
    default:
      return state;
  }
};

// Initial state
export const initialState: MerchantState = {
  merchantData: undefined,
  onboardingData: undefined,
  isLoadingMerchant: true,
  isLoadingOnboardingData: true,
  initiateTwoFaAuth: undefined,
};

// Context type with state and actions
export interface MerchantContextType extends MerchantState {
  refetchMerchantData: () => Promise<any>;
  refetchOnboardingData: () => Promise<any>;
  refetchAllData: () => Promise<any>;
  addMerchantWebsitePlugin: (data: { websiteUrl: string; pluginName: string }) => Promise<any>;
  isAddingWebsitePlugin: boolean;
}

export const MerchantContext = createContext<MerchantContextType | undefined>(undefined);

/**
 * Hook that provides access to merchant and onboarding data state
 * Must be used within a MerchantProvider component hierarchy
 */
export const useMerchantContext = (): MerchantContextType => {
  const context = useContext(MerchantContext);
  if (!context) {
    throw new Error('useMerchantContext must be used within a MerchantProvider');
  }
  return context;
};
