import { useStore } from '@federated/apps/shell/commonStore';
import { HOMEPAGE_ELEMENTS } from '@FTUX/types/homepage';
import { getLayoutByMerchantType } from '@FTUX/utils/homepage';
import {
  NO_CODE_CHANNEL_OPTIONS,
  PG_CHANNEL_OPTIONS,
} from '@OnboardingExperienceCommons/constants/merchant';
import {
  hasAcceptedAnyPaymentChannel,
  hasAddedWebsite,
} from '@OnboardingExperienceCommons/utils/merchant';
import { useMerchantContext } from '@FTUX/context/MerchantContext';
import { MerchantActivationStatusEnum } from '@OnboardingExperienceCommons/types/merchant';

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
  const mode = useStore((state) => state.session.mode);
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
  const isPgMerchant = hasAcceptedAnyPaymentChannel(paymentChannels, PG_CHANNEL_OPTIONS);
  const isNoCodeMerchant = hasAcceptedAnyPaymentChannel(paymentChannels, NO_CODE_CHANNEL_OPTIONS);

  // Check website verification status
  const websiteStatus =
    onboardingData.merchantOnboardingData.websiteVerificationUpdateStatus?.verificationStatus;
  const websiteWorkflowExists =
    onboardingData.merchantOnboardingData.selfServeWorkflowStatus?.selfServeWorkflow
      ?.isWorkflowExits;
  const isWebsiteAddInProgress = !!websiteStatus?.currentStatus || !!websiteWorkflowExists;

  let pageElements = getLayoutByMerchantType({
    isPgMerchant,
    isNoCodeMerchant,
    hasWebsite: hasAddedWebsite(paymentChannels) || isWebsiteAddInProgress,
    isTestMode: mode === 'test',
  });

  // If merchant has completed a transaction, add the transaction banner at the top
  if (merchantData.merchantById?.activation?.isTransacted && mode !== 'test') {
    pageElements.unshift(HOMEPAGE_ELEMENTS.COMPLETED_TRANSACTION);
  }

  if (!merchantData.merchantById?.activation?.isActivated) {
    pageElements.unshift(HOMEPAGE_ELEMENTS.PREACTIVATION_BANNER);
  }

  return pageElements;
};

export default useHomepageState;
