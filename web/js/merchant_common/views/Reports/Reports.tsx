import React, { useEffect, useMemo } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'common/deprecated/withRouter';
import DashboardBanner from 'common/ui/DashboardBanner';
import { getItem } from 'common/utils/localStorage';
import { pickProps } from 'common/utils/rzp-utils';
import ZapierLaunchBanner from 'merchant/components/Announcements/ZapierBanner/ZapierBanner';
import ShowWhen from 'merchant/components/ShowWhen';
import { fetchAccounts } from 'merchant/reducers/marketplace/accounts';
import { showNotification } from 'merchant_common/reducers/notifications';

import { Tabs } from 'merchant_common/views/Reports/components/Tabs';
import { getReportsDashboardConfig } from 'merchant_common/views/Reports/configs/refDashboard.config';
import { Downloads } from 'merchant_common/views/Reports/features/Downloads';
import { OverView } from 'merchant_common/views/Reports/features/Overview';
import { Schedules } from 'merchant_common/views/Reports/features/Schedules';
import { useReportsSplitzExperiments } from 'merchant_common/views/Reports/hooks';
import { useFetchReportingConfig } from 'merchant_common/views/Reports/hooks/useFetchReportingConfig';
import {
  fetchReportsConfigsFailed,
  fetchReportsConfigsSuccess,
  handleOverviewLoading,
} from './redux/reducer';
import { ReportSectionProps } from './types';

// Features Of Reports
const getReportsFeatures = (isSchedulesEnabled: boolean) => {
  const features = [
    {
      to: '/reports',
      label: 'Overview',
      exact: true,
      component: withRouter(OverView),
    },
    {
      to: '/reports/downloads',
      label: 'Downloads',
      exact: true,
      component: withRouter(Downloads),
    },
  ];

  if (isSchedulesEnabled) {
    features.push({
      to: '/reports/schedules',
      label: 'Schedules',
      exact: true,
      component: withRouter(Schedules),
    });
  }

  return features;
};

const mapStateToProps = ({ reportsCore, session }, { dashboardType, i18 }) => {
  const { allConfigs } = reportsCore[dashboardType].overview.reportConfigs;
  return {
    allReportConfigs: allConfigs.data,
    user: pickProps(session.user, ['current', 'international']),
    refDashboardConfig: getReportsDashboardConfig(
      dashboardType,
      session,
      undefined,
      undefined,
      i18,
    ),
  };
};

const mapDispatchToProps = (dispatch, { dashboardType }) => ({
  fetchReportsConfigsSuccess: (payload) =>
    dispatch(fetchReportsConfigsSuccess({ ...payload, dashboardType })),
  fetchReportsConfigsFailed: (payload) =>
    dispatch(fetchReportsConfigsFailed({ ...payload, dashboardType })),
  handleOverviewLoading: (payload) =>
    dispatch(handleOverviewLoading({ ...payload, dashboardType })),
  showNotification: (payload) => dispatch(showNotification(payload)),
  fetchAccounts: () => dispatch(fetchAccounts()),
});

export const ReportsSection = connect(
  mapStateToProps,
  mapDispatchToProps,
)(
  ({
    user,
    refDashboardConfig: { basePath, headers, parseConfigs },
    handleOverviewLoading,
    fetchReportsConfigsSuccess,
    fetchReportsConfigsFailed,
    fetchAccounts,
    dashboardType,
  }: ReportSectionProps): JSX.Element => {
    useFetchReportingConfig({
      handleOverviewLoading,
      headers,
      fetchReportsConfigsSuccess,
      fetchReportsConfigsFailed,
      dashboardType,
      parseConfigs,
    });
    const { isSchedulesEnabled } = useReportsSplitzExperiments();
    const features = useMemo(() => getReportsFeatures(isSchedulesEnabled), []);

    useEffect(() => {
      fetchAccounts();
    }, []);

    return (
      <div>
        <ShowWhen
          additionalCondition={(currentUser) =>
            currentUser.isPartOfZapierIntegrationExperiment &&
            !getItem(`zapier-integration-banner-${user.current}`)
          }
        >
          <ZapierLaunchBanner
            fromWhere="reports"
            bannerKey={`zapier-integration-banner-${user.current}`}
          />
        </ShowWhen>
        <DashboardBanner />
        <Tabs tabs={features} basePath={basePath} />
      </div>
    );
  },
);
