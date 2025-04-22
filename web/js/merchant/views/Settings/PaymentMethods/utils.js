import { DISABLED_INSTRUMENT, GETSIMPL, PHONEPE } from 'merchant/views/Settings/PaymentMethods/constants';

export const getDisabledInstruments = (user) => {
  let disabledInstruments = DISABLED_INSTRUMENT;

  // Exclude PhonePe for specific merchant
  if (user?.merchant?.id === 'H4haEBYeiS12pR') {
    disabledInstruments = disabledInstruments.filter(instrument => instrument !== PHONEPE);
  }

  // Handle optimizer case
  if (user?.isOptimizerEnabled) {
    disabledInstruments = disabledInstruments.filter((instrument) => instrument !== GETSIMPL);
  }
  return disabledInstruments;
};