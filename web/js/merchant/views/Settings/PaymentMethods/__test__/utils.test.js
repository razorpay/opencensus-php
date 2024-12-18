import { getDisabledInstruments } from 'merchant/views/Settings/PaymentMethods/utils';
import { DISABLED_INSTRUMENT, GETSIMPL } from 'merchant/views/Settings/PaymentMethods/constants';

describe('getDisabledInstruments', () => {
  test('should return DISABLED_INSTRUMENT without GETSIMPL when user is optimizer enabled', () => {
    const user = { isOptimizerEnabled: true };
    const disabledInstruments = getDisabledInstruments(user);

    expect(disabledInstruments).toEqual(
      DISABLED_INSTRUMENT.filter((instrument) => instrument !== GETSIMPL),
    );
  });

  test('should return DISABLED_INSTRUMENT when user is not optimizer enabled', () => {
    const user = { isOptimizerEnabled: false };
    const disabledInstruments = getDisabledInstruments(user);

    expect(disabledInstruments).toEqual(DISABLED_INSTRUMENT);
  });
});
