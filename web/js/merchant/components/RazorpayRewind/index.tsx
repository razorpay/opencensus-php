import React from 'react';

import ErrorBoundary, { Ranks, Teams } from 'common/new-ui/ErrorBoundary';

import RazorpayRewind from './RazorpayRewind';

const FallbackComponent = () => {
  return null;
};

const RewindLandingComponent: React.FC<{
  isRtux?: boolean;
}> = ({ isRtux = false }) => {
  return (
    <ErrorBoundary
      resetOnProps
      rank={Ranks.P1}
      team={Teams.PG_DASHBOARD}
      FallbackComponent={FallbackComponent}
    >
      <RazorpayRewind isRtux={isRtux} />
    </ErrorBoundary>
  );
};

export default RewindLandingComponent;
