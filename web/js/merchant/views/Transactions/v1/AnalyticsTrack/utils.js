const EVENT_MAP = {
  batchRefund: {
    event: 'Batch Refund',
    page: 'Batch Refunds',
  },
  dispute: {
    event: 'Dispute',
    page: 'Disputes Listing',
  },
  order: {
    event: 'Order',
    page: 'Order Listing',
  },
  payment: {
    event: 'Payment',
    page: 'Payment Listing',
  },
  refund: {
    event: 'Refund',
    page: 'Refund Listing',
  },
};

const ACTION_MAP = {
  search: 'Searched',
  filter: 'Filtered',
  fetch: 'Fetched',
};

export const getAction = (section, type) => {
  const response = {};
  if (section && EVENT_MAP[section]) {
    const { event, page } = EVENT_MAP[section];
    response.action = `${event} Details ${ACTION_MAP[type]}`;
    response.page = page;
  }
  return response;
};
