export const refundsStatusVariantMap = {
  processing: {
    variant: 'notice',
    content: 'Razorpay is attempting to complete the refund. It can take upto 3-5 working days',
  },
  processed: {
    variant: 'positive',
    content:
      'Razorpay has completed the refund. After this, bank can take 5-7 working days to credit the amount to customer(s) account',
  },
  failed: {
    variant: 'negative',
    content: 'Due to customer(s) account error or bank-related issues',
  },
} as const;
