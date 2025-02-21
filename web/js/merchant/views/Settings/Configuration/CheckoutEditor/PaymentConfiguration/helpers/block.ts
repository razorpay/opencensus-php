import { PaymentConfigInstrument } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/types/index';

export function isMethodOnlyBlock(block: PaymentConfigInstrument) {
  const keys = Object.keys(block) ?? [];

  for (let i = 0; i < keys.length; i++) {
    const key = keys[i];
    if (key !== 'method' && block[key].length > 0) {
      return false;
    }
  }
  return true;
}
