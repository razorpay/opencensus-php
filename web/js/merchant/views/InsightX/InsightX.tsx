import React, { useEffect, useState, useRef, useCallback } from 'react';
import {
  BookIcon,
  Box,
  Button,
  Heading,
  RefreshIcon,
  TabItem,
  TabList,
  TabPanel,
  Tabs,
} from '@razorpay/blade/components';
import { embedDashboard } from '@superset-ui/embedded-sdk';
import moment from 'moment';
import { Link } from '@razorpay/blade/components';

import { useMobile } from 'common/hooks/useMobile';
import { analyticsTrack } from 'common/utils/analytics';

import { DateRangePicker } from './DateRangePicker';
import { EmptyState } from './EmptyState';
import { InsightXErrorBoundary } from './InsightXErrorBoundary';
import {
  SUPERSET_DASHBOARD_IDS,
  INSIGHTX_TABS,
  DOCUMENTATION_ROUTES,
  SUPERSET_URL,
  dashboardHeights,
} from './constants';
import { useSupersetDashboard } from './hooks/useSupersetDashboard';
import { SuperSetDashboardWrapper } from './styled';
import { handleRefreshClick } from './utils/handleRefreshUtils';
import { getPrevDayTimestamps } from './utils/prevDayUtils';
import { setupTokenRefresh } from './utils/refreshTokenUtils';
import { renderLoadingOrErrorState } from './utils/renderUtils';
import { mobileBreakoints } from '../Transactions/v2/common/constants';

const InsightX: React.FC = () => {
  const { startOfPrevDay, endOfPrevDay } = getPrevDayTimestamps();
  const { data, refetch, isLoading, isError, error } = useSupersetDashboard();
  const [guestToken, setGuestToken] = useState<string | null>(null);
  const [activeTab, setActiveTab] = useState<string>(INSIGHTX_TABS[0]?.name || '');
  const [date, setDate] = useState<{ from?: number; to?: number }>({});
  const isMobile = useMobile(mobileBreakoints);
  const supersetRef = useRef<HTMLElement>(null);
  const tabStartTimeRef = useRef<moment.Moment>(moment());
  const selectedDateCallback = (date: { from?: number; to?: number }) => {
    setDate(date);

    analyticsTrack({
      objectName: 'InsightX',
      actionName: 'Date Range Selected',
      screen: 'insightx/root',
      properties: { from: date.from, to: date.to },
    });
  };
  const embedSupersetDashboard = useCallback(() => {
    if (!supersetRef.current || !guestToken || !data) return;
    const embedId = SUPERSET_DASHBOARD_IDS[activeTab];
    embedDashboard({
      id: embedId,
      supersetDomain: SUPERSET_URL,
      mountPoint: supersetRef.current,
      fetchGuestToken: async () => guestToken,
      dashboardUiConfig: {
        hideTitle: true,
        hideChartControls: true,
        filters: {
          expanded: false,
          visible: false,
        },
        urlParams: {
          standalone: 2,
          show_filters: 0,
          from_ts: date.from ?? startOfPrevDay,
          to_ts: date.to ?? endOfPrevDay,
        },
      },
    });
  }, [activeTab, date.from, date.to, guestToken]);

  useEffect(() => {
    const start = moment();

    return () => {
      const end = moment();
      const timeSpent = end.diff(start, 'seconds');
      analyticsTrack({
        objectName: 'InsightX',
        actionName: 'Time spent in Screen',
        screen: 'insightx/root',
        properties: { timeSpent },
      });
    };
  }, []);

  useEffect(() => {
    if (!guestToken && data) {
      setGuestToken(data);
    }
    analyticsTrack({
      objectName: 'InsightX',
      actionName: 'Token Fetch Initiated',
      screen: 'insightx/root',
    });
  }, [data, guestToken]);

  useEffect(() => {
    const intervalId = setupTokenRefresh({
      refetch,
      setGuestToken,
      embedSupersetDashboard,
      analyticsTrack,
    });

    return () => clearInterval(intervalId);
  }, [refetch, embedSupersetDashboard]);

  useEffect(() => {
    if (guestToken && activeTab && supersetRef.current) {
      const embedId = SUPERSET_DASHBOARD_IDS[activeTab];
      embedSupersetDashboard();
      analyticsTrack({
        objectName: 'InsightX',
        actionName: 'Dashboard Embedded',
        screen: 'insightx/root',
        properties: { embedId, tab: activeTab },
      });
    }
  }, [guestToken, activeTab, date, embedSupersetDashboard]);

  const trackTimeSpentOnTab = useCallback((tabName: string) => {
    const endTime = moment();
    const timeSpent = endTime.diff(tabStartTimeRef.current, 'seconds');
    analyticsTrack({
      objectName: 'InsightX',
      actionName: 'Time Spent on Tab',
      screen: 'insightx/root',
      properties: { tab: tabName, timeSpent },
    });
  }, []);

  useEffect(() => {
    tabStartTimeRef.current = moment();

    return () => {
      trackTimeSpentOnTab(activeTab);
    };
  }, [trackTimeSpentOnTab, activeTab]);

  const handleTabChange = (name: string) => {
    if (activeTab === name) {
      return;
    }
    setActiveTab(name);
    analyticsTrack({
      objectName: 'InsightX',
      actionName: 'InsightX Tab Changed',
      screen: 'insightx/root',
      properties: { tab: name },
    });
  };

  const shouldShowEmptyState = !isLoading && !data;

  const retryHandler = () => {
    if (isError) {
      refetch();
    }
  };

  const renderState = renderLoadingOrErrorState(
    isLoading,
    isError,
    guestToken,
    error,
    shouldShowEmptyState,
    refetch,
    retryHandler,
  );
  if (renderState) {
    return renderState;
  }

  return (
    <InsightXErrorBoundary>
      <Box
        paddingTop="spacing.5"
        backgroundColor="surface.background.gray.intense"
        paddingX="spacing.7"
        marginRight={isMobile ? 'spacing.0' : 'spacing.2'}
      >
        <Box
          display="flex"
          flexWrap="wrap"
          paddingY="spacing.7"
          justifyContent="space-between"
          alignItems="center"
        >
          <Heading color="surface.text.staticBlack.subtle" size="2xlarge" weight="semibold">
            InsightX - Success rate
          </Heading>
          <Link
            href={DOCUMENTATION_ROUTES[activeTab]}
            target="_blank"
            rel="noopener noreferrer"
            color="primary"
          >
            Documentation
          </Link>
        </Box>
        <Box gap="spacing.4">
          <Tabs size="medium" variant="bordered">
            <TabList>
              {INSIGHTX_TABS.map(({ name }) => (
                <TabItem key={name} value={name} onClick={() => handleTabChange(name)}>
                  {name}
                </TabItem>
              ))}
            </TabList>
            <TabPanel value={activeTab}>
              {INSIGHTX_TABS.map(({ name }) => name).includes(activeTab) ? (
                <>
                  <Box display="flex" flexWrap="wrap" gap="spacing.4" paddingY="spacing.6">
                    <Box display="flex" alignItems="center" justifyContent="flex-start">
                      <DateRangePicker selectedDateCallback={selectedDateCallback} />
                    </Box>
                    <Box display="flex" alignItems="flex-end">
                      <Button
                        marginRight="spacing.3"
                        icon={RefreshIcon}
                        color="primary"
                        variant="tertiary"
                        aria-label="Refresh"
                        onClick={() =>
                          handleRefreshClick({
                            refetch,
                            setGuestToken,
                            guestToken,
                            embedSupersetDashboard,
                          })
                        }
                      />
                    </Box>
                  </Box>

                  <SuperSetDashboardWrapper
                    ref={supersetRef}
                    height={dashboardHeights[activeTab] || '100%'}
                  />
                </>
              ) : (
                <EmptyState
                  text="InsightX"
                  retryHandler={retryHandler}
                  analyticsProperties={{
                    objectName: 'InsightX',
                    actionName: 'error',
                    screen: 'insightx/root',
                    ...(error ? { error: `Error while fetching guest token : ${error}` } : {}),
                  }}
                />
              )}
            </TabPanel>
          </Tabs>
        </Box>
      </Box>
    </InsightXErrorBoundary>
  );
};
export default InsightX;
