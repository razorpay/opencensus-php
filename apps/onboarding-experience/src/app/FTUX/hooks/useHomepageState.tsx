import { useStore } from '@federated/apps/shell/commonStore';
import { HOMEPAGE_ELEMENTS } from '@FTUX/types/homepage';
import { getLayoutByMerchantType } from '@FTUX/utils/homepage';
import { PG_CHANNEL_OPTIONS, NO_CODE_CHANNEL_OPTIONS } from '@FTUX/constants/homepage';
import {
  areAnyOptionsAccepted,
  hasAddedWebsite,
} from '@OnboardingExperienceCommons/utils/merchant';
import { useMerchantContext } from '@FTUX/context/MerchantContext';

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
  const { merchantData, onboardingData } = useMerchantContext();

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
    hasWebsite:
      hasAddedWebsite(merchantData?.merchantById?.business?.paymentAcceptanceChannels) ||
      isWebsiteAddInProgress,
  });

  // If merchant has completed a transaction, add the transaction banner at the top
  if (merchantData.merchantById.activation?.isTransacted) {
    pageElements.unshift(HOMEPAGE_ELEMENTS.COMPLETED_TRANSACTION);
  }

  return pageElements;
};

export default useHomepageState;
