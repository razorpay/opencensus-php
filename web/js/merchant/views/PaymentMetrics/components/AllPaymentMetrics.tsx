import React, { useEffect } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators, compose } from 'redux';
import { withRouter } from 'common/deprecated/withRouter';
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
import IndustryLevelOverallCr from './IndustryLevelOverallCr';
import MethodLevelTransactions from './MethodLevelTransactions';
import TotalGmv from './TotalGmv';
import MethodLevelGmv from './MethodLevelGmv';

const AllPaymentMetrics = ({
  paymentMetrics,
  user,
  updateInterval,
  updateDateRange,
  resetPaymentDashboard,
}: AllPaymentMetricsProps): React.ReactElement => {
  const { filters, interval } = paymentMetrics;
  const { startDate, endDate, preset } = filters;
  const category = user?.merchant?.category2 || '';

  useEffect(() => {
    return resetPaymentDashboard;
  }, []);

  return (
    <MetricsPanelContainer>
      <TopSection category={category} />
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
      <ChartCardContainer>
        <OverallCR />
        <MethodLevelCR startDate={startDate} endDate={endDate} interval={interval} />
        {category && <IndustryLevelOverallCr category={category} />}
        <MethodLevelTransactions startDate={startDate} endDate={endDate} interval={interval} />
        <TotalGmv />
        <MethodLevelGmv startDate={startDate} endDate={endDate} interval={interval} />
      </ChartCardContainer>
    </MetricsPanelContainer>
  );
};

export default compose<React.FunctionComponent>(
  withRouter,
  connect(
    ({ paymentMetrics, session }) => ({
      paymentMetrics,
      user: session.user,
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
