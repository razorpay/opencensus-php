export const order = {
  resourceIdField: 'id',
  id: 'order_KREYPLTHhqEgi8',
  entity: 'order',
  amount: 2000,
  amount_paid: 0,
  amount_due: 2000,
  currency: 'INR',
  receipt: 'R112223',
  offer_id: null,
  status: 'created',
  attempts: 3,
  shipping_fee: 99,
  notes: {
    notes_key_1: '',
  },
  created_at: 1665222570,
  line_items_total: 3000,
  offer: {
    id: 'offer_id_123',
    name: 'test offer',
    discount: 10,
  },
  resourceUrl: 'orders',
  amountInINR: '20.00',
  promotions: [
    {
      code: 'ADBDB',
      value: 5,
    },
  ],
  tax_details: {
    total_tax: 1080,
    taxes_included: false,
  },
};

export const customer_details = {
  contact: '9999912345',
  email: 'user@test.com',
  shipping_address: {
    name: 'user shipping name',
    line1: 'shipping address line 1',
    line2: 'shipping address line 2',
    city: 'shipping city',
    state: 'shipping state',
    zipcode: 'shipping zipcode',
    contact: '9999912346',
  },
  billing_address: {
    name: 'user billing name',
    line1: 'billing address line 1',
    line2: 'billing address line 2',
    city: 'billing city',
    state: 'billing state',
    zipcode: 'billing zipcode',
    contact: '9999912347',
  },
};

export const getOrderLineItems = (number) => {
  const line_items = [];
  while (number--) {
    line_items.push({
      sku: `${Math.floor(Math.random() * 900000) + 100000}`,
    });
  }
  return line_items;
};
