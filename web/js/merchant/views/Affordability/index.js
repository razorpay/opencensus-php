import React, { useEffect } from 'react';
import ErrorBoundary, { Teams } from 'common/new-ui/ErrorBoundary';
import DashboardBanner from 'common/ui/DashboardBanner';
import { selfServeTrackInitiate } from 'common/utils/selfServeAnalytics';
import AffordabilityWidget from './AffordabilityWidget';
// eslint-disable-next-line react/no-unsafe

export default function Affordability() {
  useEffect(() => {
    selfServeTrackInitiate({
      selfServeAction: 'Affordability Fetched',
      page: 'Affordability',
      screen: 'Affordability',
    });
  }, []);

  return (
    <React.Fragment>
      <div className="banner-container">
        <DashboardBanner />
      </div>
      <ErrorBoundary resetOnProps team={Teams.AFFORDABILITY}>
        <AffordabilityWidget />
      </ErrorBoundary>
    </React.Fragment>
  );
}
