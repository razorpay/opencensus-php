import { PaymentConfigInstrument } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/types/index';

export function getCustomBlockDescription(instruments: PaymentConfigInstrument[]) {
  const noOfInstruments = instruments?.length;
  if (noOfInstruments === 0) {
    return 'No payment options added';
  }
  if (noOfInstruments === 1) {
    return '1 payment option added';
  }
  return `${noOfInstruments} payment options added`;
}
