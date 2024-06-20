import { useContext } from 'react';

import { BusinessWebsiteAutomationContext, BusinessWebsiteAutomationContextType } from '../context';

const useBusinessWebsiteData = () => {
  const {
    isWebsiteDetailsFetching,
    isWebsiteDetailsFetchError,
    currentStep,
    setCurrentStep,
    websiteUpdateData,
    refetchWebsiteUpdateData,
  } = useContext<BusinessWebsiteAutomationContextType>(BusinessWebsiteAutomationContext);
  return {
    isWebsiteDetailsFetching,
    isWebsiteDetailsFetchError,
    currentStep,
    setCurrentStep,
    websiteUpdateData,
    refetchWebsiteUpdateData,
  };
};
export default useBusinessWebsiteData;
