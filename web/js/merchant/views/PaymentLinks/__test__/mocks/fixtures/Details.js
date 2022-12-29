import rolesList from 'merchant/helpers/permissions/roles-list';
import Details from 'merchant/views/PaymentLinks/PaymentLinks/Details/Details';
import PaymentDetail from 'merchant/views/PaymentLinks/PaymentLinks/Details/PaymentDetails';

jest.mock('common/ui/Toggler/ContentToggler', () => ({ children }) => <div>{children}</div>);

jest.mock('merchant/views/PaymentLinks/PaymentLinks/Details/ReminderStepsDetails', () => () => {
  <div>
    <div>11 May 20202</div>
    <div>12 May 20202</div>
  </div>;
});

jest.mock('merchant/views/PaymentLinks/PaymentLinks/components/Edit/EditExpiry', () => () => {
  return <div>Edit Expiry</div>;
});

jest.mock(
  'merchant/views/PaymentLinks/PaymentLinks/components/Edit/EditBusinessSegment',
  () => () => {
    return <div>Edit Business Segment</div>;
  },
);

export const generateUser = (data = {}) => {
  return {
    role: rolesList.RBL_AGENT,
    isPaymentlinksV2Enabled: data.isPaymentlinksV2Enabled || false,
    isPaymentLinkCreationV2Enabled: data.isPaymentLinkCreationV2Enabled || false,
    isInvoiceReceiptMandatory: true,
    isCustomNotesDropdownEnabled: data.isCustomNotesDropdownEnabled || false,
  };
};

export const paymentLinkConfig = (data = {}) => {
  return {
    accept_partial: false,
    amount: 100,
    amount_paid: 0,
    cancelled_at: 0,
    paid_at: 123340000,
    created_at: 1669786953,
    currency: 'INR',
    description: 'sasff',
    expire_by: data.expire_by || 12340000,
    expired_at: 12340000,
    first_min_partial_amount: 0,
    id: data?.paymentLinkId || 'plink_Km8evXT2XtGXc7',
    notes: [],
    order_id: 'order_Km8f0JjfeU3nbu',
    payments: [
      {
        id: '2',
        value: 20,
        payment_id: 'xVdegaTYs',
      },
      {
        id: '3',
        value: 30,
        payment_id: 'xVde99TYs',
      },
    ],
    reference_id: '',
    reminder_enable: false,
    reminders: {
      status: data.reminders?.status || 'active',
    },
    short_url: 'https://rzp.io/i/GN3SOBucBc',
    status: data.status || 'issued',
    updated_at: 1669786958,
    sms_status: data.sms_status || 'sent',
    upi_link: data.upiLink || false,
    user_id: 'F1fmi7bRy3m3I0',
    user: data.user || {
      id: 'F1fmi7bRy3m3I0',
      name: 'bhaskar mishra',
      email: 'bhaskar.mishra@razorpay.com',
      contact_mobile: '8004212709',
      contact_mobile_verified: true,
      email_verified: true,
      second_factor_auth: false,
      second_factor_auth_enforced: false,
      second_factor_auth_setup: true,
      org_enforced_second_factor_auth: false,
      restricted: false,
      confirmed: true,
      account_locked: false,
      created_at: 1591956191,
      signup_via_email: 1,
      isCustomNotesDropdownEnabled: false,
    },
    type: 'link',
    customer_details: {
      customer_email: 'bhaskar.mishra@razorpay.com',
      customer_name: 'bhaskar mishra',
      email_status: 'sent',
    },
    email_notify: '0',
    email_status: data.email_status || 'sent',
    sms_notify: '0',
    receipt: '',
    partial_payment: data.partial_payment || false,
    first_payment_min_amount: 0,
  };
};

export const App = (props = {}) => {
  return <Details {...props} />;
};

export const PaymentDetailsApp = (props = {}) => {
  return <PaymentDetail {...props} />;
};
