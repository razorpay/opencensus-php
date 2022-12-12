import React, { useEffect } from 'react';

import { selfServeTrackInitiate } from 'common/utils/selfServeAnalytics';
import './affordability-widget-content.styl';

import OnBording from './Onboarding';

export default function AffordabilityWidget() {
  useEffect(() => {
    selfServeTrackInitiate({
      selfServeAction: 'Affordability Fetched',
      page: 'Affordability',
      screen: 'Affordability',
    });
  }, []);

  return <OnBording />;
}
