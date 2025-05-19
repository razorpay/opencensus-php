import React, { useEffect, useState } from 'react';
import moment from 'moment';

import { showNotification } from 'merchant_common/reducers/notifications';
import { fetchSSOLoginGraphData } from 'merchant/views/MagicCheckout/SSODashboard/api';
import type { LoginGraphData, TimeRange } from 'merchant/views/MagicCheckout/SSODashboard/types';

import { Box, Heading, Text } from '@razorpay/blade/components';

import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import LoginWidget from 'merchant/views/MagicCheckout/SSODashboard/components/LoginWidget';
import CustomerData from 'merchant/views/MagicCheckout/SSODashboard/components/CustomerData';
import SSOLoginGraph from 'merchant/views/MagicCheckout/SSODashboard/components/SSOLoginGraph';
import ExportWidget from 'merchant/views/MagicCheckout/SSODashboard/components/ExportWidget';
import DateRangeFilters from 'merchant/views/MagicCheckout/SSODashboard/components/DateRangeFilters';

const defaultEndDate = moment(); // nosemgrep: ssc-1e99e462-0fc5-4109-ad52-d2b5a7048232
const defaultStartDate = moment().subtract(2, 'days'); // nosemgrep: ssc-1e99e462-0fc5-4109-ad52-d2b5a7048232

const SSODashboard = () => {
  const [timeRange, setTimeRange] = useState<TimeRange>({
    start: defaultStartDate,
    end: defaultEndDate,
  });
  const [isLoading, setIsLoading] = useState(true);
  const [loginGraphData, setLoginGraphData] = useState<LoginGraphData>({
    totalLoggedIn: 0,
    newLogin: 0,
    graphData: null,
    aggregate: 'daily',
  });

  const getLoginGraphData = async () => {
    try {
      setIsLoading(true);
      const response = await fetchSSOLoginGraphData({
        from: timeRange.start.valueOf(),
        to: timeRange.end.valueOf(),
      });
      setLoginGraphData({
        totalLoggedIn: response.data?.total_logged_in_users,
        newLogin: response.data?.new_account_creations,
        graphData: response.data?.metrics,
        aggregate: response.data?.aggregate,
      });
    } catch (error: any) {
      showNotification({
        type: 'error',
        message: error?.errors?.[0] || 'Something went wrong. Please try again later.',
      });
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    getLoginGraphData();
  }, [timeRange.end, timeRange.start]);

  return (
    <SuspenseWithLoader type="center">
      <Box display="flex" flexDirection="column" backgroundColor="surface.background.gray.intense">
        <Box display="flex" alignItems="center" marginBottom="spacing.8">
          <Box display="flex" flexDirection="column" flex="1">
            <Heading size="large" weight="semibold">
              Login with Razorpay Analytics
            </Heading>
            <Text variant="body" size="medium">
              Track every sign-in, spot growth trends, and export user cohorts, all from one place.
            </Text>
          </Box>
          <ExportWidget timeRange={timeRange} />
        </Box>
        <DateRangeFilters setTimeRange={setTimeRange} />
        <LoginWidget
          isLoading={isLoading}
          totalLogin={loginGraphData.totalLoggedIn}
          newLogin={loginGraphData.newLogin}
        />
        <SSOLoginGraph
          isFetching={isLoading}
          aggregate={loginGraphData.aggregate}
          data={loginGraphData.graphData}
        />
        <CustomerData timeRange={timeRange} />
      </Box>
    </SuspenseWithLoader>
  );
};

export default SSODashboard;
