import React, { useEffect } from 'react';
import moment from 'moment';
import { withRouter } from 'common/deprecated/withRouter';
import { connect } from 'react-redux';
import { bindActionCreators, compose } from 'redux';
import Graph from './Graph';
import { getMethodLevelGmv } from 'merchant/reducers/paymentMetrics';
import { GRAPHS_DATA, momentDurationMap } from 'merchant/views/PaymentMetrics/constants';
import { GraphDataTokenTypes, MethodLevelGmvProps } from 'merchant/views/PaymentMetrics/types';

const MethodLevelGmv = ({
  paymentMetrics,
  getMethodLevelGmv,
  interval,
  startDate,
  endDate,
}: MethodLevelGmvProps): React.ReactElement => {
  const { chartData } = paymentMetrics || {};
  const { checkout_method_level_gmv } = chartData || {};
  const { isLoading, datasets, error } = checkout_method_level_gmv || {};

  useEffect(() => {
    getMethodLevelGmv({
      lte: moment(endDate).unix(),
      gte: moment(startDate).unix(),
      breakdown: interval,
    });
  }, [startDate, endDate, interval]);

  const isNoData = !isLoading && !datasets.length;
  const graphData = GRAPHS_DATA.CHECKOUT_METHOD_LEVEL_GMV as GraphDataTokenTypes;
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
      customUnit="L"
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
          getMethodLevelGmv,
        },
        dispatch,
      );
    },
  ),
)(MethodLevelGmv);
