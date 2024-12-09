import { TrustedBadgeType } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/types/index';

export function isRazorpayTrustedBadgeActive(trustedBadge: TrustedBadgeType) {
  return (
    trustedBadge?.status?.original?.merchant_status !== 'optout' &&
    (trustedBadge?.status?.original?.status === 'eligible' ||
      trustedBadge?.status?.original?.status === 'whitelist')
  );
}
