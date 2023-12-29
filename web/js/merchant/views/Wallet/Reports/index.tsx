import React from 'react';
import { connect } from 'react-redux';

import { walletReportHeaders } from 'merchant/views/Wallet/constants';
import { ReportsProps } from 'merchant/views/Wallet/types';
import { showNotification } from 'merchant_common/reducers/notifications';
import { ReportContextProvider } from 'merchant_common/views/Reports/contexts/ReportsContext';
import { Downloads } from 'merchant_common/views/Reports/features/Downloads';
import { useFetchReportingConfig } from 'merchant_common/views/Reports/hooks/useFetchReportingConfig';
import {
  fetchReportsConfigsFailed,
  fetchReportsConfigsSuccess,
  handleOverviewLoading,
} from 'merchant_common/views/Reports/redux/reducer';

const WalletReports = ({
  handleOverviewLoading,
  fetchReportsConfigsSuccess,
  fetchReportsConfigsFailed,
}: ReportsProps): JSX.Element => {
  useFetchReportingConfig({
    handleOverviewLoading,
    headers: walletReportHeaders,
    fetchReportsConfigsSuccess,
    fetchReportsConfigsFailed,
    dashboardType: 'merchant',
  });

  return (
    <ReportContextProvider dashboardType="merchant">
      <Downloads headers={walletReportHeaders} />
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

export const Reports = connect(null, mapDispatchToProps)(WalletReports);
