import Withdrawal from 'merchant/models/Capital/Withdrawals';

export function setMerchantPreferences(payload) {
  const withdrawal = new Withdrawal();
  return withdrawal.setMerchantPreferences(payload);
}
