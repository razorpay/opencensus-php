import { useVerificationStatus } from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/Methods/IntlBankTransfer/verificationStates';
import { VerificationStatus } from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/Methods/IntlBankTransfer/verificationStates/types';

import { getBannerActions, getBannerDetails } from './helpers';

export type FailureBannerProps = {
  user: {
    promoter_pan_name: string | null;
    iec_code: string | null;
    merchant: {
      purpose_code: string | null;
      category: string | null;
    };
  };
  onRetry: (status: VerificationStatus[keyof VerificationStatus]) => void;
};

export const useFailureBanner = ({ user, onRetry }: FailureBannerProps) => {
  const { status, videoKycRejectedReason, isLoading } = useVerificationStatus({
    user,
  });

  const banner = getBannerDetails({
    status,
    videoKycRejectedReason,
  });

  const actions = getBannerActions({
    status: banner?.status,
    onRetry,
  });

  return {
    banner,
    isLoading,
    actions,
  };
};
