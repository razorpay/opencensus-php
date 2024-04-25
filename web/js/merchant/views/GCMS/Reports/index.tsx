import React from 'react';
import { connect } from 'react-redux';

import { gcmsReportHeaders } from 'merchant/views/GCMS/Reports/constants';
import { Downloads } from 'merchant/views/GCMS/Reports/features/Downloads';
import { ReportsProps } from 'merchant/views/Wallet/types';
import { showNotification } from 'merchant_common/reducers/notifications';
import { ReportContextProvider } from 'merchant_common/views/Reports/contexts/ReportsContext';
import { useFetchReportingConfig } from 'merchant_common/views/Reports/hooks/useFetchReportingConfig';
import {
  fetchReportsConfigsFailed,
  fetchReportsConfigsSuccess,
  handleOverviewLoading,
} from 'merchant_common/views/Reports/redux/reducer';

const GcmsReports = ({
  handleOverviewLoading,
  fetchReportsConfigsSuccess,
  fetchReportsConfigsFailed,
}: ReportsProps): JSX.Element => {
  useFetchReportingConfig({
    handleOverviewLoading,
    headers: gcmsReportHeaders,
    fetchReportsConfigsSuccess,
    fetchReportsConfigsFailed,
    dashboardType: 'merchant',
  });

  return (
    <ReportContextProvider dashboardType="merchant">
      <Downloads headers={gcmsReportHeaders} />
    </ReportContextProvider>
  );
};

const mapDispatchToProps = (dispatch) => {
  return {
    fetchReportsConfigsSuccess: (payload) =>
      dispatch(fetchReportsConfigsSuccess({ ...payload, dashboardType: 'merchant' })),
    fetchReportsConfigsFailed: (payload) =>
      dispatch(fetchReportsConfigsFailed({ ...payload, dashboardType: 'merchant' })),
    handleOverviewLoading: (payload) =>
      dispatch(handleOverviewLoading({ ...payload, dashboardType: 'merchant' })),
    showNotification: (payload) => dispatch(showNotification(payload)),
  };
};

export default connect(null, mapDispatchToProps)(GcmsReports);
