import {
  CHART_LABEL_MAPPING,
  METRIC_TYPE,
  AGGERGATE_OPERATION,
} from 'merchant/views/MagicCheckout/OrderAnalytics/constants';

export const SUMMARY_WIDGETS = {
  [CHART_LABEL_MAPPING.TOTAL_SALES]: {
    aggregation: AGGERGATE_OPERATION.SUM,
    unit: METRIC_TYPE.CURRENCY,
  },
  [CHART_LABEL_MAPPING.TOTAL_ORDERS]: {
    aggregation: AGGERGATE_OPERATION.SUM,
    unit: METRIC_TYPE.NUMBER,
  },
  [CHART_LABEL_MAPPING.AVG_ORDER_VALUE]: {
    aggregation: AGGERGATE_OPERATION.AVERAGE,
    unit: METRIC_TYPE.CURRENCY,
  },
};

export const CR_SUMMARY_WIDGETS = {
  [CHART_LABEL_MAPPING.CONVERSION_RATE]: {
    aggregation: AGGERGATE_OPERATION.AVERAGE,
    unit: METRIC_TYPE.PERCENTAGE,
  },
  [CHART_LABEL_MAPPING.MAGIC_CUSTOMERS]: {
    unit: METRIC_TYPE.PERCENTAGE,
  },
};

export const HELP_TEXTS = {
  [CHART_LABEL_MAPPING.MAGIC_CUSTOMERS]:
    'Number of "authorised" payments, which were created in the selected time range.',
};
