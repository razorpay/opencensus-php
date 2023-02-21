import { rest } from 'msw';

const RLResp = {
  entity: 'collection',
  count: 1,
  has_more: true,
  items: [
    {
      id: 'inv_L7tZvc3bp6fbT9',
      entity: 'invoice',
      receipt: '676',
      invoice_number: '676',
      customer_id: 'cust_KzzbVyt7xqrPNH',
      customer_details: {
        id: 'cust_KzzbVyt7xqrPNH',
        name: 'utkarsh',
        email: 'ugparekh@gmail.com',
        contact: '9821593603',
        gstin: null,
        billing_address: null,
        shipping_address: null,
        customer_name: 'utkarsh',
        customer_email: 'ugparekh@gmail.com',
        customer_contact: '9821593603',
      },
      order_id: 'order_L7tZvFX9kt1tly',
      line_items: [],
      payment_id: null,
      status: 'issued',
      expire_by: null,
      issued_at: 1674537327,
      paid_at: null,
      cancelled_at: null,
      expired_at: null,
      sms_status: 'sent',
      email_status: 'sent',
      date: 1674537327,
      terms: null,
      partial_payment: false,
      gross_amount: 1000,
      tax_amount: 0,
      taxable_amount: 0,
      amount: 1000,
      amount_paid: 0,
      amount_due: 1000,
      first_payment_min_amount: null,
      currency: 'INR',
      currency_symbol: '\u20b9',
      description: 'test 123',
      notes: [],
      comment: null,
      short_url: 'https://rzp.io/i/sd8YhFm3TP',
      view_less: true,
      billing_start: null,
      billing_end: null,
      type: 'link',
      group_taxes_discounts: false,
      supply_state_code: null,
      subscription_status: null,
      user_id: 'JnJMo8LVYAmKNh',
      created_at: 1674537327,
      idempotency_key: null,
      reminder_status: null,
      ref_num: null,
      auth_link_status: 'issued',
    },
  ],
};

export const fetchRegistrationLinksMock = () => {
  const url = '*/merchant/api/test/subscription_registration/auth_links';

  return rest.get(url, (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: RLResp,
      }),
      ctx.delay(50),
    );
  });
};
