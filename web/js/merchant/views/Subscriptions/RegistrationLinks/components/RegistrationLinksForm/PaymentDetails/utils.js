import { isAmount } from 'common/utils/validators';
import { PAYMENT_METHODS } from 'merchant/views/Subscriptions/constants';

export const DOCUMENTATION_LINKS = {
  card: {
    title: 'Supported Banks & Networks',
    href: 'https://razorpay.com/docs/recurring-payments/bank-options/#card-networks',
  },
  upi: {
    title: 'Supported Banks & UPI Apps',
    href: 'https://razorpay.com/docs/recurring-payments/bank-options/#card-networks',
  },
  emandate: {
    title: 'Supported Banks & Methods',
    href: 'https://razorpay.com/docs/recurring-payments/bank-options/#emandate',
  },
};

export const getPaymentMethodOptions = (isEsignEnabled) => ({
  [PAYMENT_METHODS.CARD]: {
    method: 'Card',
    icon: 'card',
    desc: 'Via Credit and Debit Cards',
  },
  [PAYMENT_METHODS.NACH]: {
    method: 'NACH',
    icon: 'bank',
    desc: 'Via a NACH Form',
  },
  [PAYMENT_METHODS.EMANDATE]: {
    method: 'Emandate',
    icon: 'bank',
    desc: `${
      isEsignEnabled
        ? 'via Netbanking, Debit Card and Aadhaar on supported bank accounts'
        : 'Via Netbanking and Debit Card on supported bank accounts'
    }`,
  },
  [PAYMENT_METHODS.UPI]: {
    method: 'UPI',
    icon: 'upi',
    desc: 'Via UPI mandate',
  },
  [PAYMENT_METHODS.WALLET]: {
    method: 'Wallet',
    icon: 'wallet',
  },
});

export function checkIfAmount(value) {
  return !isAmount(Number(value)) && 'Invalid Amount';
}

export function checkIfAmountForFirstCharge(maxAmount, value) {
  const amount = Number(value);

  if (amount === 0) {
    return null;
  }

  if (amount > maxAmount) {
    return 'Amount should be less than or equal to Token Max Amount';
  }

  return !isAmount(value) && 'Invalid Amount';
}
