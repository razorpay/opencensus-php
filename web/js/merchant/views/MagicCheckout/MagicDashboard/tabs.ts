import lazy from 'merchant/routes/LazyLoader';

import { User } from 'merchant/views/MagicCheckout/types';
import { MagicDashboardLayout } from 'merchant/views/MagicCheckout/MagicDashboard/types';

const TotalSales = lazy(
  () =>
    import(
      /* webpackChunkName: "MagicTotalSalesWidget" */ 'merchant/views/MagicCheckout/OrderAnalytics/widgets/TotalSales'
    ),
);

const OrderSplitV2 = lazy(
  () =>
    import(
      /* webpackChunkName: "MagicOrderSplitV2Widget" */ 'merchant/views/MagicCheckout/OrderAnalytics/widgets/OrderSplitV2'
    ),
);

const ConversionRate = lazy(
  () =>
    import(
      /* webpackChunkName: "MagicConversionRateWidget" */ 'merchant/views/MagicCheckout/OrderAnalytics/widgets/ConversionRate'
    ),
);

const RTORateComparison = lazy(
  () =>
    import(
      /* webpackChunkName: "RTORatePreAndPostMagic" */ 'merchant/views/MagicCheckout/RTOAnalytics/widgets/PreAndPostMagic'
    ),
);
export const CHART_LABEL_MAPPING = {
  CONVERSION_RATE: 'conversion_rate',
  TOTAL_SALES: 'total_sales',
  ORDER_SPLIT: 'prepay_vs_cod_total_orders_placed',
  RTO_RATE_COMPARISON: 'rto_rate_comparison',
};

export const WIDTHS = {
  FULL: 'full-width',
  TWO_THIRDS: 'two-thirds-width',
  HALF: 'half-width',
  ONE_THIRD: 'one-thirds-width',
};

export const MAGIC_DASHBOARD_LAYOUT: Array<MagicDashboardLayout> = [
  {
    chart: CHART_LABEL_MAPPING.CONVERSION_RATE,
    width: WIDTHS.HALF,
    type: 'Conversion Rate Analytics',
    condition: (_user: User) =>
      _user.isMagicOrderAnalyticsEnabled && _user.isMagicOrderAnalyticsCREnabled,
    onRCOD: true,
  },
  {
    chart: CHART_LABEL_MAPPING.TOTAL_SALES,
    width: WIDTHS.HALF,
    type: 'Order Analytics',
    condition: (_user: User) => _user.isMagicOrderAnalyticsEnabled,
  },
  {
    chart: CHART_LABEL_MAPPING.ORDER_SPLIT,
    width: WIDTHS.HALF,
    type: 'Order Analytics',
    condition: (_user: User) => _user.isMagicOrderAnalyticsEnabled,
  },
  {
    chart: CHART_LABEL_MAPPING.RTO_RATE_COMPARISON,
    width: WIDTHS.HALF,
    type: 'RTO Analytics',
  },
];

export const CHART_COMPONENT_MAP = {
  [CHART_LABEL_MAPPING.TOTAL_SALES]: TotalSales,
  [CHART_LABEL_MAPPING.ORDER_SPLIT]: OrderSplitV2,
  [CHART_LABEL_MAPPING.CONVERSION_RATE]: ConversionRate,
  [CHART_LABEL_MAPPING.RTO_RATE_COMPARISON]: RTORateComparison,
};

export const MAGIC_DASHBOARD = {
  label: 'Overview',
  layout: MAGIC_DASHBOARD_LAYOUT,
  isCharts: true,
};
