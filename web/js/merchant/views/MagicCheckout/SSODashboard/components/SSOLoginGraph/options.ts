import { CHART_LABEL_MAPPING } from 'merchant/views/MagicCheckout/OrderAnalytics/constants';
import { customTooltip, getNumericValue } from 'merchant/views/MagicCheckout/OrderAnalytics/utils';

export const CHART_OPTIONS = {
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
      customTooltip(tooltipModel, this, CHART_LABEL_MAPPING.LOGIN_TREND);
    },
  },
};
