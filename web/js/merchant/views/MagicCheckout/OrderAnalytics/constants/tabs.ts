import { CHART_LABEL_MAPPING } from '.';
import { RCOD_APP_NAME, SOPC_APP_NAME } from 'merchant/views/MagicCheckout/common/constants';

export const WIDTHS = {
  FULL: 'full-width',
  TWO_THIRDS: 'two-thirds-width',
  HALF: 'half-width',
  ONE_THIRD: 'one-thirds-width',
};

export const OVERVIEW_LAYOUT = [
  {
    chart: CHART_LABEL_MAPPING.TOTAL_SALES,
    width: WIDTHS.HALF,
  },
  {
    chart: CHART_LABEL_MAPPING.TOTAL_ORDERS,
    width: WIDTHS.HALF,
  },
  {
    chart: CHART_LABEL_MAPPING.AVG_ORDER_VALUE,
    width: WIDTHS.FULL,
  },
  {
    chart: CHART_LABEL_MAPPING.SALES_SPLIT,
    width: WIDTHS.HALF,
  },
  {
    chart: CHART_LABEL_MAPPING.ORDER_SPLIT,
    width: WIDTHS.HALF,
  },
  {
    chart: CHART_LABEL_MAPPING.TRAFFIC_UTM_PARAMS,
    width: WIDTHS.FULL,
  },
  {
    chart: CHART_LABEL_MAPPING.TOP_SELLING_PRODUCTS,
    width: WIDTHS.FULL,
  },
];

export const CONVERSION_LAYOUT = [
  {
    chart: CHART_LABEL_MAPPING.CONVERSION_FUNNEL,
    width: WIDTHS.FULL,
  },
  {
    chart: CHART_LABEL_MAPPING.CONVERSION_RATE,
    width: WIDTHS.FULL,
  },
];

export const TABS = {
  OVERVIEW: {
    label: 'Overview',
    layout: OVERVIEW_LAYOUT,
    condition: (_user, dashboardView) =>
      dashboardView !== SOPC_APP_NAME && dashboardView !== RCOD_APP_NAME,
  },
  CONVERSION: {
    label: 'Conversion',
    condition: (_user) => _user.isMagicOrderAnalyticsCREnabled,
    layout: CONVERSION_LAYOUT,
  },
};
