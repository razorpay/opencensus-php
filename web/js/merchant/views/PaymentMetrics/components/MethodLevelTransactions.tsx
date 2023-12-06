import React, { useEffect } from 'react';
import moment from 'moment';
import { withRouter } from 'common/deprecated/withRouter';
import { connect } from 'react-redux';
import { bindActionCreators, compose } from 'redux';
import Graph from './Graph';
import { getMethodLevelTransactions } from 'merchant/reducers/paymentMetrics';
import { GRAPHS_DATA, momentDurationMap } from 'merchant/views/PaymentMetrics/constants';
import {
  GraphDataTokenTypes,
  MethodLevelTransactionsProps,
} from 'merchant/views/PaymentMetrics/types';

const MethodLevelTransactions = ({
  paymentMetrics,
  getMethodLevelTransactions,
  interval,
  startDate,
  endDate,
}: MethodLevelTransactionsProps): React.ReactElement => {
  const { chartData } = paymentMetrics || {};
  const { method_level_transactions } = chartData || {};
  const { isLoading, datasets, error } = method_level_transactions || {};

  useEffect(() => {
    getMethodLevelTransactions({
      lte: moment(endDate).unix(),
      gte: moment(startDate).unix(),
      breakdown: interval,
    });
  }, [startDate, endDate, interval]);

  const isNoData = !isLoading && !datasets.length;
  const graphData = GRAPHS_DATA.METHOD_LEVEL_TRANSACTIONS as GraphDataTokenTypes;
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
      customUnit="k"
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
          getMethodLevelTransactions,
        },
        dispatch,
      );
    },
  ),
)(MethodLevelTransactions);
