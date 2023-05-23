import DashboardBanner from 'common/ui/DashboardBanner';
import React, { useEffect } from 'react';
import ShowWhen from 'merchant/components/ShowWhen';
import ZapierLaunchBanner from 'merchant/components/Announcements/ZapierBanner/ZapierBanner';
import { Downloads } from './features/Downloads';
import { OverView } from './features/Overview';
import { ReportSectionProps } from './types';
import { Tabs } from './components/Tabs';
import { connect } from 'react-redux';
import { getConfigs } from './api/overview';
import { getItem } from 'common/utils/localStorage';
import { pickProps } from 'common/utils/rzp-utils';
import { withRouter } from 'react-router-dom';
import {
  fetchReportsConfigsFailed,
  fetchReportsConfigsSuccess,
  handleOverviewLoading,
} from './redux/reducer';
import { getReportsDashboardConfig } from './configs/refDashboard.config';
import { showNotification } from 'merchant_common/reducers/notifications';
import { fetchAccounts } from 'merchant/reducers/marketplace/accounts';
import { trackReportsSection } from './configs/analytics.config';

// Features Of Reports
const NAV_LINKS = [
  {
    to: `/reports`,
    label: 'Overview',
    exact: true,
    component: withRouter(OverView),
  },
  {
    to: `/reports/downloads`,
    label: 'Downloads',
    exact: true,
    component: withRouter(Downloads),
  },
];

const mapStateToProps = ({ reportsCore, session }, { dashboardType }) => {
  const { allConfigs } = reportsCore[dashboardType].overview.reportConfigs;
  return {
    allReportConfigs: allConfigs.data,
    user: pickProps(session.user, ['current', 'international']),
    refDashboardConfig: getReportsDashboardConfig(dashboardType, session),
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
    allReportConfigs,
    refDashboardConfig: { basePath, headers, parseConfigs },
    handleOverviewLoading,
    fetchReportsConfigsSuccess,
    fetchReportsConfigsFailed,
    showNotification,
    fetchAccounts,
    dashboardType,
  }: ReportSectionProps): JSX.Element => {
    const handleAllConfigsFetch = async (validationCheck = true) => {
      try {
        if (validationCheck) {
          handleOverviewLoading({
            key: 'allConfigs',
            state: true,
          });
          const configs = await getConfigs(headers);
          if (configs?.data?.items) {
            fetchReportsConfigsSuccess({
              configs: parseConfigs(configs.data.items),
            });
          } else {
            trackReportsSection({
              actionName: 'Configs Fetch Failed',
              dashboardType,
            });
          }
        }
      } catch (err) {
        showNotification({
          type: 'error',
          message: 'Unable to fetch reports at this moment, please try again later.',
        });
        fetchReportsConfigsFailed();
      }
    };

    useEffect(() => {
      handleAllConfigsFetch(!allReportConfigs.length);
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
        <Tabs tabs={NAV_LINKS} basePath={basePath} />
      </div>
    );
  },
);
