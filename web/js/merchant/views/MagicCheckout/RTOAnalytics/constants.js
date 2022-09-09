import { NavLink } from 'react-router-dom';
import Overview from 'merchant/views/MagicCheckout/RTOAnalytics/containers/Overview';
import OrderInsights from 'merchant/views/MagicCheckout/RTOAnalytics/containers/OrderInsights';

export const TABS = {
  OVERVIEW: {
    label: 'Overview',
    component: <Overview />,
  },
  ORDER_INSIGHTS: {
    label: 'Order Insights',
    component: <OrderInsights />,
  },
};

export const FLAGGEDREASONS_DOUGHNUT_COLORS = [
  '#B5356D',
  '#A93A8A',
  '#923A94',
  '#7848B6',
  '#5B4EAE',
];

export const FEEDBACKRATE_FOOTER_TEXTS = {
  complete: (
    <small>
      <div className="custom-success-tick">
        <i className="i i-tick" />
      </div>
      <span className="feedback-footer-info">RTO order up to date</span>
    </small>
  ),
  empty: (
    <small>
      <i className="i i-warning empty-status" />
      <span className="feedback-footer-info">RTO order data unavailable</span>
    </small>
  ),
  partial: (
    <small>
      <i className="i i-warning partial-status" />
      <span className="feedback-footer-info">Incomplete RTO order data available</span>
    </small>
  ),
};

export const FEEDBACKRATE_INFO_TEXTS = {
  incomplete: (
    <p>
      RTO insights depends on data shared by you.{' '}
      <NavLink className="feedbackRate-links" to="/magic/order-status">
        Upload your data
      </NavLink>{' '}
      now or directly integrate a{' '}
      <NavLink className="feedbackRate-links" to="/magic/settings">
        logistic partner.
      </NavLink>
    </p>
  ),
  complete: (
    <p>
      RTO insights accuracy depends on data passed from your integrated{' '}
      <NavLink className="feedbackRate-links" to="/magic/settings">
        logistic partner.
      </NavLink>
    </p>
  ),
};

export const CHART_COLORS = {
  TOTAL_ORDERS: '#6886B7',
  RISKY_ORDERS: '#E86250',
  SAFE_ORDERS: '#7EB471',
  RTO_ORDERS: '#E86250',
};

export const DATASET_LABEL_MAP = {
  TOTAL_ORDERS: {
    label: 'Total Orders',
    response_key: 'total_order',
  },
  RISKY_ORDERS: {
    label: 'Risky Orders',
    response_key: 'risky_order',
  },
  SAFE_ORDERS: {
    label: 'Safe Orders',
    response_key: 'safe_order',
  },
  RTO_ORDERS: {
    label: 'RTO Orders',
    response_key: 'rto_order',
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

export const ORDERS_SPLIT_CHARTS = ['TOTAL_ORDERS', 'RISKY_ORDERS', 'SAFE_ORDERS'];

export const SAFE_ORDERS_CHARTS = ['SAFE_ORDERS', 'RTO_ORDERS'];

// this is for bar and line charts
export const defaultOptions = {
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
  layout: {
    padding: {
      top: 0,
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
