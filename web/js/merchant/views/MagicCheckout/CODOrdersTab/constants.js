import lazy from 'merchant/routes/LazyLoader';

const ReviewOrders = lazy(() =>
  import(
    /* webpackChunkName: "MagicCODOrders" */ 'merchant/views/MagicCheckout/CODOrdersTab/tabs/ReviewOrdersTab'
  ),
);

const ApprovedOrders = lazy(() =>
  import(
    /* webpackChunkName: "MagicCODOrders" */ 'merchant/views/MagicCheckout/CODOrdersTab/tabs/ApprovedOrdersTab'
  ),
);

const CanceledOrders = lazy(() =>
  import(
    /* webpackChunkName: "MagicCODOrders" */ 'merchant/views/MagicCheckout/CODOrdersTab/tabs/CanceledOrdersTab'
  ),
);

const OnHoldOrders = lazy(() =>
  import(
    /* webpackChunkName: "MagicCODOrders" */ 'merchant/views/MagicCheckout/CODOrdersTab/tabs/OnHoldOrdersTab'
  ),
);

export const TABS = [
  {
    id: 'reviewOrdersTab',
    title: 'Review Orders',
    component: <ReviewOrders />,
  },
  {
    id: 'approvedOrdersTab',
    title: 'Approved Orders',
    component: <ApprovedOrders />,
  },
  {
    id: 'canceledOrdersTab',
    title: 'Canceled Orders',
    component: <CanceledOrders />,
  },
  {
    id: 'onHoldOrdersTab',
    title: 'On Hold Orders',
    component: <OnHoldOrders />,
  },
];

export const RISK_TIER_COLOR_MAPPING = {
  high: ' high-risk',
  low: ' low-risk',
  medium: ' medium-risk',
};

export const RISK_TIERS = [
  { label: 'All', name: '' },
  { label: 'High Risk', name: 'high' },
  { label: 'Medium Risk', name: 'medium' },
  { label: 'Low Risk', name: 'low' },
];

export const REVIEW_STATUS_MAP = {
  approval_initiated: 'Approval Initiated',
  cancel_initiated: 'Cancel Initiated',
  hold_initiated: 'Hold Initiated',
  approved: 'Approved',
  canceled: 'Canceled',
};

export const REVIEW_CONFIRMATION_TEXTS = {
  approve: {
    heading: {
      singular: 'Approve Selected Order?',
      plural: 'Approve Selected Orders?',
    },
    desc: 'Once orders are approved they cannot be cancelled or put on hold.',
    affirmativeLabel: {
      singular: 'Approve Order',
      plural: 'Approve Orders',
    },
    abortLabel: 'Cancel',
  },
  cancel: {
    heading: {
      singular: 'Cancel Selected Order?',
      plural: 'Cancel Selected Orders?',
    },
    desc: 'Once orders are cancelled, they cannot be approved or put on hold.',
    affirmativeLabel: {
      singular: 'Cancel Order',
      plural: 'Cancel Orders',
    },
    abortLabel: 'Cancel',
  },
  hold: {
    heading: {
      singular: 'Hold Selected Order?',
      plural: 'Hold Selected Orders?',
    },
    desc: 'You can choose to approve or cancel this order later.',
    affirmativeLabel: {
      singular: 'Hold Order',
      plural: 'Hold Orders',
    },
    abortLabel: 'Cancel',
  },
};

export const REVIEW_STATUS_LABEL = {
  approve: 'approval_initiated',
  cancel: 'cancel_initiated',
  hold: 'hold_initiated',
};

export const ALERT_MESSAGES = {
  error: {
    approval_initiated: 'The order has already been approved by',
    cancel_initiated: 'The order has already been cancelled by',
    hold_initiated: 'The order has already been put on hold by',
    approved: 'The order has already been approved by',
    canceled: 'The order has already been cancelled by',
    hold: 'The order has already been put on hold by',
  },
  success: {
    approve: 'approval initiated successfully',
    cancel: 'cancellation initiated successfully',
    hold: 'hold initiated successfully',
  },
};

export const RISK_TIER_LABEL = {
  high: 'High Risk',
  medium: 'Medium Risk',
  low: 'Low Risk',
};

export const REVIEW_ORDERS_CATEGORY = [
  'approval_initiated',
  'cancel_initiated',
  'hold_initiated',
  'null',
];

export const RISK_TIER = {
  high: 'high',
  low: 'low',
  medium: 'medium',
};

export const DATE_RANGE_PRESETS = [
  ['Past 7 Days', -7, 'days'],
  ['Past 30 Days', -30, 'days'],
];

export const DATE_FORMAT = 'Do MMM YYYY, HH:MM:SS A';

export const REVIEW_ACTIONS = {
  approve: 'approve',
  cancel: 'cancel',
  hold: 'hold',
};

export const REVIEWED_ORDERS_CATEGORY = {
  approved: ['approved'],
  canceled: ['canceled'],
  hold: ['hold'],
};

export const MIN_START_DATE = 1663957800000;

export const AUTOMATION_BANNER_SUBHEADING =
  'Set conditions to automatically approve / hold / cancel your COD orders on the basis of RTO risk.';
export const AUTOMATION_TAB_LINK = '/magic/settings/cod-review-workflow';

export const REVIEW_MODE = [
  { label: 'All', name: '' },
  { label: 'Manual', name: 'manual' },
  { label: 'Automation', name: 'automation' },
];
