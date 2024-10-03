import React, { Suspense, useState } from 'react';
import { Alert, Text, Link } from '@razorpay/blade/components';

import lazy from 'merchant/routes/LazyLoader';
import { trackButton } from 'merchant/views/Settlements/InstantSettlements/utils/analytics';
import {
  SCREENS,
  formatAmount,
} from 'merchant/views/Settlements/Settlements/components/Modals/OnDemandV2/helpers';

const ODSRestrictedInfoModal = lazy(
  () =>
    import(
      /* webpackChunkName: "ISRestrictedInfo", webpackPrefetch: true */
      'merchant/views/Settlements/Settlements/components/Modals/OnDemandV2/ODSRestrictedInfoModal'
    ),
);

const MAX_LIMIT = {
  INR: 1500000, // 15,000
} as const;

const ODSRestrictedBanner = ({ currency, maxLimit }: { currency: 'INR'; maxLimit: number }) => {
  const [shouldShowODSRestrictedInfo, setShowODSRestrictedInfo] = useState(false);

  const handleLearnMore = () => {
    setShowODSRestrictedInfo(true);
    trackButton({
      screen: SCREENS.WITHDRAW,
      name: 'ods-restricted-info',
    });
  };
  maxLimit = maxLimit || MAX_LIMIT.INR;

  return (
    <>
      <Alert
        marginTop="spacing.6"
        color="information"
        description={
          <>
            <Text size="small" color="surface.text.gray.subtle" as="span" weight="semibold">
              Early Access:{' '}
            </Text>
            Withdraw upto 60% of your balance upto {formatAmount(maxLimit, currency)}.{' '}
            <Link variant="button" size="small" onClick={handleLearnMore}>
              Learn More
            </Link>
          </>
        }
        isDismissible={false}
      />
      <Suspense fallback={null}>
        <ODSRestrictedInfoModal
          maxLimit={maxLimit}
          currency={currency}
          isOpen={shouldShowODSRestrictedInfo}
          onDismiss={() => setShowODSRestrictedInfo(false)}
        />
      </Suspense>
    </>
  );
};

export { ODSRestrictedBanner };
