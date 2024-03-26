import React from 'react';

import ErrorBoundary, { Ranks, Teams } from 'common/new-ui/ErrorBoundary';
import { User } from 'common/typings';

import PaymentsRecap from './PaymentsRecap';

const FallbackComponent = () => {
  return null;
};

const Entry: React.FC<{
  user: User;
  bannerVariant: 'desktop' | 'mobile';
}> = ({ user, bannerVariant }) => {
  const shouldMobileBanner = bannerVariant === 'mobile';

  return (
    <ErrorBoundary
      resetOnProps
      rank={Ranks.P1}
      team={Teams.PG_DASHBOARD}
      FallbackComponent={FallbackComponent}
    >
      <PaymentsRecap user={user} showMobileBanner={shouldMobileBanner} />
    </ErrorBoundary>
  );
};

export default Entry;
