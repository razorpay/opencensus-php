import React, { useEffect } from 'react';
import moment from 'moment';
import { withRouter } from 'react-router-dom';
import { connect } from 'react-redux';
import { bindActionCreators, compose } from 'redux';
import Graph from './Graph';
import { getIndustryOverallCR } from 'merchant/reducers/paymentMetrics';
import { GRAPHS_DATA, momentDurationMap } from 'merchant/views/PaymentMetrics/constants';
import { GraphDataTokenTypes, OverallCrProps } from 'merchant/views/PaymentMetrics/types';

const IndustryOverallCR = ({
  paymentMetrics,
  category,
  getIndustryOverallCR,
}: OverallCrProps): React.ReactElement => {
  const { interval, filters, chartData } = paymentMetrics;
  const { startDate, endDate } = filters || {};
  const { checkout_industry_level_cr } = chartData || {};
  const { datasets, error, isLoading } = checkout_industry_level_cr || {};

  useEffect(() => {
    getIndustryOverallCR({
      lte: moment(endDate).unix(),
      gte: moment(startDate).unix(),
      breakdown: interval,
      category,
    });
  }, [startDate, endDate, interval]);

  const isNoData = !isLoading && !datasets.length;

  const graphData = GRAPHS_DATA.INDUSTRY_OVERALL_CR as GraphDataTokenTypes;

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
    />
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
          getIndustryOverallCR,
        },
        dispatch,
      );
    },
  ),
)(IndustryOverallCR);
