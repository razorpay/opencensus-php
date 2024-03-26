import { FunnelTypeMaps } from 'merchant/views/MagicCheckout/OrderAnalytics/widgets/ConversionFunnel/types';
import { funnelCustomTooltip } from './utils';
export const FUNNEL_STEPS = {
  checkout_initiated: 'Checkout Initiated',
  reached_address_screen: 'Reached Address Screen',
  reached_payment_screen: 'Reached Payment Screen',
  payment_method_selected: 'Payment Method Selected',
  order_placed: 'Order Placed',
};

export const CHART_CONFIG = {
  responsive: true,

  scales: {
    xAxes: [
      {
        gridLines: {
          display: false,
          drawTicks: false,
        },
        ticks: {
          display: false,
        },
      },
    ],
    yAxes: [
      {
        ticks: {
          beginAtZero: true,
          display: true,
          fontSize: 12,
          fontColor: '#58666E',
          fontFamily: 'Lato',
        },
        gridLines: {
          display: false,
        },
      },
    ],
  },
  tooltips: {
    enabled: false,
    position: 'nearest',
    custom: function custom(tooltipModel) {
      funnelCustomTooltip(tooltipModel, this);
    },
  },
};

export const FUNNEL_TYPES: FunnelTypeMaps = {
  FUNNEL: 'conversion_funnel',
  FUNNEL_LOGGED_IN: 'conversion_funnel_logged_in',
  FUNNEL_LOGGED_OUT: 'conversion_funnel_logged_out',
};

export const FUNNEL_FILTER = [
  { label: 'Total', name: 'FUNNEL' },
  { label: 'Logged In', name: 'FUNNEL_LOGGED_IN' },
  { label: 'Logged Out', name: 'FUNNEL_LOGGED_OUT' },
];
