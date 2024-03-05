import React from 'react';
import FailedPayments from './FailedPayments';
import Refunds from './Refunds';
import {
  EntityAnalyticsProps,
  EntityOverviewType,
} from 'apps/self-serve/src/App/Transactions/v2/Analytics/types';

const EntityAnalytics = ({ type }: EntityAnalyticsProps): JSX.Element | null => {
  switch (type) {
    case EntityOverviewType.Failed:
      return <FailedPayments />;
    case EntityOverviewType.Refunds:
      return <Refunds />;
    default:
      return null;
  }
};

export default EntityAnalytics;
