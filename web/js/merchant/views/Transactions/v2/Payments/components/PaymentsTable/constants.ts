export const paymentStatusVariantMap = {
  created: {
    variant: 'notice',
    content:
      'Customer payment details have been sent to Razorpay. The payment has not been processed yet',
  },
  captured: {
    variant: 'positive',
    content:
      'This is the amount collected in your Razorpay balance and will be deposited in your bank account after deductions and adjustments as per your settlement cycle',
  },
  authenticated: {
    variant: 'neutral',
    content:
      "This is amount that was deducted from the customer(s) account after successful authentication. It'll be added to your Razorpay balance after being captured",
  },
  authorized: {
    variant: 'neutral',
    content:
      "This is amount that was deducted from the customer(s) account after successful authentication. It'll be added to your Razorpay balance after being captured",
  },
  failed: {
    variant: 'negative',
    content:
      'These are payments that were unsuccessful due to technical, network, bank, business, or customer related issues (they will need to be retried by the customer)',
  },
  refunded: {
    variant: 'information',
    content: 'This is the amount reversed to a customer(s) bank account',
  },
  pending: {
    variant: 'blue',
    content:
      "These are payments with Cash on Delivery (COD) as the customer's chosen mode of payment",
  },
} as const;
