export const TEST_ITEMS = [
  {
    id: 'id',
    entity: 'payment',
    amount: 20000,
    currency: 'INR',
    base_amount: 20000,
    status: 'captured',
    order_id: null,
    invoice_id: null,
    international: true,
    method: 'card',
    amount_refunded: 0,
    amount_transferred: 0,
    refund_status: null,
    captured: false,
    description: null,
    card_id: null,
    bank: null,
    vpa: null,
    email: 'email@razorpay.com',
    contact: '+918888888888',
    notes: {
      invoice_number: 'doc_123',
    },
    fee: null,
    tax: null,
    error_code: 'SERVER_ERROR',
    error_description:
      'We are facing some trouble completing your request at the moment. Please try again shortly.',
    error_source: 'internal',
    error_step: 'payment_initiation',
    error_reason: 'server_error',
    acquirer_data: {
      discount: 0,
      amount: 200,
    },
    created_at: 1655472637,
  },
];

export const MOCK_USER = {
  tags: [],
};

export const MOCK_PNG_FILE = new File(['hello'], 'testImage.png', { type: 'image/png' });
