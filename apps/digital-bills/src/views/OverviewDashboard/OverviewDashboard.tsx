import React from 'react';
import ErrorBoundary from '@razorpay/universe-cli/errorService/ErrorBoundary';
import errorService from '@razorpay/universe-cli/errorService';

import ErrorPage from '@apps/digital-bills/src/common/components/ErrorPage';
import { DIGITAL_BILLS, ERROR_PAGE_DESCRIPTION } from '@apps/digital-bills/src/utils/constants';
import OverviewDashboardContainer from '@apps/digital-bills/src/views/OverviewDashboard/containers/OverviewDashboardContainer';

const OverviewDashboard = (): React.ReactElement => {
  return (
    <ErrorBoundary
      rank={errorService.ErrorRank.P0}
      tags={{ module: DIGITAL_BILLS }}
      fallbackComponent={<ErrorPage description={ERROR_PAGE_DESCRIPTION} />}
    >
      <OverviewDashboardContainer />
    </ErrorBoundary>
  );
};

export default OverviewDashboard;
