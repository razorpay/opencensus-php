import { DashboardGraphQLMerchant } from '@libs/shared-types';

interface CheckIfKycCompleteProps {
  merchant: DashboardGraphQLMerchant;
}

/**
 *
 * checks if merchant kyc is complete or not for  ** Sales assisted onboarding**
 * if online presence exists - shop kyc is not required, only check if L2 form is submitted
 * if online presence does not exist - check if shop images are uploaded AND L2 form is submitted
 */

export const checkIfKycComplete = ({ merchant }: CheckIfKycCompleteProps): boolean => {
  const { android, ios, websites, socialMedia } =
    merchant?.business?.paymentAcceptanceChannels ?? {};
  const hasWebsite = !!websites?.urls?.[0]?.value;
  const hasAndoidIosLinks = !!android?.urls?.[0]?.value && !!ios?.urls?.[0]?.value;
  const hasSocialMediaUrls = !!socialMedia?.socialMediaUrls?.length;
  const merchantHasOnlinePresence = hasWebsite || hasAndoidIosLinks || hasSocialMediaUrls;

  const { shopFront, shopInterior } = merchant?.document ?? {};
  const shopFrontImageUploaded = !!shopFront?.values.length;
  const shopInteriorImageUploaded = !!shopInterior?.values.length;
  const hasShopImages = shopFrontImageUploaded && shopInteriorImageUploaded;

  const isL2FormSubmitted = !!merchant?.activation.isFormSubmitted;

  if (merchantHasOnlinePresence) return isL2FormSubmitted;
  else return hasShopImages && isL2FormSubmitted;
};

export const isKycQualified = (posActivationStatus) => {
  return ['ACTIVATED', 'REJECTED', 'KYC_QUALIFIED_STB'].includes(
    posActivationStatus ?? '',
  );
};
