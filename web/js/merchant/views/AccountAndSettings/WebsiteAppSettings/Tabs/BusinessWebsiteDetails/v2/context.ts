import { createContext } from 'react';

import { WebsiteSubmitModalSteps, WebsiteUpdateApiData } from './types';

export interface BusinessWebsiteAutomationContextType {
  currentStep: WebsiteSubmitModalSteps;
  setCurrentStep: (step: WebsiteSubmitModalSteps) => void;
  websiteUpdateData: WebsiteUpdateApiData | undefined;
  refetchWebsiteUpdateData: () => void;
  isWebsiteDetailsFetching: boolean;
  isWebsiteDetailsFetchError: boolean;
}

export const BusinessWebsiteAutomationContext = createContext<BusinessWebsiteAutomationContextType>(
  {
    currentStep: WebsiteSubmitModalSteps.ADD_MAIN_PAGE,
    setCurrentStep: () => {},
    websiteUpdateData: {},
    refetchWebsiteUpdateData: () => {},
    isWebsiteDetailsFetching: false,
    isWebsiteDetailsFetchError: false,
  },
);
