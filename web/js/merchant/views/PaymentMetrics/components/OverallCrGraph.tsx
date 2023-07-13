import React, { useEffect } from 'react';
import moment from 'moment';
import { withRouter } from 'react-router-dom';
import { connect } from 'react-redux';
import { bindActionCreators, compose } from 'redux';
import Graph from './Graph';
import {
  getOverallCR,
  setSelectedMetric,
  selectedMetricsUpdateDateRange,
  selectedMetricsUpdateInterval,
} from 'merchant/reducers/paymentMetrics';
import {
  METHOD_LEVEL_CR,
  GRAPHS_DATA,
  momentDurationMap,
} from 'merchant/views/PaymentMetrics/constants';
import { Filter, GraphDataTokenTypes, OverallCrProps } from 'merchant/views/PaymentMetrics/types';
import {
  getBreakdownInterval,
  getTimeForSelectedGraphs,
} from 'merchant/views/PaymentMetrics/helpers';

const OverallCR = ({
  paymentMetrics,
  getOverallCR,
  selectedMetricsUpdateInterval,
  selectedMetricsUpdateDateRange,
  setSelectedMetric,
}: OverallCrProps): React.ReactElement => {
  const { interval, filters, chartData } = paymentMetrics;
  const { startDate, endDate } = filters || {};
  const { checkout_overall_cr } = chartData || {};
  const { datasets, error, isLoading } = checkout_overall_cr || {};

  const handleSelectedMetrics = /* istanbul ignore next */ (
    data: Array<Record<string, string>>,
  ) => {
    const selectedMetricTime: Filter = getTimeForSelectedGraphs(data?.[0]?.xLabel, interval);
    selectedMetricsUpdateDateRange(selectedMetricTime);
    selectedMetricsUpdateInterval(
      getBreakdownInterval(selectedMetricTime.startDate, selectedMetricTime.endDate),
    );
    setSelectedMetric(METHOD_LEVEL_CR);
  };

  useEffect(() => {
    getOverallCR({
      lte: moment(endDate).unix(),
      gte: moment(startDate).unix(),
      breakdown: interval,
    });
  }, [startDate, endDate, interval]);

  const isNoData = !isLoading && !datasets.length;

  const graphData = GRAPHS_DATA.OVERALL_CR as GraphDataTokenTypes;

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
          getOverallCR,
          selectedMetricsUpdateDateRange,
          selectedMetricsUpdateInterval,
          setSelectedMetric,
        },
        dispatch,
      );
    },
  ),
)(OverallCR);
