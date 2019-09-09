import { isAmount } from 'rzp/utils/validators';

export function checkIfAmount(value) {
  return !isAmount(Number(value)) && 'Invalid Amount';
}

export function checkIfAmountForFirstCharge(maxAmount, value) {
  const amount = Number(value);

  if (amount === 0) {
    return null;
  }

  if (amount > maxAmount) {
    return 'Amount is should be less than or equal Token Max Amount';
  }

  return !isAmount(value) && 'Invalid Amount';
}
