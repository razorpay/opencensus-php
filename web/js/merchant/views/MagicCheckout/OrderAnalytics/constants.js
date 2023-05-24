import {
  campaign,
  medium,
  source,
  total_sales,
  total_orders,
} from 'merchant/views/MagicCheckout/OrderAnalytics/common/cellItem';

import { i18HumanReadableCurrency, i18HumanReadableNumerals } from 'common/utils/numerals';

import lazy from 'merchant/routes/LazyLoader';
import FbIcon from 'assets/payment_pages/fb-pixel-logo.svg';
import GoogleIcon from 'assets/google-icon-transparent.svg';
import InstagramIcon from 'assets/instagram-logo.svg';
import WhatsAppIcon from 'assets/whatsapp.svg';

const AverageOrderValue = lazy(() =>
  import(
    /* webpackChunkName: "MagicAverageOrderValueWidget" */ 'merchant/views/MagicCheckout/OrderAnalytics/widgets/AverageOrderValue'
  ),
);
const TopSellingProducts = lazy(() =>
  import(
    /* webpackChunkName: "MagicTopSellingProductsWidget" */ 'merchant/views/MagicCheckout/OrderAnalytics/widgets/TopSellingProducts'
  ),
);
const TotalSales = lazy(() =>
  import(
    /* webpackChunkName: "MagicTotalSalesWidget" */ 'merchant/views/MagicCheckout/OrderAnalytics/widgets/TotalSales'
  ),
);
const TotalOrders = lazy(() =>
  import(
    /* webpackChunkName: "MagicTotalOrdersWidget" */ 'merchant/views/MagicCheckout/OrderAnalytics/widgets/TotalOrders'
  ),
);
const TrafficByUTM = lazy(() =>
  import(
    /* webpackChunkName: "MagicTrafficByUTMWidget" */ 'merchant/views/MagicCheckout/OrderAnalytics/widgets/TrafficByUTM'
  ),
);
const SalesSplitV2 = lazy(() =>
  import(
    /* webpackChunkName: "MagicSalesSplitV2Widget" */ 'merchant/views/MagicCheckout/OrderAnalytics/widgets/SalesSplitV2'
  ),
);
const OrderSplitV2 = lazy(() =>
  import(
    /* webpackChunkName: "MagicOrderSplitV2Widget" */ 'merchant/views/MagicCheckout/OrderAnalytics/widgets/OrderSplitV2'
  ),
);

export const NO_GRAPH_DATA = {
  customTitle: 'No data to display',
  customSubtitle: `There is no data available for the selected date-range.
                  Please modify the date-range and try again.`,
};

const COLORS = {
  GREEN: '#7EB471',
  BLUE: '#01BBFF',
  DARK_BLUE: '#1B3C96',
  PURPLE: '#9573FB',
  DARK_PURPLE: '#4f39a6',
};

const GRADIENT_COLORS = {
  GREEN: '#E2F7E2',
  BLUE: '#CEDDF1',
  PURPLE: '#E3D9FF',
};

export const CHART_LABEL_MAPPING = {
  TOTAL_SALES: 'total_sales',
  TOTAL_ORDERS: 'total_orders_placed',
  AVG_ORDER_VALUE: 'average_order_value',
  ORDER_SALES_SPLIT: 'prepay_vs_cod',
  TRAFFIC_UTM_PARAMS: 'traffic_utm',
  TOP_SELLING_PRODUCTS: 'top_selling_products',
  SALES_SPLIT: 'prepay_vs_cod_total_sales',
  ORDER_SPLIT: 'prepay_vs_cod_total_orders_placed',
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

export const LAYOUT = [
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

export const CHART_COMPONENT_MAP = {
  [CHART_LABEL_MAPPING.TOTAL_SALES]: TotalSales,
  [CHART_LABEL_MAPPING.TOTAL_ORDERS]: TotalOrders,
  [CHART_LABEL_MAPPING.AVG_ORDER_VALUE]: AverageOrderValue,
  [CHART_LABEL_MAPPING.TRAFFIC_UTM_PARAMS]: TrafficByUTM,
  [CHART_LABEL_MAPPING.TOP_SELLING_PRODUCTS]: TopSellingProducts,
  [CHART_LABEL_MAPPING.SALES_SPLIT]: SalesSplitV2,
  [CHART_LABEL_MAPPING.ORDER_SPLIT]: OrderSplitV2,
};

export const AGGREGATION_TYPES = {
  HOURLY: 'hourly',
  DAILY: 'daily',
};

export const UTM_OPTIONS = [
  {
    label: 'Source',
    name: 'sources',
  },
  {
    label: 'Medium',
    name: 'mediums',
  },
  {
    label: 'Campaign',
    name: 'campaigns',
  },
];

export const UTM_TABLE_COLUMN_MAP = {
  [UTM_OPTIONS[0].name]: [source, total_orders, total_sales],
  [UTM_OPTIONS[1].name]: [source, medium, total_orders, total_sales],
  [UTM_OPTIONS[2].name]: [source, medium, campaign, total_orders, total_sales],
};

export const LINE_CHARTS = [
  CHART_LABEL_MAPPING.TOTAL_SALES,
  CHART_LABEL_MAPPING.TOTAL_ORDERS,
  CHART_LABEL_MAPPING.AVG_ORDER_VALUE,
  CHART_LABEL_MAPPING.ORDER_SPLIT,
  CHART_LABEL_MAPPING.TOTAL_ORDERS_COD,
  CHART_LABEL_MAPPING.TOTAL_ORDERS_PREPAID,
];

export const CHART_COLORS = {
  [CHART_LABEL_MAPPING.TOTAL_SALES]: COLORS.BLUE,
  [CHART_LABEL_MAPPING.TOTAL_ORDERS]: COLORS.PURPLE,
  [CHART_LABEL_MAPPING.AVG_ORDER_VALUE]: COLORS.GREEN,
  [CHART_LABEL_MAPPING.TOTAL_SALES_COD]: COLORS.DARK_BLUE,
  [CHART_LABEL_MAPPING.TOTAL_SALES_PREPAID]: COLORS.BLUE,
  [CHART_LABEL_MAPPING.TOTAL_ORDERS_COD]: COLORS.DARK_PURPLE,
  [CHART_LABEL_MAPPING.TOTAL_ORDERS_PREPAID]: COLORS.PURPLE,
};

export const LINE_CHART_GRAPH_COLOR = {
  [CHART_LABEL_MAPPING.TOTAL_SALES]: GRADIENT_COLORS.BLUE,
  [CHART_LABEL_MAPPING.TOTAL_ORDERS]: GRADIENT_COLORS.PURPLE,
  [CHART_LABEL_MAPPING.AVG_ORDER_VALUE]: GRADIENT_COLORS.GREEN,
  [CHART_LABEL_MAPPING.TOTAL_ORDERS_COD]: GRADIENT_COLORS.BLUE,
  [CHART_LABEL_MAPPING.TOTAL_ORDERS_PREPAID]: GRADIENT_COLORS.PURPLE,
};

export const METRIC_TYPE = {
  CURRENCY: 'currency',
  PERCENTAGE: 'percentage',
  NUMBER: 'number',
};

export const AGGERGATE_OPERATION = {
  SUM: 'sum',
  AVERAGE: 'average',
};

export const DEFAULT_CHART_OPTIONS = {
  responsive: true,
  scales: {
    xAxes: [
      {
        stacked: true,
        offset: true,
        gridLines: {
          offsetGridLines: true,
          display: true,
          drawOnChartArea: false,
          drawTicks: true,
        },
        ticks: {
          autoSkip: true,
          fontSize: 12,
          fontColor: '#58666E',
          maxRotation: 0,
          autoSkipPadding: 30,
          labelOffset: 15,
        },
      },
    ],
    yAxes: [
      {
        stacked: true,
        ticks: {
          beginAtZero: true,
          padding: 10,
          fontSize: 12,
          maxTicksLimit: 5,
          fontColor: '#58666E',
        },
        gridLines: {
          color: '#F1F3F6',
          zeroLineColor: '#E0E8F4',
          display: true,
          drawTicks: false,
          drawBorder: false,
        },
      },
    ],
  },
  tooltips: {
    enabled: false,
    position: 'nearest',
  },
  layout: {
    padding: {
      top: 24,
      left: 24,
      right: 24,
      bottom: 0,
    },
  },
};

export const TOOLTIP_HANDLERS = {
  [CHART_LABEL_MAPPING.TOTAL_SALES]: {
    label: 'Total Sales',
    value: (val) => i18HumanReadableCurrency(val, 'INR'),
  },
  [CHART_LABEL_MAPPING.TOTAL_ORDERS]: {
    label: 'Total Orders',
    value: (val) => i18HumanReadableNumerals(val),
  },
  [CHART_LABEL_MAPPING.AVG_ORDER_VALUE]: {
    label: 'Average Value',
    value: (val) => i18HumanReadableCurrency(val, 'INR'),
  },
  [CHART_LABEL_MAPPING.TOTAL_SALES_COD]: {
    value: (val) => i18HumanReadableCurrency(val, 'INR'),
  },
  [CHART_LABEL_MAPPING.TOTAL_SALES_PREPAID]: {
    value: (val) => i18HumanReadableCurrency(val, 'INR'),
  },
  [CHART_LABEL_MAPPING.TOTAL_ORDERS_PREPAID]: {
    value: (val) => i18HumanReadableNumerals(val),
  },
  [CHART_LABEL_MAPPING.TOTAL_ORDERS_COD]: {
    value: (val) => i18HumanReadableNumerals(val),
  },
};

export const UTM_KEYS = {
  SOURCE: 'utm_source',
  MEDIUM: 'utm_medium',
  CAMPAIGN: 'utm_campaign',
};

export const LIMIT = 10;

export const UTM_SOURCE_ICONS = {
  facebook: FbIcon,
  google: GoogleIcon,
  whatsapp: WhatsAppIcon,
  instagram: InstagramIcon,
};
