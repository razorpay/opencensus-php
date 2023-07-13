import React, { useEffect, Suspense, lazy } from 'react';

import { METHOD_LEVEL_CR, GRAPHS_DATA } from 'merchant/views/PaymentMetrics/constants';
import { connect } from 'react-redux';
import { bindActionCreators, compose } from 'redux';
import { withRouter } from 'react-router';
import PaymentMetricsFilter from './PaymentMetricsFilter';
import {
  SelectedMetricsContainer,
  MetricsTopBar,
  BreadcrumbArea,
  SelectedMetric,
  TopBar,
} from './styled';
import GraphInterval from './GraphInterval';
import {
  setSelectedMetric,
  selectedMetricsUpdateDateRange,
  selectedMetricsUpdateInterval,
} from 'merchant/reducers/paymentMetrics';
import { SelectedMetricPanelProps } from 'merchant/views/PaymentMetrics/types';

const MethodLevelCR = lazy(() => import(/* webpackChunkName: "MethodLevelCR" */ './MethodLevelCr'));

const SelectedMetricPanel = ({
  handleBack,
  paymentMetrics,
  selectedMetric,
  selectedMetricsUpdateInterval,
  selectedMetricsUpdateDateRange,
}: SelectedMetricPanelProps): React.ReactElement => {
  const { selectedMetricFilters, selectedMetricInterval } = paymentMetrics;
  const { startDate, endDate, preset } = selectedMetricFilters || {};

  useEffect(() => {
    window.scrollTo({
      top: 0,
      left: 0,
      behavior: 'smooth',
    });
  }, []);

  const handleBreakdown = (breakdown: string) => {
    selectedMetricsUpdateInterval(breakdown);
  };

  return (
    <SelectedMetricsContainer>
      <MetricsTopBar>
        <i className="i i-arrow-back" onClick={handleBack} />
        <BreadcrumbArea>
          CR / <SelectedMetric>{GRAPHS_DATA[selectedMetric].name}</SelectedMetric>
        </BreadcrumbArea>
      </MetricsTopBar>
      <TopBar className="payment-metrics">
        <PaymentMetricsFilter
          updateInterval={selectedMetricsUpdateInterval}
          startDate={startDate}
          endDate={endDate}
          preset={preset}
          updateDateRange={selectedMetricsUpdateDateRange}
        />
        <GraphInterval
          selected={selectedMetricInterval}
          onChange={handleBreakdown}
          startDate={startDate}
          endDate={endDate}
          isPaymentMetrics={true}
        />
      </TopBar>
      <Suspense fallback={null}>
        {selectedMetric === METHOD_LEVEL_CR && (
          <MethodLevelCR
            startDate={startDate}
            endDate={endDate}
            interval={selectedMetricInterval}
          />
        )}
      </Suspense>
    </SelectedMetricsContainer>
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
          selectedMetricsUpdateInterval,
          selectedMetricsUpdateDateRange,
        },
        dispatch,
      );
    },
  ),
)(SelectedMetricPanel);
