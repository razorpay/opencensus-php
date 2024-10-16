import { ONBOARDING_PARTNERS, ONBOARDING_STATUS } from './constants';
import { FetchOnboardingStatusResponse, GetOnboardingDetails, GetPartnerDetails } from './types';

/**
 * Function to convert base64 string to Blob
 * @param base64
 * @returns blob
 */
const base64ToBlob = (base64: string) => {
  const byteString = atob(base64); // Decode base64 string

  const ab = new ArrayBuffer(byteString.length);
  const ia = new Uint8Array(ab);

  for (let i = 0; i < byteString.length; i++) {
    ia[i] = byteString.charCodeAt(i);
  }

  return new Blob([ab], { type: 'application/pdf' });
};

export const convertToFile = (base64String: string, irn: string): File => {
  /* Convert the base64 string to a Blob */
  const blob = base64ToBlob(base64String);

  /* Create a new File object from the Blob */
  const file = new File([blob], `${irn.substring(0, 4)}_invoice.pdf`, { type: 'application/pdf' });
  return file;
};

const getPartnerDetials = (
  partnerStatus: FetchOnboardingStatusResponse = [],
): GetPartnerDetails => {
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

export const getOnboardingDetails = (
  partnerStatus: FetchOnboardingStatusResponse = [],
): GetOnboardingDetails => {
  const [einvoice, gstPortal] = getPartnerDetials(partnerStatus ?? []);

  if (!gstPortal?.status) {
    return {
      headerText: 'Link GST to unlock this option',
      buttonText: 'Link your GST account now',
      partner: ONBOARDING_PARTNERS.GST_PORTAL,
      status: undefined,
    };
  }

  if (!einvoice?.status) {
    return {
      headerText: 'Link NIC to unlock this option',
      buttonText: 'Link your NIC portal now',
      partner: ONBOARDING_PARTNERS.E_INVOICE,
      status: undefined,
    };
  }
  return {
    headerText: 'Re-Link NIC to unlock this option',
    buttonText: 'Resume Re-linking of NIC portal',
    partner: ONBOARDING_PARTNERS.E_INVOICE,
    status: ONBOARDING_STATUS.EXPIRED,
  };
};
