import React from 'react';
// eslint-disable-next-line import/no-cycle
import { CHART_LABEL_MAPPING } from 'merchant/views/MagicCheckout/OrderAnalytics/constants';
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
      customTooltip(tooltipModel, this, CHART_LABEL_MAPPING.SALES_SPLIT);
    },
  },
};

const SalesSplitV2 = ({ isFetching, data }) => {
  return (
    <GraphWidget
      data={data}
      isFetching={isFetching}
      label={CHART_LABEL_MAPPING.SALES_SPLIT}
      customOptions={CHART_OPTIONS}
      isChartStacked={true}
    />
  );
};

export default SalesSplitV2;
