import React from 'react';
// eslint-disable-next-line import/no-cycle
import {
  CHART_LABEL_MAPPING,
  METRIC_TYPE,
} from 'merchant/views/MagicCheckout/OrderAnalytics/constants';
import GraphWidget from 'merchant/views/MagicCheckout/OrderAnalytics/common/GraphWidget';
import { customTooltip, getNumericValue } from 'merchant/views/MagicCheckout/OrderAnalytics/utils';

const CHART_OPTIONS = {
  scales: {
    yAxes: [
      {
        ticks: {
          callback: (value) => {
            return getNumericValue(value);
          },
        },
      },
    ],
  },
  tooltips: {
    custom: function custom(tooltipModel) {
      customTooltip(tooltipModel, this, CHART_LABEL_MAPPING.TOTAL_ORDERS);
    },
  },
};

const TotalOrders = ({ isFetching, data }) => {
  return (
    <GraphWidget
      data={data}
      isFetching={isFetching}
      label={CHART_LABEL_MAPPING.TOTAL_ORDERS}
      aggregationUnit={METRIC_TYPE.NUMBER}
      customOptions={CHART_OPTIONS}
    />
  );
};

export default TotalOrders;
