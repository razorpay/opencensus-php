import { rest } from 'msw';

// RL - Registration Link
const emandateRLDetailsResponse = {
  id: 'inv_L5EnvKoOpcBamw',
  entity: 'invoice',
  receipt: 'Receipt no.1222',
  invoice_number: 'Receipt no.1222',
  customer_id: 'cust_Idb4n9K5aONDON',
  customer_details: {
    id: 'cust_Idb4n9K5aONDON',
    name: 'Suhas More',
    email: 'dsuhas4u@gmail.com',
    contact: '9689649696',
    gstin: null,
    billing_address: null,
    shipping_address: null,
    customer_name: 'Suhas More',
    customer_email: 'dsuhas4u@gmail.com',
    customer_contact: '9689649696',
  },
  order_id: 'order_L5EnvhtUxe9q5c',
  line_items: [],
  payment_id: null,
  status: 'issued',
  expire_by: 2147483647,
  issued_at: 1673957056,
  paid_at: null,
  cancelled_at: null,
  expired_at: null,
  sms_status: 'sent',
  email_status: 'sent',
  date: 1673957056,
  terms: null,
  partial_payment: false,
  gross_amount: 0,
  tax_amount: 0,
  taxable_amount: 0,
  amount: 0,
  amount_paid: 0,
  amount_due: 0,
  first_payment_min_amount: null,
  currency: 'INR',
  currency_symbol: '\u20b9',
  description: 'test registration link',
  notes: { note_key_1: 'Tea. Earl grey. Hot.', note_key_2: 'Tea. Earl grey. Decaf.' },
  comment: null,
  short_url: 'https://rzp.io/i/lLbMKJS',
  view_less: true,
  billing_start: null,
  billing_end: null,
  type: 'link',
  group_taxes_discounts: false,
  supply_state_code: null,
  subscription_status: null,
  user_id: null,
  created_at: 1673957056,
  idempotency_key: null,
  reminder_status: null,
  auth_link_status: 'issued',
  subscription_registration: {
    id: 'subr_L5EnvFS4XtLJjc',
    method: 'emandate',
    entity: 'subscription_registration',
    notes: { note_key_1: 'Tea. Earl grey. Hot.', note_key_2: 'Tea. Earl grey. Decaf.' },
    recurring_status: null,
    failure_reason: null,
    currency: 'INR',
    max_amount: 5000,
    auth_type: 'netbanking',
    expire_at: 2147483647,
    bank_account: {
      id: 'ba_KuxUyVp0pc3Sbs',
      entity: 'bank_account',
      ifsc: 'HDFC0002844',
      bank_name: null,
      name: 'Suhas More',
      account_number: '50100168632173',
      account_type: 'savings',
      beneficiary_email: 'dsuhas4u@gmail.com',
      beneficiary_mobile: '9689649696',
    },
    status: 'created',
  },
};

const cancelLinkResponse = {
  id: 'inv_Kn4FQsp1Hz41Nl',
  entity: 'invoice',
  receipt: '1',
  invoice_number: '1',
  customer_id: 'cust_Kn4FQn1HZqd2n5',
  customer_details: {
    id: 'cust_Kn4FQn1HZqd2n5',
    name: 'Lakshmi Kanth Anakutty Mani',
    email: 'lakshmikanth.am@razorpay.com',
    contact: '7760027047',
    gstin: null,
    billing_address: null,
    shipping_address: null,
    customer_name: 'Lakshmi Kanth Anakutty Mani',
    customer_email: 'lakshmikanth.am@razorpay.com',
    customer_contact: '7760027047',
  },
  order_id: 'order_Kn4FQuczR1Y151',
  line_items: [],
  payment_id: null,
  status: 'cancelled',
  expire_by: null,
  issued_at: 1669989759,
  paid_at: null,
  cancelled_at: 1674040275,
  expired_at: null,
  sms_status: null,
  email_status: null,
  date: 1669989759,
  terms: null,
  partial_payment: false,
  gross_amount: 200,
  tax_amount: 0,
  taxable_amount: 0,
  amount: 200,
  amount_paid: 0,
  amount_due: 200,
  first_payment_min_amount: null,
  currency: 'INR',
  currency_symbol: '\u20b9',
  description: 'card',
  notes: [],
  comment: null,
  short_url: 'https://rzp.io/i/jNyUaeQ7s',
  view_less: true,
  billing_start: null,
  billing_end: null,
  type: 'link',
  group_taxes_discounts: false,
  supply_state_code: null,
  subscription_status: null,
  user_id: 'J2teHDRwlH1ZwI',
  created_at: 1669989759,
  idempotency_key: null,
  reminder_status: null,
  auth_link_status: 'cancelled',
  subscription_registration: {
    id: 'subr_Kn4FQpKmsvqXel',
    method: 'card',
    entity: 'subscription_registration',
    notes: [],
    recurring_status: null,
    failure_reason: null,
    currency: 'INR',
    max_amount: 2200,
    auth_type: null,
    expire_at: null,
  },
};

const nachRLDetailsResponse = {
  id: 'inv_L5f93vaP3fQZQW',
  entity: 'invoice',
  receipt: 'asdaa',
  invoice_number: 'asdaa',
  customer_id: 'cust_ISWiDtup1SYBfB',
  customer_details: {
    id: 'cust_ISWiDtup1SYBfB',
    name: 'Superstar',
    email: 'as@sda.com',
    contact: '7760027047',
    gstin: null,
    billing_address: null,
    shipping_address: null,
    customer_name: 'Superstar',
    customer_email: 'as@sda.com',
    customer_contact: '7760027047',
  },
  order_id: 'order_L5f94PHYpoKeD1',
  line_items: [],
  payment_id: null,
  status: 'issued',
  expire_by: null,
  issued_at: 1674049819,
  paid_at: null,
  cancelled_at: null,
  expired_at: null,
  sms_status: null,
  email_status: null,
  date: 1674049818,
  terms: null,
  partial_payment: false,
  gross_amount: 0,
  tax_amount: 0,
  taxable_amount: 0,
  amount: 0,
  amount_paid: 0,
  amount_due: 0,
  first_payment_min_amount: null,
  currency: 'INR',
  currency_symbol: '\u20b9',
  description: 'asda',
  notes: { title1: 'pair1' },
  comment: null,
  short_url: 'https://rzp.io/i/4fGb0UIIc',
  view_less: true,
  billing_start: null,
  billing_end: null,
  type: 'link',
  group_taxes_discounts: false,
  supply_state_code: null,
  subscription_status: null,
  user_id: 'HoWFJXWsZq6E2w',
  created_at: 1674049819,
  idempotency_key: null,
  reminder_status: null,
  ref_num: null,
  auth_link_status: 'issued',
  token: {
    method: 'nach',
    notes: { title1: 'pair1' },
    recurring_status: null,
    failure_reason: null,
    currency: 'INR',
    max_amount: 2000,
    auth_type: 'physical',
    expire_at: null,
    nach: {
      create_form: true,
      form_reference1: '123',
      form_reference2: '456',
      prefilled_form: 'https://rzp.io/i/TU2xZkrEGT',
      prefilled_form_transient: 'https://rzp.io/i/knBfYdWU6i',
      upload_form_url: 'https://rzp.io/i/4fGb0UIIc',
      description: 'asda',
    },
    bank_account: {
      ifsc: 'HDFC0005315',
      bank_name: 'HDFC Bank',
      name: 'Lakshmikanth',
      account_number: '1231231231',
      account_type: 'savings',
      beneficiary_email: 'as@sda.com',
      beneficiary_mobile: '7760027047',
    },
    first_payment_amount: 100,
  },
  nach_form_url: 'https://rzp.io/i/knBfYdWU6i',
  subscription_registration: {
    id: 'subr_L5f93Z3FSJcpN0',
    method: 'nach',
    entity: 'subscription_registration',
    notes: { title1: 'pair1' },
    recurring_status: null,
    failure_reason: null,
    currency: 'INR',
    max_amount: 2000,
    auth_type: 'physical',
    expire_at: null,
    status: 'created',
  },
  is_nach_form_uploaded: true,
};

const cancelRLErrorRes = {
  status_code: 400,
  success: false,
  errors: ['Authentication failed', 'Status Code: 400'],
};

export const fetchRLDetails = (isNach = false) => {
  const url = '*/merchant/api/test/subscription_registration/auth_links/*/internal';
  return rest.get(url, (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: isNach ? nachRLDetailsResponse : emandateRLDetailsResponse,
      }),
      ctx.delay(50),
    );
  });
};

export const cancelRL = () => {
  const url = '*/merchant/api/*/subscription_registration/auth_links/*/cancel';
  return rest.post(url, (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: cancelLinkResponse,
      }),
      ctx.delay(50),
    );
  });
};

export const cancelRLError = () => {
  const url = '*/merchant/api/*/subscription_registration/auth_links/*/cancel';
  return rest.post(url, (req, res, ctx) => {
    return res(ctx.status(200), ctx.json(cancelRLErrorRes), ctx.delay(50));
  });
};

export const sendEmail = () => {
  const url = '*/merchant/api/*/subscription_registration/auth_links/*/notify_by/email';
  return rest.post(url, (req, res, ctx) => {
    return res(ctx.status(200), ctx.json({ success: true }), ctx.delay(50));
  });
};

export const sendSms = () => {
  const url = '*/merchant/api/*/subscription_registration/auth_links/*/notify_by/sms';
  return rest.post(url, (req, res, ctx) => {
    return res(ctx.status(200), ctx.json({ success: true }), ctx.delay(50));
  });
};

export const sendEmailError = () => {
  const url = '*/merchant/api/*/subscription_registration/auth_links/*/notify_by/email';
  return rest.post(url, (req, res, ctx) => {
    return res(ctx.status(200), ctx.json({ success: false }), ctx.delay(50));
  });
};

export const sendSmsError = () => {
  const url = '*/merchant/api/*/subscription_registration/auth_links/*/notify_by/sms';
  return rest.post(url, (req, res, ctx) => {
    return res(ctx.status(200), ctx.json({ success: false }), ctx.delay(50));
  });
};

export const downloadSignedNachForm = () => {
  const url = '*/merchant/api/*/token.registration/paper_mandate/uploaded_form';
  return rest.get(url, (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          url: '/someurl',
        },
      }),
      ctx.delay(50),
    );
  });
};

export const downloadSignedNachFormWithError = () => {
  const url = '*/merchant/api/*/token.registration/paper_mandate/uploaded_form';
  return rest.get(url, (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 400,
        success: false,
        errors: ['error', 'Signed Form is not available'],
      }),
      ctx.delay(50),
    );
  });
};
