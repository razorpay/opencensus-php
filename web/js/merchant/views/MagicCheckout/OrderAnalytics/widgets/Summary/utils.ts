import { getAggregatedValue } from 'merchant/views/MagicCheckout/OrderAnalytics/utils';
import {
  TOOLTIP_HANDLERS,
  CHART_LABEL_MAPPING,
  CHART_COLORS,
} from 'merchant/views/MagicCheckout/OrderAnalytics/constants';
import { HELP_TEXTS } from './constants';

export const buildSummaryWidgetData = (metrics, widgets) => {
  const data = [] as Record<string, unknown>[];
  Object.keys(widgets).forEach((key) => {
    const { aggregation, unit } = widgets[key];
    const value = getAggregatedValue(metrics[key].values || metrics[key].value, unit, aggregation);
    const title = TOOLTIP_HANDLERS[key].label;
    if (key === CHART_LABEL_MAPPING.MAGIC_CUSTOMERS) {
      data.push({ value, title, helpText: HELP_TEXTS[key] });
    } else {
      data.push({ value, title, color: CHART_COLORS[key] });
    }
  });
  return data;
};
