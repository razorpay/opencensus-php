import React from 'react';
// eslint-disable-next-line import/no-cycle
import {
  CHART_LABEL_MAPPING,
  METRIC_TYPE,
  AGGERGATE_OPERATION,
} from 'merchant/views/MagicCheckout/OrderAnalytics/constants';
import GraphWidget from 'merchant/views/MagicCheckout/OrderAnalytics/common/GraphWidget';
import { customTooltip, getCurrencyValue } from 'merchant/views/MagicCheckout/OrderAnalytics/utils';

const CHART_OPTIONS = {
  scales: {
    yAxes: [
      {
        ticks: {
          callback: (value) => {
            return getCurrencyValue(value);
          },
        },
      },
    ],
  },
  tooltips: {
    custom: function custom(tooltipModel) {
      customTooltip(tooltipModel, this, CHART_LABEL_MAPPING.AVG_ORDER_VALUE);
    },
  },
};

const AverageOrderValue = ({ isFetching, data }) => {
  return (
    <GraphWidget
      data={data}
      isFetching={isFetching}
      label={CHART_LABEL_MAPPING.AVG_ORDER_VALUE}
      aggregationUnit={METRIC_TYPE.CURRENCY}
      aggregationType={AGGERGATE_OPERATION.AVERAGE}
      customOptions={CHART_OPTIONS}
    />
  );
};

export default AverageOrderValue;
