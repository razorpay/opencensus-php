import React, { Fragment, useEffect } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators, compose } from 'redux';
import { withRouter } from 'react-router';
import { ChartCardContainer, MetricsPanelContainer, TopBar } from './styled';
import PaymentMetricsFilter from './PaymentMetricsFilter';
import GraphInterval from './GraphInterval';
import {
  updateInterval,
  updateDateRange,
  resetPaymentDashboard,
} from 'merchant/reducers/paymentMetrics';
import { AllPaymentMetricsProps } from 'merchant/views/PaymentMetrics/types';
import TopSection from './TopSection';
import OverallCR from './OverallCrGraph';
import MethodLevelCR from './MethodLevelCr';

const AllPaymentMetrics = ({
  paymentMetrics,
  updateInterval,
  updateDateRange,
  resetPaymentDashboard,
}: AllPaymentMetricsProps): React.ReactElement => {
  const { filters, interval } = paymentMetrics;
  const { startDate, endDate, preset } = filters;

  useEffect(() => {
    return resetPaymentDashboard;
  }, []);

  return (
    <Fragment>
      <TopBar className="payment-metrics">
        <PaymentMetricsFilter
          updateInterval={updateInterval}
          startDate={startDate}
          endDate={endDate}
          preset={preset}
          updateDateRange={updateDateRange}
        />
        <GraphInterval
          selected={interval}
          onChange={updateInterval}
          startDate={startDate}
          endDate={endDate}
          isPaymentMetrics={true}
        />
      </TopBar>
      <MetricsPanelContainer>
        <TopSection />
        <ChartCardContainer>
          <OverallCR />
          <MethodLevelCR startDate={startDate} endDate={endDate} interval={interval} />
        </ChartCardContainer>
      </MetricsPanelContainer>
    </Fragment>
  );
};

export default compose<React.FunctionComponent>(
  withRouter,
  connect(
    ({ paymentMetrics }) => ({
      paymentMetrics,
    }),
    (dispatch) => {
      return bindActionCreators(
        {
          updateInterval,
          updateDateRange,
          resetPaymentDashboard,
        },
        dispatch,
      );
    },
  ),
)(AllPaymentMetrics);
