import React, { useEffect } from 'react';
import moment from 'moment';
import { withRouter } from 'common/deprecated/withRouter';
import { connect } from 'react-redux';
import { bindActionCreators, compose } from 'redux';
import Graph from './Graph';
import {
  getOverallGmv,
  setSelectedMetric,
  selectedMetricsUpdateDateRange,
  selectedMetricsUpdateInterval,
} from 'merchant/reducers/paymentMetrics';
import {
  GRAPHS_DATA,
  momentDurationMap,
  CHECKOUT_METHOD_LEVEL_GMV,
} from 'merchant/views/PaymentMetrics/constants';
import { Filter, GraphDataTokenTypes, TotalGmvProps } from 'merchant/views/PaymentMetrics/types';
import {
  getBreakdownInterval,
  getTimeForSelectedGraphs,
} from 'merchant/views/PaymentMetrics/helpers';

const TotalGmv = ({
  paymentMetrics,
  getOverallGmv,
  selectedMetricsUpdateInterval,
  selectedMetricsUpdateDateRange,
  setSelectedMetric,
}: TotalGmvProps): React.ReactElement => {
  const { interval, filters, chartData } = paymentMetrics;
  const { startDate, endDate } = filters || {};
  const { checkout_gmv } = chartData || {};
  const { datasets, error, isLoading } = checkout_gmv || {};

  const handleSelectedMetrics = /* istanbul ignore next */ (
    data: Array<Record<string, string>>,
  ) => {
    const selectedMetricTime: Filter = getTimeForSelectedGraphs(data?.[0]?.xLabel, interval);
    selectedMetricsUpdateDateRange(selectedMetricTime);
    selectedMetricsUpdateInterval(
      getBreakdownInterval(selectedMetricTime.startDate, selectedMetricTime.endDate),
    );
    setSelectedMetric(CHECKOUT_METHOD_LEVEL_GMV);
  };

  useEffect(() => {
    getOverallGmv({
      lte: moment(endDate).unix(),
      gte: moment(startDate).unix(),
      breakdown: interval,
    });
  }, [startDate, endDate, interval]);

  const isNoData = !isLoading && !datasets.length;

  const graphData = GRAPHS_DATA.CHECKOUT_GMV as GraphDataTokenTypes;

  return (
    <Graph
      title={graphData.title}
      description={graphData.description}
      data={{ datasets }}
      interval={interval}
      xLabel={`${graphData.xLabel} ${momentDurationMap[interval]}`}
      yLabel={graphData.yLabel}
      xAxisID={graphData.xAxisID}
      yAxisID={graphData.yAxisID}
      isLoading={isLoading}
      noData={isNoData}
      error={error}
      btnAction={handleSelectedMetrics}
      customUnit="L"
    />
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
          getOverallGmv,
          selectedMetricsUpdateDateRange,
          selectedMetricsUpdateInterval,
          setSelectedMetric,
        },
        dispatch,
      );
    },
  ),
)(TotalGmv);
