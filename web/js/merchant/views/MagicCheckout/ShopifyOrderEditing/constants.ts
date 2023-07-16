export const ORDER_STATUS_COLOR_MAPPING = {
  UNFULFILLED: 'unfulfilled-orders',
  FULFILLED: 'fullfiled-orders',
  PARTIALLY_FULFILLED: 'partially-fulfilled-orders',
  ON_HOLD: 'unfulfilled-orders',
  ARCHIVE: 'unfulfilled-orders',
};

export const PAYMENT_STATUS_COLOR_MAPPING = {
  PAID: 'low-risk',
  PENDING: 'high-risk',
  REFUNDED: 'canceled-label',
  PARTIALLY_PAID: 'medium-risk',
  PARTIALLY_REFUNDED: 'medium-risk',
  VOIDED: 'canceled-label',
};

export const ORDER_STATUS = {
  FULFILLED: 'FULFILLED',
  UNFULFILLED: 'UNFULFILLED',
  PARTIALLY_FULFILLED: 'PARTIALLY_FULFILLED',
  ON_HOLD: 'ON_HOLD',
  ARCHIVE: 'ARCHIVE',
};

export const DATE_FORMAT = 'Do MMM YYYY, HH:mm:ss A';

export const ORDER_EDITING_SUBTABS = {
  DEFAULT: 'default',
  ADD_ITEMS: 'add-items',
  ADD_CUSTOM_ITEMS: 'add-custom-items',
  ADD_DISCOUNT: 'add-discount',
  EXIT_INTENT: 'exit-intent',
};

export const HEADER_TITLE = {
  DEFAULT: 'Edit Order',
  EXIT_INTENT: 'Exit Edit Order',
};
