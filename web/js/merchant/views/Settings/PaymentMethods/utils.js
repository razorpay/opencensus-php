import { DISABLED_INSTRUMENT, GETSIMPL } from 'merchant/views/Settings/PaymentMethods/constants';

export const getDisabledInstruments = (user) => {
  if (user?.isOptimizerEnabled) {
    return DISABLED_INSTRUMENT.filter((instrument) => instrument !== GETSIMPL);
  }
  return DISABLED_INSTRUMENT;
};
