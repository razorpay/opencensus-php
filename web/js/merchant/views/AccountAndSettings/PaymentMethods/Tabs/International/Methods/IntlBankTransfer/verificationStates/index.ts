import { useQuery } from '@tanstack/react-query';

import { IntlBankTransferProps } from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/Methods/types';

import { VERIFICATION_STATUS } from './constants';
import {
  isMerchantBlacklisted,
  getEddDetails,
  getUserPurposeCode,
  getVerificationStatus,
} from './helpers';

export const useVerificationStatus = ({ user }: { user: IntlBankTransferProps['user'] }) => {
  const hasPromoterPanName = !!user.promoter_pan_name;
  const isBlacklisted = isMerchantBlacklisted(user.merchant.category);

  // Only call the API if the user has a promoter PAN name and is not blacklisted
  const canUseApi = hasPromoterPanName && !isBlacklisted;

  const {
    data,
    isFetching,
    refetch: refetchEddDetails,
  } = useQuery({
    queryKey: ['international_edd_details'],
    queryFn: getEddDetails,
    enabled: canUseApi,
  });

  const {
    data: purposeCodeData,
    isFetching: isPurposeCodeLoading,
    refetch: refetchPurposeCode,
  } = useQuery({
    queryKey: ['user_purpose_code'],
    queryFn: getUserPurposeCode,
    enabled: canUseApi,
  });

  const { isEddVerified, videoKycStatus, videoKycRejectedReason } = data ?? {};
  const { iecCode, purposeCode, purposeCodeDesc } = purposeCodeData ?? {};

  const verificationStatus = getVerificationStatus({
    hasPromoterPanName,
    isBlacklisted,
    isEddVerified,
    iecCode,
    purposeCode,
    videoKycStatus,
  });

  const shouldShowActivate =
    verificationStatus === VERIFICATION_STATUS.NO_PURPOSE_CODE || verificationStatus === null;

  return {
    iecCode,
    purposeCode,
    purposeCodeDesc,
    isBlacklisted,
    isEddVerified,
    videoKycStatus,
    hasPromoterPanName,
    videoKycRejectedReason,
    shouldShowActivate,
    status: verificationStatus,
    isLoading: isFetching || isPurposeCodeLoading,
    refetchEddDetails,
    refetchPurposeCode,
  };
};
