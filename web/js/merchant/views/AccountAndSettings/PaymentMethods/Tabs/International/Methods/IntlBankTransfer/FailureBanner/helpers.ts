import { AlertProps } from '@razorpay/blade/components';

import {
  V_KYC_STATUS,
  VERIFICATION_STATUS,
} from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/Methods/IntlBankTransfer/verificationStates/constants';
import { VerificationStatus } from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/Methods/IntlBankTransfer/verificationStates/types';

import {
  FAILURE_BANNER,
  SHOW_V_KYC_BANNER_WHEN,
  V_KYC_REJECTION_REASON_MAPPING,
} from './constants';

export const getBannerDetails = ({
  status,
  videoKycRejectedReason,
}: {
  status: VerificationStatus[keyof VerificationStatus] | null;
  videoKycRejectedReason: string | null | undefined;
}):
  | (Pick<AlertProps, 'title' | 'color' | 'description'> & {
      status: VerificationStatus[keyof VerificationStatus];
    })
  | null => {
  if (!status || status === VERIFICATION_STATUS.NO_PURPOSE_CODE) {
    // if purpose code is missing, then don't show the banner
    return null;
  }

  if (status === VERIFICATION_STATUS.BLACKLISTED) {
    return {
      ...FAILURE_BANNER[status],
      status,
    };
  }

  if (status === VERIFICATION_STATUS.NO_PROMOTER_PAN_NAME) {
    return {
      ...FAILURE_BANNER[status],
      status,
    };
  }

  if (status === VERIFICATION_STATUS.NO_IEC_CODE) {
    return {
      ...FAILURE_BANNER[status],
      status,
    };
  }

  if (status === VERIFICATION_STATUS.EDD_NOT_VERIFIED) {
    return {
      ...FAILURE_BANNER[status],
      status,
    };
  }

  if (SHOW_V_KYC_BANNER_WHEN.has(status)) {
    const banner = FAILURE_BANNER[status];
    if (status === V_KYC_STATUS.FRAUD_REJECTED && videoKycRejectedReason) {
      banner.description = (banner.description as string).replace(
        '<insert reason>',
        videoKycRejectedReason,
      );
    } else {
      banner.description = V_KYC_REJECTION_REASON_MAPPING[videoKycRejectedReason ?? ''] ?? '';
    }

    return {
      ...banner,
      status,
    };
  }

  return null;
};

export const getBannerActions = ({
  status,
  onRetry,
}: {
  status: VerificationStatus[keyof VerificationStatus] | undefined;
  onRetry: (status: VerificationStatus[keyof VerificationStatus]) => void;
}): AlertProps['actions'] => {
  if (!status) {
    return undefined;
  }

  if (status === VERIFICATION_STATUS.V_KYC_REJECTED) {
    return {
      primary: {
        onClick: () => onRetry(status),
        text: 'Retry video KYC',
      },
    };
  }

  if (status === VERIFICATION_STATUS.V_KYC_INITIATED) {
    return {
      primary: {
        onClick: () => onRetry(status),
        text: 'Resume video KYC',
      },
    };
  }

  if (status === VERIFICATION_STATUS.EDD_NOT_VERIFIED) {
    return {
      primary: {
        onClick: () => onRetry(status),
        text: 'Update Now',
      },
    };
  }

  if (status === VERIFICATION_STATUS.NO_IEC_CODE) {
    return {
      primary: {
        onClick: () => onRetry(status),
        text: 'Update Now',
      },
    };
  }

  return undefined;
};
