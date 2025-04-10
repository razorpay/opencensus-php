import React, { useEffect, useState, useRef, useCallback, useMemo } from 'react';
import { Box, Button, Heading, RefreshIcon, Link, DownloadIcon } from '@razorpay/blade/components';
import { embedDashboard } from '@superset-ui/embedded-sdk';
import moment from 'moment';
import { useParams } from 'react-router-dom';
import { useStore } from '@federated/apps/shell/commonStore';
import { useMobile } from 'common/hooks/useMobile';
import { analyticsTrack, getDeviceSource } from 'common/utils/analytics';
import { trackInsightsAnalytics } from 'merchant/views/Insights/utils/trackInsightsAnalytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { isMobileDevice } from 'merchant/components/Home/data';
import { InsightsErrorBoundary } from 'merchant/views/Insights/InsightsErrorBoundary';
import HeaderSection from 'merchant/views/Insights/components/HeaderSection';
import FilteredSection from 'merchant/views/Insights/components/FilteredSection';
import {
  SUPERSET_DASHBOARD_IDS,
  SUCCESS_RATE_TABS,
  DOCUMENTATION_ROUTES,
  SUPERSET_URL,
  dashboardHeights,
  CHECKOUT_TABS,
  INSIGHTS_DASHBOARDS,
} from 'merchant/views/Insights/constants';
import { useInsightsSplitzExperiments } from 'merchant/views/Insights/hooks/useInsightsSplitzExperiments';
import { useSupersetDashboard } from 'merchant/views/Insights/hooks/useSupersetDashboard';
import { SuperSetDashboardWrapper } from 'merchant/views/Insights/styled';
import { renderLoadingOrErrorState } from 'merchant/views/Insights/utils/renderUtils';
import { mobileBreakoints } from 'merchant/views/Transactions/v2/common/constants';
import { useTimeSpentOnScreen } from '@libs/shared-utils';

type DateRange = {
  from?: number;
  to?: number;
};

const getDefaultDateRange = () => {
  return {
    startOfDay: moment().clone().startOf('day').unix(),
    endOfDay: moment().clone().endOf('day').unix(),
  };
};

const Insights: React.FC = () => {
  const { timeSpent, registerTimeSpent, currentRoute } = useTimeSpentOnScreen();
  const { startOfDay, endOfDay } = getDefaultDateRange();
  const [date, setDate] = useState<DateRange>({});
  const isMobile = useMobile(mobileBreakoints);
  const supersetRef = useRef<HTMLElement>(null);
  const user = useStore((state) => state.session.user);
  const userEmail = user?.email;
  const activationStatus = user?.activation_status;
  const { data, refetch, isLoading, isError, error } = useSupersetDashboard();
  const [guestToken, setGuestToken] = useState<string | undefined>();
  const { activetab, insights_dashboard } = useParams();
  const { isInsightsCheckoutMagicXEnabled } = useInsightsSplitzExperiments();
  const { activeTab, Insights_Dashboard, currentDashboard, experiment_name } = useMemo(() => {
    let activeTab =
      [...SUCCESS_RATE_TABS, ...CHECKOUT_TABS].find((tab) => tab.link === activetab)?.name ||
      'Overview';
    if (activeTab === 'Magic' && isInsightsCheckoutMagicXEnabled) {
      activeTab = 'MagicX';
    }

    const currentDashboard = INSIGHTS_DASHBOARDS.find(
      (dashboard) => dashboard.link === insights_dashboard,
    );

    return {
      activeTab,
      Insights_Dashboard: currentDashboard?.name || 'Success Rate',
      currentDashboard,
      experiment_name: currentDashboard?.experiment_name,
    };
  }, [activetab, insights_dashboard]);

  const baseAnalyticsProps = useMemo(
    () => ({
      device_type: isMobileDevice(1020) ? 'mweb' : 'dweb',
      source: getDeviceSource(),
      url: window.location.href,
      page: location.pathname?.replace('/app/', ''),
      version: 'v2',
      tab: activeTab,
      exp_name: experiment_name,
      email_id: userEmail,
      activation_status: activationStatus,
      ...getCommonAnalyticsProperties(window.rzp_user, { addUserProperties: true }),
    }),
    [activeTab, experiment_name, user, activationStatus],
  );
  const trackAnalytics = useCallback(
    (actionName: string, additionalProps = {}) => {
      trackInsightsAnalytics(
        Insights_Dashboard,
        activeTab,
        insights_dashboard,
        baseAnalyticsProps,
        actionName,
        additionalProps,
      );
    },
    [Insights_Dashboard, insights_dashboard, activeTab, baseAnalyticsProps],
  );

  const selectedDateCallback = async (date: DateRange) => {
    const { data: updatedGuestToken } = await refetch();
    setGuestToken(updatedGuestToken);
    setDate(date);
    trackAnalytics('Date Range Selected', { from: date.from, to: date.to });
  };

  const getDashboardEmbedId = (tabName: string) => {
    return SUPERSET_DASHBOARD_IDS[tabName];
  };
  const embedSupersetDashboard = useCallback(
    (guestToken) => {
      if (!supersetRef.current || !guestToken || !data) return;
      const embedId = getDashboardEmbedId(activeTab);
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
            from_ts: date.from ?? startOfDay,
            to_ts: date.to ?? endOfDay,
          },
        },
      });
      trackAnalytics('Superset Dashboard Embedded', { embedId, from: date.from, to: date.to });
    },
    [activeTab, date.from, date.to, guestToken],
  );

  useEffect(() => {
    return () => {
      const { timeSpent: finalTime, route } = registerTimeSpent();
      trackAnalytics('Time spent on Route', {
        timeSpent: finalTime,
        route: route,
        activeTab,
      });
    };
  }, [activeTab, registerTimeSpent]);

  useEffect(() => {
    if (!guestToken && data) {
      setGuestToken(data);
    }
    trackAnalytics('Token Fetch Initiated');
  }, [data, guestToken]);
  useEffect(() => {
    if (guestToken && activeTab && supersetRef.current) {
      embedSupersetDashboard(guestToken);
    }
  }, [guestToken, activeTab, date, embedSupersetDashboard]);

  useEffect(() => {
    const fetchUpdatedGuestToken = async () => {
      const { data: updatedGuestToken } = await refetch();
      setGuestToken(updatedGuestToken);
    };

    fetchUpdatedGuestToken();
  }, [activeTab]);

  const retryHandler = async () => {
    if (isError) {
      const { data: updatedGuestToken } = await refetch();
      setGuestToken(updatedGuestToken);
    }
  };
  const getDashboardHeight = (tabName: string) => {
    return dashboardHeights[tabName] || '100vh';
  };

  const shouldShowEmptyState = !isLoading && !data;

  const renderState = renderLoadingOrErrorState(
    isLoading,
    isError,
    guestToken,
    error,
    activeTab,
    shouldShowEmptyState,
    retryHandler,
    Insights_Dashboard,
    baseAnalyticsProps,
  );
  if (renderState) {
    return renderState;
  }

  return (
    <InsightsErrorBoundary>
      <Box
        paddingTop="spacing.5"
        backgroundColor="surface.background.gray.intense"
        paddingX="spacing.7"
        marginRight={isMobile ? 'spacing.0' : 'spacing.2'}
      >
        <HeaderSection
          activeTab={activeTab}
          Insights_Dashboard={Insights_Dashboard}
          trackAnalytics={trackAnalytics}
        />
        <Box gap="spacing.4">
          <FilteredSection
            selectedDateCallback={selectedDateCallback}
            trackAnalytics={trackAnalytics}
            embedSupersetDashboard={embedSupersetDashboard}
            refetch={refetch}
            setGuestToken={setGuestToken}
          />
          <SuperSetDashboardWrapper
            id="superset-wrapper"
            ref={supersetRef}
            height={getDashboardHeight(activeTab)}
          />
        </Box>
      </Box>
    </InsightsErrorBoundary>
  );
};
export default Insights;
