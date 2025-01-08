import { merchantFetch } from 'merchant/utils/ajax';

import {
  BLACKLISTED_MCC_CODES_SET,
  V_KYC_REJECTION,
  VKycStatus,
  V_KYC_STATUS,
  EDD_STATUS,
  VERIFICATION_STATUS,
  SHOW_V_KYC_BANNER_WHEN,
} from './constants';

export const getEddDetails = (): Promise<{
  eddStatus: string | undefined;
  isEddVerified: boolean;
  videoKycStatus: VKycStatus[keyof VKycStatus];
  videoKycRejectedReason: string | undefined;
}> => {
  return merchantFetch({
    url: 'edd_details',
    mode: 'live',
  }).then((res) => {
    const vKycDetails = res?.data?.details?.find((item) => item.type === 'vkyc');

    let videoKycStatus = vKycDetails?.status as VKycStatus[keyof VKycStatus];
    const videoKycRejectedReason = vKycDetails?.reason?.toLowerCase() ?? '';
    const eddStatus = res?.data?.status;
    const isEddVerified = eddStatus === EDD_STATUS.VERIFIED;

    if (videoKycRejectedReason === V_KYC_REJECTION.FRAUD_CUSTOMER) {
      videoKycStatus = V_KYC_STATUS.FRAUD_REJECTED;
    }

    return {
      eddStatus,
      isEddVerified,
      videoKycStatus,
      videoKycRejectedReason,
    };
  });
};

export const getUserPurposeCode = (): Promise<{
  purposeCode: string | null;
  purposeCodeDesc: string | null;
  iecCode: string | null;
}> => {
  return merchantFetch({
    url: 'users/purpose/code',
    mode: 'live',
    method: 'get',
  }).then((res) => {
    const data = res?.data?.merchants?.[0] ?? {};

    return {
      purposeCode: data.purpose_code,
      purposeCodeDesc: data.purpose_code_desc,
      iecCode: data.iec_code,
    };
  });
};

export const postInternationalVirtualAccountToggle = (action: string) =>
  merchantFetch({
    url: 'international/virtual_account/toggle',
    method: 'post',
    mode: 'live',
    data: {
      action,
    },
  });

export const postInternationalVirtualAccountActivate = (currency?: string) =>
  merchantFetch({
    url: 'international/virtual_accounts',
    method: 'post',
    mode: 'live',
    data: {
      enable_all_currencies: !currency,
      accept_b2b_tnc: true,
      va_currency: currency,
    },
  });

export const getInternationalVirtualAccounts = (): Promise<{
  data?: {
    accounts?: {
      va_currency: string;
      routing_code: string;
      routing_type: string;
      account_number: string;
      beneficiary_name: string;
      bank_name: string;
      bank_address: string;
      status: string;
    }[];
    status?: string;
  };
}> =>
  merchantFetch({
    url: 'international/virtual_accounts',
    mode: 'live',
  });

export const isMerchantBlacklisted = (mccCode: string | null) =>
  mccCode ? BLACKLISTED_MCC_CODES_SET.has(mccCode) : false;

export const getVerificationStatus = ({
  hasPromoterPanName,
  isBlacklisted,
  iecCode,
  purposeCode,
  isEddVerified,
  videoKycStatus,
}: {
  hasPromoterPanName: boolean;
  isBlacklisted: boolean;
  isEddVerified: boolean | undefined;
  iecCode: string | null | undefined;
  purposeCode: string | null | undefined;
  videoKycStatus: VKycStatus[keyof VKycStatus] | undefined;
}) => {
  if (isBlacklisted) {
    return VERIFICATION_STATUS.BLACKLISTED;
  }

  if (!hasPromoterPanName) {
    return VERIFICATION_STATUS.NO_PROMOTER_PAN_NAME;
  }

  if (!purposeCode) {
    return VERIFICATION_STATUS.NO_PURPOSE_CODE;
  }

  if (!iecCode) {
    return VERIFICATION_STATUS.NO_IEC_CODE;
  }

  if (purposeCode && iecCode && !isEddVerified && videoKycStatus === V_KYC_STATUS.NOT_VERIFIED) {
    return VERIFICATION_STATUS.EDD_NOT_VERIFIED;
  }

  if (
    !isEddVerified &&
    videoKycStatus &&
    videoKycStatus !== V_KYC_STATUS.NOT_VERIFIED &&
    SHOW_V_KYC_BANNER_WHEN.has(videoKycStatus)
  ) {
    return videoKycStatus;
  }

  return null;
};
