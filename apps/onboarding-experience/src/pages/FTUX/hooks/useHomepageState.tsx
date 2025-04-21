import { useStore } from '@federated/apps/shell/commonStore';
import { HOMEPAGE_ELEMENTS } from '@FTUX/types/homepage';
import { getLayoutByMerchantType } from '@FTUX/utils/homepage';
import {
  PG_CHANNEL_OPTIONS,
  NO_CODE_CHANNEL_OPTIONS,
  FTUX_FEATURE_FLAGS,
  FTUX_REQUIRED_DATA_REQUEST,
} from '@FTUX/constants/homepage';
import useMerchant from 'apps/onboarding-experience/src/common/hooks/useMerchant';
import useMerchantOnboardingData from 'apps/onboarding-experience/src/common/hooks/useMerchantOnboardingData';
import { WORKFLOW_TYPES } from 'apps/onboarding-experience/src/common/types/merchant';
import {
  areAnyOptionsAccepted,
  hasAddedWebsite,
} from 'apps/onboarding-experience/src/common/utils/merchant';

/**
 * Hook that determines which UI elements to show on the FTUX homepage
 * based on the merchant's profile, payment acceptance channels, and onboarding state.
 *
 * The hook dynamically generates a list of components to render based on:
 * - Whether the merchant is a PG (Payment Gateway) merchant
 * - Whether they use no-code payment solutions
 * - Whether they have added a website
 * - Whether they have completed a transaction
 */
const useHomepageState = (): HOMEPAGE_ELEMENTS[] => {
  const activeUser = useStore((state) => state.session.user);
  const { data: merchantData } = useMerchant();
  const { data: onboardingData } = useMerchantOnboardingData({
    defaultWorkflow: WORKFLOW_TYPES.BUSINESS_WEBSITE,
    defaultFeatureFlags: FTUX_FEATURE_FLAGS,
    requestedData: FTUX_REQUIRED_DATA_REQUEST,
  });

  // Return empty array if required data is not available yet
  if (!merchantData?.merchantById || !onboardingData?.merchantOnboardingData) {
    return [];
  }

  const paymentChannels = merchantData.merchantById.business?.paymentAcceptanceChannels;
  if (!paymentChannels) {
    return [];
  }

  // Determine merchant type based on payment acceptance channels
  const isPgMerchant = areAnyOptionsAccepted(paymentChannels, PG_CHANNEL_OPTIONS);
  const isNoCodeMerchant = areAnyOptionsAccepted(paymentChannels, NO_CODE_CHANNEL_OPTIONS);

  // Check website verification status
  const websiteStatus =
    onboardingData.merchantOnboardingData.websiteVerificationUpdateStatus?.verificationStatus;
  const isWebsiteAddInProgress = !!websiteStatus?.currentStatus && !!websiteStatus.mainPageUrl;

  const pageElements = getLayoutByMerchantType({
    isPgMerchant,
    isNoCodeMerchant,
    hasWebsite: hasAddedWebsite(activeUser) || isWebsiteAddInProgress,
  });

  // If merchant has completed a transaction, add the transaction banner at the top
  if (merchantData.merchantById.activation?.isTransacted) {
    pageElements.unshift(HOMEPAGE_ELEMENTS.COMPLETED_TRANSACTION);
  }

  return pageElements;
};

export default useHomepageState;
