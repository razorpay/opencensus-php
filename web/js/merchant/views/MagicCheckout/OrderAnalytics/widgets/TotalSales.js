import React from 'react';
// eslint-disable-next-line import/no-cycle
import {
  CHART_LABEL_MAPPING,
  METRIC_TYPE,
} from 'merchant/views/MagicCheckout/OrderAnalytics/constants';
import GraphWidget from 'merchant/views/MagicCheckout/OrderAnalytics/common/GraphWidget';
import { getCurrencyValue, customTooltip } from 'merchant/views/MagicCheckout/OrderAnalytics/utils';

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
      customTooltip(tooltipModel, this, CHART_LABEL_MAPPING.TOTAL_SALES);
    },
  },
};
const TotalSales = ({ isFetching, data }) => {
  return (
    <GraphWidget
      data={data}
      isFetching={isFetching}
      label={CHART_LABEL_MAPPING.TOTAL_SALES}
      aggregationUnit={METRIC_TYPE.CURRENCY}
      customOptions={CHART_OPTIONS}
    />
  );
};

export default TotalSales;
