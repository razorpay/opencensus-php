import ListView from 'merchant/views/PaymentHandle/views/List';

// TODO : we are resolving this as an promise , because handlers were not updating store
// Need to check later why it happening.
jest.mock('merchant/views/PaymentPages/PaymentPages/model', () => ({
  fetchPaymentPageEntity: jest.fn().mockResolvedValue({
    data: {
      id: 'pl_JneRfB2WsBgkr2',
      amount: null,
      currency: 'INR',
      currency_symbol: '\u20b9',
      expire_by: null,
      times_payable: null,
      times_paid: 0,
      total_amount_paid: 100,
      status: 'active',
      status_reason: null,
      short_url: 'https://razorpay.me/@deepnewbusiness',
      user_id: null,
      user: null,
      receipt: null,
      title: 'Demo',
      description: null,
      notes: [],
      support_contact: null,
      support_email: null,
      terms: null,
      type: 'payment',
      payment_page_items: [
        {
          id: 'ppi_JneRfDZc2lvEyl',
          entity: 'payment_page_item',
          payment_link_id: 'pl_JneRfB2WsBgkr2',
          item: {
            id: 'item_JneRfDzwAyH2bO',
            active: true,
            name: 'amount',
            description: null,
            amount: null,
            unit_amount: null,
            currency: 'INR',
            type: 'payment_page',
            unit: null,
            tax_inclusive: false,
            hsn_code: null,
            sac_code: null,
            tax_rate: null,
            tax_id: null,
            tax_group_id: null,
            created_at: 1656580145,
          },
          mandatory: true,
          image_url: null,
          stock: null,
          quantity_sold: 1,
          total_amount_paid: 100,
          min_purchase: null,
          max_purchase: null,
          min_amount: 100,
          max_amount: null,
          settings: {
            position: '0',
          },
          plan_id: null,
          product_config: null,
        },
      ],
      created_at: 1656580145,
      updated_at: 1676365085,
      slug: '@deepnewbusiness',
      captured_payments_count: 1,
      settings: {
        udf_schema:
          '[{"name":"comment","title":"Comment","required":true,"type":"string","options":[],"settings":{"position":1}}]',
        version: 'V2',
        theme: 'light',
      },
    },
  }),
}));

export const App = (props = {}) => {
  return <ListView {...props} />;
};
