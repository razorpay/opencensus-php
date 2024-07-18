import Reports from 'merchant/views/MagicCheckout/OrderAnalytics/Reports/components';

import { RCOD_APP_NAME, SOPC_APP_NAME } from 'merchant/views/MagicCheckout/common/constants';

import { Tabs, Layout } from 'merchant/views/MagicCheckout/ReportsAndAnalyticsV2/types';

export const CHART_LABEL_MAPPING = {
  SUMMARY: 'summary',
  TOTAL_SALES: 'total_sales',
  TOTAL_ORDERS: 'total_orders_placed',
  AVG_ORDER_VALUE: 'average_order_value',
  ORDER_SALES_SPLIT: 'prepay_vs_cod',
  TRAFFIC_UTM_PARAMS: 'traffic_utm',
  TOP_SELLING_PRODUCTS: 'top_selling_products',
  SALES_SPLIT: 'prepay_vs_cod_total_sales',
  ORDER_SPLIT: 'prepay_vs_cod_total_orders_placed',
  CONVERSION_FUNNEL: 'conversion_funnel',
  CONVERSION_RATE: 'conversion_rate',
  MAGIC_CUSTOMERS: 'magic_customers',
  TOTAL_ORDERS_COD: 'Total Orders - COD',
  TOTAL_ORDERS_PREPAID: 'Total Orders - Prepaid',
  TOTAL_SALES_COD: 'Total Sales - COD',
  TOTAL_SALES_PREPAID: 'Total Sales - Prepaid',
};

export const WIDTHS = {
  FULL: 'full-width',
  TWO_THIRDS: 'two-thirds-width',
  HALF: 'half-width',
  ONE_THIRD: 'one-thirds-width',
};

export const OVERVIEW_LAYOUT: Layout[] = [
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

export const CONVERSION_LAYOUT: Layout[] = [
  {
    chart: CHART_LABEL_MAPPING.CONVERSION_FUNNEL,
    width: WIDTHS.FULL,
  },
  {
    chart: CHART_LABEL_MAPPING.CONVERSION_RATE,
    width: WIDTHS.FULL,
  },
];

export const TABS: Tabs = {
  OVERVIEW: {
    label: 'Overview',
    layout: OVERVIEW_LAYOUT,
    condition: (_user, dashboardView) =>
      dashboardView !== SOPC_APP_NAME && dashboardView !== RCOD_APP_NAME,
    isCharts: true,
  },
  CONVERSION: {
    label: 'Conversion',
    condition: (_user) => _user.isMagicOrderAnalyticsCREnabled,
    layout: CONVERSION_LAYOUT,
    isCharts: true,
  },
  REPORTS: {
    label: 'Reports',
    isCharts: false,
    Component: Reports,
  },
};
