import Overview from 'merchant/views/MagicCheckout/RTOAnalytics/containers/Overview';
import OrderInsights from 'merchant/views/MagicCheckout/RTOAnalytics/containers/OrderInsights';
import RiskReport from 'merchant/views/MagicCheckout/RTOAnalytics/containers/RiskReport';

const COLOR_SAFE = '#7EB471';
const COLOR_RISKY = '#E86250';
const COLOR_NEUTRAL = '#FBBF48';
const COLOR_TOTAL = '#6886B7';

const LINE_GRAPH_COLOR_RISKY = '#FEF6F6';
const LINE_GRAPH_COLOR_SAFE = '#E2F7E2';
const LINE_GRAPH_COLOR_TOTAL = '#E1EBFB';
const LINE_GRAPH_COLOR_NEUTRAL = '#FEFBED';

export const TABS = {
  OVERVIEW: {
    label: 'Overview',
    Component: Overview,
    eventName: 'Overview',
  },
  RISK_REPORT: {
    label: 'Risk Report',
    Component: RiskReport,
    eventName: 'RiskReport',
  },
  ORDER_INSIGHTS: {
    label: 'RTO Insights',
    Component: OrderInsights,
    eventName: 'RTOInsights',
  },
};

export const FLAGGEDREASONS_DOUGHNUT_COLORS = [
  '#B5356D',
  '#A93A8A',
  '#923A94',
  '#7848B6',
  '#5B4EAE',
];

export const UPLOAD_ORDER_STATUS_TAB = '/magic/delivery-status/';

export const MAGIC_SETTINGS_TAB = '/magic/settings/rto-settings';

export const DEFAULT_SHIPPING_CHARGE = 75;

export const CHART_COLORS = {
  TOTAL_ORDERS: COLOR_TOTAL,
  RISKY_ORDERS: COLOR_RISKY,
  SAFE_ORDERS: COLOR_SAFE,
  RISKY_USERS: COLOR_RISKY,
  SAFE_USERS: COLOR_SAFE,
  RTO_ORDERS: COLOR_RISKY,
  TOTAL_SAFE_ORDERS: COLOR_TOTAL,
  NON_RTO_ORDERS: COLOR_SAFE,
  REGULAR_PREPAID_ORDERS: COLOR_SAFE,
  ADDITIONAL_PREPAID_ORDERS: COLOR_TOTAL,
  ORDERS_PLACED: COLOR_SAFE,
  TOTAL_RTO_RATE: COLOR_TOTAL,
  COD_RTO_RATE: COLOR_SAFE,
  PREPAID_RTO_RATE: COLOR_NEUTRAL,
  COD_ORDERS_PERCENTAGE: COLOR_SAFE,
  APPROVED_ORDERS: COLOR_SAFE,
  CANCELED_ORDERS: COLOR_RISKY,
  ON_HOLD_ORDERS: COLOR_NEUTRAL,
  NO_ACTION_TAKEN_ORDERS: COLOR_TOTAL,
  HIGH_RISK_ORDERS: COLOR_RISKY,
  MEDIUM_RISK_ORDERS: COLOR_NEUTRAL,
  LOW_RISK_ORDERS: COLOR_SAFE,
};

export const DATASET_LABEL_MAP = {
  TOTAL_ORDERS: {
    label: 'Total orders',
    response_key: 'total_order',
  },
  TOTAL_USERS: {
    label: 'Total users',
    response_key: 'total_order',
  },
  RISKY_USERS: {
    label: 'Risky users',
    response_key: 'risky_order',
  },
  SAFE_USERS: {
    label: 'Safe users',
    response_key: 'safe_order',
  },
  RTO_ORDERS: {
    label: 'RTO orders',
    response_key: 'rto_order',
  },
  TOTAL_SAFE_ORDERS: {
    label: 'Total safe orders',
    response_key: 'total_order',
  },
  NON_RTO_ORDERS: {
    label: 'Non RTO orders',
    response_key: 'safe_order',
  },
  COD_ORDERS_PERCENTAGE: {
    label: 'COD orders',
    response_key: 'percentage',
  },
  REGULAR_PREPAID_ORDERS: {
    label: 'Regular orders',
    response_key: 'safe_successful_orders_count',
  },
  ADDITIONAL_PREPAID_ORDERS: {
    label: 'Additional prepaid orders',
    response_key: 'risky_successful_orders_count',
  },
  ORDERS_PLACED: {
    label: 'Orders placed successfully',
    response_key: 'placed_orders',
  },
  TOTAL_RTO_RATE: {
    label: 'Overall RTO%',
    response_key: 'total_rto_rate',
  },
  COD_RTO_RATE: {
    label: 'COD RTO%',
    response_key: 'cod_rto_rate',
  },
  PREPAID_RTO_RATE: {
    label: 'Prepaid RTO%',
    response_key: 'prepaid_rto_rate',
  },
  ON_HOLD_ORDERS: {
    label: 'Put on hold',
    response_key: 'hold_order',
  },
  APPROVED_ORDERS: {
    label: 'Approved orders',
    response_key: 'approved_order',
  },
  CANCELED_ORDERS: {
    label: 'Cancelled orders',
    response_key: 'cancelled_order',
  },
  NO_ACTION_TAKEN_ORDERS: {
    label: 'No action taken',
    response_key: 'no_action_order',
  },
  HIGH_RISK_ORDERS: {
    label: 'High risk orders',
    response_key: 'high_risk_order',
  },
  MEDIUM_RISK_ORDERS: {
    label: 'Medium risk orders',
    response_key: 'medium_risk_order',
  },
  LOW_RISK_ORDERS: {
    label: 'Low risk orders',
    response_key: 'low_risk_order',
  },
};

export const BREAKDOWN_MAP = {
  daily: {
    text: 'Daily',
    value: 'day',
  },
  weekly: {
    text: 'Weekly',
    value: 'week',
  },
  monthly: {
    text: 'Monthly',
    value: 'month',
  },
};

export const ORDERS_SPLIT_CHARTS = ['TOTAL_USERS', 'RISKY_USERS', 'SAFE_USERS'];

export const RISK_LEVEL_ORDERS_SPLIT_CHARTS = [
  'TOTAL_ORDERS',
  'HIGH_RISK_ORDERS',
  'MEDIUM_RISK_ORDERS',
  'LOW_RISK_ORDERS',
];

export const MANUAL_REVIEW_ORDERS_SPLIT_CHARTS = [
  'TOTAL_ORDERS',
  'APPROVED_ORDERS',
  'CANCELED_ORDERS',
  'ON_HOLD_ORDERS',
  'NO_ACTION_TAKEN_ORDERS',
];

export const SAFE_ORDERS_CHARTS = ['TOTAL_SAFE_ORDERS', 'NON_RTO_ORDERS', 'RTO_ORDERS'];

export const COD_RATE_CHARTS = ['COD_ORDERS_PERCENTAGE'];

export const RISKY_ORDERS_CHARTS = [
  'TOTAL_ORDERS',
  'REGULAR_PREPAID_ORDERS',
  'ADDITIONAL_PREPAID_ORDERS',
];

export const RTO_RATE_CHARTS = ['TOTAL_RTO_RATE', 'COD_RTO_RATE', 'PREPAID_RTO_RATE'];

// this is for bar and line charts
export const defaultOptions = {
  responsive: true,
  legend: {
    display: false,
  },
  tooltips: {
    enabled: true,
    backgroundColor: '#ffffff',
    borderColor: '#e0e8f4',
    borderWidth: 1,
    bodySpacing: 12,
    position: 'average',
    bodyFontColor: '#262D3A',
    footerFontColor: '#8A91AC',
    xPadding: 12,
    yPadding: 12,
    cornerRadius: 2,
    footerMarginTop: 14,
    footerFontStyle: 'normal',
    callbacks: {
      title() {},
    },
  },
  scales: {
    xAxes: [
      {
        type: 'time',
        distribution: 'series',
        time: {
          displayFormats: {
            hour: 'MMM D',
            month: 'MMM YYYY',
            day: 'MMM D',
            week: 'MMM YYYY',
            second: 'MMM D',
            millisecond: 'MMM D',
          },
          tooltipFormat: 'ddd DD MMM YYYY',
        },
        stacked: false,
        gridLines: {
          offsetGridLines: true,
          display: true,
          drawOnChartArea: false,
          drawTicks: true,
        },
        ticks: {
          autoSkip: true,
          fontSize: 12,
          fontColor: '#858C9A',
          maxRotation: 0,
          autoSkipPadding: 15,
        },
      },
    ],
    yAxes: [
      {
        ticks: {
          beginAtZero: true,
          padding: 10,
          fontSize: 12,
          maxTicksLimit: 5,
          fontColor: '#858C9A',
          precision: 0,
        },
        stacked: false,
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
  layout: {
    padding: {
      top: 24,
      left: 0,
      right: 0,
      bottom: 0,
    },
  },
};

export const NO_GRAPH_DATA = {
  customTitle: 'No data to display',
  customSubtitle: `There is no data available for the selected date-range.
                  Please modify the date-range and try again.`,
};

export const LINE_CHART_GRAPH_COLOR = {
  RTO_ORDERS: LINE_GRAPH_COLOR_RISKY,
  NON_RTO_ORDERS: LINE_GRAPH_COLOR_SAFE,
  TOTAL_RTO_RATE: LINE_GRAPH_COLOR_TOTAL,
  COD_RTO_RATE: LINE_GRAPH_COLOR_SAFE,
  PREPAID_RTO_RATE: LINE_GRAPH_COLOR_NEUTRAL,
  REGULAR_PREPAID_ORDERS: LINE_GRAPH_COLOR_SAFE,
  ADDITIONAL_PREPAID_ORDERS: LINE_GRAPH_COLOR_TOTAL,
  RISKY_USERS: LINE_GRAPH_COLOR_RISKY,
  SAFE_USERS: LINE_GRAPH_COLOR_SAFE,
  COD_ORDERS_PERCENTAGE: LINE_GRAPH_COLOR_SAFE,
  TOTAL_ORDERS: LINE_GRAPH_COLOR_TOTAL,
  TOTAL_USERS: LINE_GRAPH_COLOR_TOTAL,
  APPROVED_ORDERS: LINE_GRAPH_COLOR_SAFE,
  CANCELED_ORDERS: LINE_GRAPH_COLOR_RISKY,
  ON_HOLD_ORDERS: LINE_GRAPH_COLOR_NEUTRAL,
  NO_ACTION_TAKEN_ORDERS: LINE_GRAPH_COLOR_TOTAL,
  HIGH_RISK_ORDERS: LINE_GRAPH_COLOR_RISKY,
  MEDIUM_RISK_ORDERS: LINE_GRAPH_COLOR_NEUTRAL,
  LOW_RISK_ORDERS: LINE_GRAPH_COLOR_SAFE,
};

export const BREAKDOWN = {
  days: 'daily',
  weeks: 'weekly',
  months: 'monthly',
  cumulative: 'cumulative',
  lifetime: 'lifetime',
};

export const RTO_RATE_FILTER = [
  { label: 'COD RTO %', name: 'COD_RTO_RATE' },
  { label: 'Prepaid RTO %', name: 'PREPAID_RTO_RATE' },
  { label: 'Overall RTO %', name: 'TOTAL_RTO_RATE' },
  { label: 'View all RTO %', name: 'ALL_RTO_RATES' },
];

export const REQUEST_LIMIT = 2;

export const OVERALL_LINE_CHARTS = ['rto_rate'];

export const COST_SAVED_WIDGET_TEXTS = {
  manualReview: {
    header: 'Cost saved due to manual review of COD orders',
    subtext: 'Total reverse shipping cost saved by cancelling risky COD orders.',
  },
  intelligence: {
    header: 'Cost saved by COD Intelligence',
    subtext: 'Total reverse shipping cost saved by blocking risky users from placing COD orders.',
  },
};
