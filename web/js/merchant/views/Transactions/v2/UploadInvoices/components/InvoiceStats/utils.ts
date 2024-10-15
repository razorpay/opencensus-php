import { FetchOnboardingStatusResponse } from 'merchant/views/Transactions/v2/UploadInvoices/types';

import { ONBOARDING_PARTNERS, ONBOARDING_STATUS } from './constant';
import { OnboardingCardDetailsType } from './types';

export const getPartnerDetials = (partnerStatus: FetchOnboardingStatusResponse = []) => {
  const einvoice = partnerStatus.find((partner) => partner.name === ONBOARDING_PARTNERS.E_INVOICE);
  const gstPortal = partnerStatus.find(
    (service) => service.name === ONBOARDING_PARTNERS.GST_PORTAL,
  );
  return [einvoice, gstPortal];
};

export const isMerchantFullyOnboarded = (partnerStatus: FetchOnboardingStatusResponse): boolean => {
  const [einvoice, gstPortal] = getPartnerDetials(partnerStatus ?? []);

  return (
    einvoice?.status === ONBOARDING_STATUS.ONBOARDED &&
    gstPortal?.status === ONBOARDING_STATUS.ONBOARDED
  );
};

export const getOnboardingCardDetails = (
  partnerStatus: FetchOnboardingStatusResponse = [],
): OnboardingCardDetailsType => {
  const [einvoice, gstPortal] = getPartnerDetials(partnerStatus ?? []);

  if (!gstPortal?.status) {
    return {
      bannerText: 'Tired of uploading invoice for every transaction?',
      buttonText: 'Link your GST account now',
      partner: ONBOARDING_PARTNERS.GST_PORTAL,
      status: undefined,
    };
  }

  if (!einvoice?.status) {
    return {
      bannerText: 'Complete just 1 more step to auto fetch e-invoices!',
      buttonText: 'Link your NIC portal now',
      partner: ONBOARDING_PARTNERS.E_INVOICE,
      status: undefined,
    };
  }
  return {
    bannerText: 'Complete just 1 more step to auto fetch e-invoices!',
    buttonText: 'Resume linking of GST portal',
    partner: ONBOARDING_PARTNERS.GST_PORTAL,
    status: ONBOARDING_STATUS.EXPIRED,
  };
};
