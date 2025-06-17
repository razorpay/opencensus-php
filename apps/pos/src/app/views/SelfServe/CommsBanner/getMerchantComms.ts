import { RazorpayUser as User } from '@libs/shared-types';
import { KYC_STATUS_TYPES, SCENARIOS } from 'apps/pos/src/app/views/SelfServe/constants';
import { CommsItem, OrderDetailsItem } from 'apps/pos/src/app/views/SelfServe/types';

const checkIfMerchantHasOnlinePresence = (user): boolean => {
  const hasBusinessWebsite = user?.business_website;
  const hasAppstoreUrl = user?.appstore_url;
  const hasPlaystoreUrl = user?.playstore_url;
  const hasSocialMediaPresence = user?.merchant_business_detail?.website_details?.social_media_urls;

  return hasBusinessWebsite || hasAppstoreUrl || hasPlaystoreUrl || hasSocialMediaPresence;
};

type GetMerchantComms = {
  user: User;
  mode: 'test' | 'live';
  order?: OrderDetailsItem;
  isMobileOrTablet: boolean;
};

interface STAGES extends Omit<CommsItem, 'description'> {
  description: JSX.Element | string | null;
}

export const getMerchantComms = ({
  user,
  mode,
  order,
  isMobileOrTablet,
}: GetMerchantComms): STAGES[] => {
  const isDeviceOrdered = ['paid', 'delivered', 'rejected'].includes(order?.status ?? '');

  const offlineKycStatus = user?.pos_activation_status;
  const onlineKycStatus = user?.activation_status;

  const {
    UNDER_REVIEW,
    NEEDS_CLARIFICATION,
    REJECTED,
    ACTIVATED,
    KYC_QUALIFIED_STB,
    KYC_QUALIFIED_UNACTIVATED,
  } = KYC_STATUS_TYPES;

  let scenario: CommsItem[] = [];

  if (order?.status === 'delivered') {
    scenario = [];
  } else if (mode === 'test' && !offlineKycStatus) {
    scenario = SCENARIOS[0];
  } else if (
    offlineKycStatus === UNDER_REVIEW &&
    onlineKycStatus !== NEEDS_CLARIFICATION &&
    isDeviceOrdered
  ) {
    scenario = SCENARIOS[1];
  } else if (
    offlineKycStatus === UNDER_REVIEW &&
    onlineKycStatus !== NEEDS_CLARIFICATION &&
    !isDeviceOrdered
  ) {
    scenario = SCENARIOS[2];
  } else if (
    (offlineKycStatus === NEEDS_CLARIFICATION && isDeviceOrdered) ||
    (onlineKycStatus === NEEDS_CLARIFICATION &&
      offlineKycStatus === UNDER_REVIEW &&
      isDeviceOrdered)
  ) {
    scenario = SCENARIOS[3];
  } else if (
    (offlineKycStatus === NEEDS_CLARIFICATION && !isDeviceOrdered) ||
    (onlineKycStatus === NEEDS_CLARIFICATION &&
      offlineKycStatus === UNDER_REVIEW &&
      !isDeviceOrdered)
  ) {
    scenario = SCENARIOS[4];
  } else if (offlineKycStatus === KYC_QUALIFIED_STB && isDeviceOrdered) {
    scenario = SCENARIOS[5];
  } else if (offlineKycStatus === KYC_QUALIFIED_STB && !isDeviceOrdered) {
    scenario = SCENARIOS[6];
  } else if (offlineKycStatus === ACTIVATED && isDeviceOrdered) {
    scenario = SCENARIOS[7];
  } else if (offlineKycStatus === REJECTED && onlineKycStatus === ACTIVATED && isDeviceOrdered) {
    scenario = SCENARIOS[8];
  } else if (offlineKycStatus === REJECTED && onlineKycStatus === ACTIVATED && !isDeviceOrdered) {
    scenario = SCENARIOS[9];
  } else if (offlineKycStatus === REJECTED && onlineKycStatus === REJECTED && isDeviceOrdered) {
    const isRefundInitiated = !!order?.refund;
    const isRefundComplete = order?.refund?.status === 'processed';
    if (isRefundInitiated && isRefundComplete) {
      scenario = SCENARIOS[12];
    } else if (isRefundInitiated && !isRefundComplete) {
      scenario = SCENARIOS[11];
    } else {
      scenario = SCENARIOS[10];
    }
  } else if (offlineKycStatus === REJECTED && onlineKycStatus === REJECTED && !isDeviceOrdered) {
    scenario = SCENARIOS[13];
  } else if (checkIfMerchantHasOnlinePresence(user) && !offlineKycStatus) {
    scenario = SCENARIOS[14];
  } else if (!checkIfMerchantHasOnlinePresence(user) && !offlineKycStatus) {
    scenario = SCENARIOS[15];
  } else if (offlineKycStatus === REJECTED && onlineKycStatus === UNDER_REVIEW && isDeviceOrdered) {
    scenario = SCENARIOS[16];
  } else if (
    offlineKycStatus === REJECTED &&
    onlineKycStatus === KYC_QUALIFIED_UNACTIVATED &&
    isDeviceOrdered
  ) {
    scenario = SCENARIOS[17];
  } else if (
    offlineKycStatus === REJECTED &&
    onlineKycStatus === UNDER_REVIEW &&
    !isDeviceOrdered
  ) {
    scenario = SCENARIOS[18];
  } else if (
    offlineKycStatus === REJECTED &&
    onlineKycStatus === KYC_QUALIFIED_UNACTIVATED &&
    !isDeviceOrdered
  ) {
    scenario = SCENARIOS[19];
  }

  const customParams = {
    user: { ...user },
    order,
    isMobileOrTablet,
  };

  return scenario.map(({ status, title, description, cta }) => ({
    status,
    title,
    description: typeof description === 'function' ? description(customParams) : description,
    cta: (cta ?? []).map((cta) => ({
      ...cta,
      url: typeof cta.url === 'function' ? cta.url(customParams) : cta.url,
    })),
  }));
};
