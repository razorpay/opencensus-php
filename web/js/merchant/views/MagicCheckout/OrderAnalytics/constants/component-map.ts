import lazy from 'merchant/routes/LazyLoader';
import { CHART_LABEL_MAPPING } from '.';

const AverageOrderValue = lazy(
  () =>
    import(
      /* webpackChunkName: "MagicAverageOrderValueWidget" */ 'merchant/views/MagicCheckout/OrderAnalytics/widgets/AverageOrderValue'
    ),
);
const TopSellingProducts = lazy(
  () =>
    import(
      /* webpackChunkName: "MagicTopSellingProductsWidget" */ 'merchant/views/MagicCheckout/OrderAnalytics/widgets/TopSellingProducts'
    ),
);
const TotalSales = lazy(
  () =>
    import(
      /* webpackChunkName: "MagicTotalSalesWidget" */ 'merchant/views/MagicCheckout/OrderAnalytics/widgets/TotalSales'
    ),
);
const TotalOrders = lazy(
  () =>
    import(
      /* webpackChunkName: "MagicTotalOrdersWidget" */ 'merchant/views/MagicCheckout/OrderAnalytics/widgets/TotalOrders'
    ),
);
const TrafficByUTM = lazy(
  () =>
    import(
      /* webpackChunkName: "MagicTrafficByUTMWidget" */ 'merchant/views/MagicCheckout/OrderAnalytics/widgets/TrafficByUTM'
    ),
);
const SalesSplitV2 = lazy(
  () =>
    import(
      /* webpackChunkName: "MagicSalesSplitV2Widget" */ 'merchant/views/MagicCheckout/OrderAnalytics/widgets/SalesSplitV2'
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

const ConversionFunnel = lazy(
  () =>
    import(
      /* webpackChunkName: "MagicConversionFunnelWidget" */ 'merchant/views/MagicCheckout/OrderAnalytics/widgets/ConversionFunnel'
    ),
);

export const CHART_COMPONENT_MAP = {
  [CHART_LABEL_MAPPING.TOTAL_SALES]: TotalSales,
  [CHART_LABEL_MAPPING.TOTAL_ORDERS]: TotalOrders,
  [CHART_LABEL_MAPPING.AVG_ORDER_VALUE]: AverageOrderValue,
  [CHART_LABEL_MAPPING.TRAFFIC_UTM_PARAMS]: TrafficByUTM,
  [CHART_LABEL_MAPPING.TOP_SELLING_PRODUCTS]: TopSellingProducts,
  [CHART_LABEL_MAPPING.SALES_SPLIT]: SalesSplitV2,
  [CHART_LABEL_MAPPING.ORDER_SPLIT]: OrderSplitV2,
  [CHART_LABEL_MAPPING.CONVERSION_RATE]: ConversionRate,
  [CHART_LABEL_MAPPING.CONVERSION_FUNNEL]: ConversionFunnel,
};
