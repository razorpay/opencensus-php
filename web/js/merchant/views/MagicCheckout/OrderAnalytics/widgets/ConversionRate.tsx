import React from 'react';
import {
  CHART_LABEL_MAPPING,
  METRIC_TYPE,
  AGGERGATE_OPERATION,
} from 'merchant/views/MagicCheckout/OrderAnalytics/constants';
import GraphWidget from 'merchant/views/MagicCheckout/OrderAnalytics/common/GraphWidget';
import { customTooltip } from 'merchant/views/MagicCheckout/OrderAnalytics/utils';

const CHART_OPTIONS = {
  scales: {
    yAxes: [
      {
        ticks: {
          callback: (value) => {
            return `${value}%`;
          },
        },
      },
    ],
  },
  tooltips: {
    custom: function custom(tooltipModel) {
      customTooltip(tooltipModel, this, CHART_LABEL_MAPPING.CONVERSION_RATE);
    },
  },
};

const ConversionRate = ({ isFetching, data }) => {
  return (
    <GraphWidget
      data={data}
      isFetching={isFetching}
      aggregationUnit={METRIC_TYPE.PERCENTAGE}
      aggregationType={AGGERGATE_OPERATION.AVERAGE}
      label={CHART_LABEL_MAPPING.CONVERSION_RATE}
      customOptions={CHART_OPTIONS}
      className="conversion-chart"
    />
  );
};

export default ConversionRate;
