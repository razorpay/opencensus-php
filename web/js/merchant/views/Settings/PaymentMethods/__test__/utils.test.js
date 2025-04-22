import { getDisabledInstruments } from 'merchant/views/Settings/PaymentMethods/utils';
import { DISABLED_INSTRUMENT, GETSIMPL, PHONEPE } from 'merchant/views/Settings/PaymentMethods/constants';

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
  test('should exclude PhonePe for merchant H4haEBYeiS12pR', () => {
    const user = { 
      isOptimizerEnabled: false,
      merchant: { id: 'H4haEBYeiS12pR' }
    };
    const disabledInstruments = getDisabledInstruments(user);

    expect(disabledInstruments).not.toContain(PHONEPE);
  });

  test('should exclude both PhonePe and GETSIMPL for merchant H4haEBYeiS12pR with optimizer enabled', () => {
    const user = { 
      isOptimizerEnabled: true,
      merchant: { id: 'H4haEBYeiS12pR' }
    };
    const disabledInstruments = getDisabledInstruments(user);

    expect(disabledInstruments).not.toContain(PHONEPE);
    expect(disabledInstruments).not.toContain(GETSIMPL);
  });
});
