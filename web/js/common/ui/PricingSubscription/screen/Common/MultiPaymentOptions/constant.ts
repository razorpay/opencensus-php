const MODAL_TYPE = {
  INSUFFICIENT_BALANCE: 'INSUFFICIENT_BALANCE',
  SUFFICIENT_BALANCE: 'SUFFICIENT_BALANCE',
  CHECKOUT_PAYMENT: 'CHECKOUT_PAYMENT',
};

const MODAL_CONTENT = {
  [MODAL_TYPE.INSUFFICIENT_BALANCE]: {
    header:
      'Request processed. Awaiting your settlement balance to be sufficient for this transaction. New Pricing plan will be active in 24-48 hrs post payment realization.',
    subHeader: "Upon plan activation, we'll send you further details and benefits via e-mail.",
  },
  [MODAL_TYPE.SUFFICIENT_BALANCE]: {
    header:
      'Payment received. New Pricing plan will be active in 24-48 hrs post payment realization.',
    subHeader: "Upon plan activation, we'll send you further details and benefits via e-mail.",
  },
  [MODAL_TYPE.CHECKOUT_PAYMENT]: {
    header:
      'Your payment has been received, and your updated Pricing plan will be implemented within 24-48 hours post payment realization.',
    subHeader: "Upon plan activation, we'll send you further details and benefits via e-mail.",
  },
};

export { MODAL_CONTENT, MODAL_TYPE };
