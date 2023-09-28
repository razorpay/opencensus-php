import React, { useEffect } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators, compose } from 'redux';
import { withRouter } from 'common/deprecated/withRouter';
import AllPaymentMetrics from './components/AllPaymentMetrics';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import { MetricsDashboard } from './components/styled';
import './payment-metrics.styl';
import { setSelectedMetric } from 'merchant/reducers/paymentMetrics';
import SelectedMetricPanel from './components/SelectedMetricPanel';
import { PaymentMetricsReducerProps } from './types';

const PaymentMetrics = ({
  paymentMetrics,
  setSelectedMetric,
}: {
  paymentMetrics: PaymentMetricsReducerProps;
  setSelectedMetric: (arg0: string) => any;
}): React.ReactElement => {
  const { selectedMetric } = paymentMetrics || {};

  const resetMetricsPage = () => {
    setSelectedMetric('');
  };

  useEffect(() => {
    return () => {
      resetMetricsPage();
    };
  }, []);

  return (
    <ErrorBoundary resetOnProps>
      <MetricsDashboard>
        {selectedMetric ? (
          <SelectedMetricPanel handleBack={resetMetricsPage} selectedMetric={selectedMetric} />
        ) : (
          <AllPaymentMetrics />
        )}
      </MetricsDashboard>
    </ErrorBoundary>
  );
};

export default compose<any>(
  withRouter,
  connect(
    ({ paymentMetrics }) => ({
      paymentMetrics,
    }),
    (dispatch) => {
      return bindActionCreators(
        {
          setSelectedMetric,
        },
        dispatch,
      );
    },
  ),
)(PaymentMetrics);
