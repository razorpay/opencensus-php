import React, { Suspense } from 'react';
import ErrorBoundary, { Ranks, Teams } from 'common/new-ui/ErrorBoundary';
import lazy from 'merchant/routes/LazyLoader';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';

// eslint-disable-next-line prettier/prettier
const EcosystemDowntimesContainer = lazy(
  () =>
    import(
      /* webpackChunkName: 'EcosystemDowntimes' */ 'merchant/views/EcosystemDowntimes/containers/EcosystemDowntimesContainer'
    ),
);

// eslint-disable-next-line prettier/prettier
const StatusDetails = lazy(
  () =>
    import(/* webpackChunkName: "StatusDetails" */ 'merchant/components/HeaderNav/StatusDetails'),
);

type EcosystemDowntimesProps = {
  mode: string;
  showMobileNav: boolean;
  isRTUXHomepage: boolean;
  isConnectedNavigation?: boolean;
};

const EcosystemDowntimes = ({
  mode,
  showMobileNav,
  isRTUXHomepage,
  isConnectedNavigation = false,
}: EcosystemDowntimesProps): JSX.Element => {
  return (
    <ErrorBoundary
      tags={{ page: 'ecosystemDowntimes' }}
      FallbackComponent={() => (
        <SuspenseWithLoader>
          <StatusDetails AppMode={mode} showMobileNav={showMobileNav} isForceOpen />
        </SuspenseWithLoader>
      )}
      resetOnProps
      rank={Ranks.P1}
      team={Teams.AVAILABILITY_AND_DOWNTIME}
    >
      <Suspense fallback={null}>
        <EcosystemDowntimesContainer
          isRTUXHomepage={isRTUXHomepage}
          isConnectedNavigation={isConnectedNavigation}
        />
      </Suspense>
    </ErrorBoundary>
  );
};

export default EcosystemDowntimes;
