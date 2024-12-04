import React from 'react';
import { connect } from 'react-redux';

import {
  fetchRecentlyUsedConfigsFailed,
  fetchRecentlyUsedConfigsSuccess,
  handleOverviewLoading,
} from 'merchant_common/views/Reports/redux/reducer';
import { useDashboardType } from 'merchant_common/views/Reports/contexts/ReportsContext';
import { getReportsDashboardConfig } from 'merchant_common/views/Reports/configs/refDashboard.config';
import { OverviewSection } from './OverView';
import { showNotification } from 'merchant_common/reducers/notifications';
import { openModal } from 'merchant_common/reducers/modals';
import { useI18Service } from 'common/i18';

const mapStateToProps = ({ reportsCore, session }, { dashboardType, i18 }) => {
  const { allConfigs, recentConfigs } = reportsCore[dashboardType].overview.reportConfigs;
  const refDashboardConfig = getReportsDashboardConfig(
    dashboardType,
    session,
    undefined,
    undefined,
    i18,
  );

  return {
    refDashboardConfig,
    allReportConfigs: allConfigs.data,
    recentlyUsedReportConfigs: recentConfigs.data,
    isAllConfigLoaded: !allConfigs.loading && !allConfigs.error,
    isRecentlyUsedConfigLoaded: !recentConfigs.loading && !recentConfigs.error,
  };
};

export const mapDispatchToProps = (dispatch, { dashboardType }) => {
  return {
    openModal: (modal) => dispatch(openModal(modal)),
    fetchRecentlyUsedConfigsFailed: (payload) =>
      dispatch(fetchRecentlyUsedConfigsFailed({ ...payload, dashboardType })),
    fetchRecentlyUsedConfigsSuccess: (payload) =>
      dispatch(fetchRecentlyUsedConfigsSuccess({ ...payload, dashboardType })),
    handleOverviewLoading: (payload) =>
      dispatch(handleOverviewLoading({ ...payload, dashboardType })),
    showNotification: (payload) => dispatch(showNotification(payload)),
  };
};

const OverviewComponent = connect(mapStateToProps, mapDispatchToProps)(OverviewSection);

export const OverView = (props) => {
  const dashboardType = useDashboardType();
  const i18 = useI18Service();

  return <OverviewComponent dashboardType={dashboardType} i18={i18} {...props} />;
};
