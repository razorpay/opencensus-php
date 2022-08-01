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
  const { event, page } = EVENT_MAP[section];
  return {
    action: `${event} Details ${ACTION_MAP[type]}`,
    page,
  };
};
