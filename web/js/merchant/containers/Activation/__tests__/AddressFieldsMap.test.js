import {
  isPinValid,
  getInvalidErrorCodeMsg,
  COUNTY_PINCODE_NUMBER_MAP,
} from 'merchant/containers/Activation/AddressFieldsMap';
import User from 'merchant/models/User';
import { getUser } from 'merchant/store';

jest.mock('merchant/store', () => ({
  __esModule: true,
  ...jest.requireActual('merchant/store'),
  getUser: jest.fn(),
}));

describe('isPinValid function', () => {
  test('should return 0 if the pin is valid for Malaysia', () => {
    const newUser = new User({ merchant: { country_code: 'MY' } });
    getUser.mockReturnValue(newUser);

    const validMalaysiaPin = '12345';
    expect(isPinValid(validMalaysiaPin)).toBe(0);
  });

  test('should return error message if the pin is invalid for Malaysia', () => {
    const invalidMalaysiaPin = '1234';
    const newUser = new User({ merchant: { country_code: 'MY' } });

    getUser.mockReturnValue(newUser);
    expect(isPinValid(invalidMalaysiaPin)).toBe(
      getInvalidErrorCodeMsg(COUNTY_PINCODE_NUMBER_MAP.MY),
    );
  });

  test('should return error message if the pin is invalid for India', () => {
    const invalidOtherCountryPin = '123';
    const newUser = new User({ merchant: { country_code: 'IN' } });
    getUser.mockReturnValue(newUser);
    expect(isPinValid(invalidOtherCountryPin)).toBe(
      getInvalidErrorCodeMsg(COUNTY_PINCODE_NUMBER_MAP.IN),
    );
  });

  test('should return 0 if the pin is valid for India', () => {
    const newUser = new User({ merchant: { country_code: 'IN' } });
    getUser.mockReturnValue(newUser);

    const validOtherCountryPin = '123456';
    expect(isPinValid(validOtherCountryPin)).toBe(0);
  });
});

describe('getInvalidErrorCodeMsg', () => {
  test('should return correct error message based on pin length', () => {
    const invalidPinLengthMsgFor5 = getInvalidErrorCodeMsg(5);
    const invalidPinLengthMsgFor6 = getInvalidErrorCodeMsg(6);

    expect(invalidPinLengthMsgFor5).toBe('Please enter 5 digit pincode.');
    expect(invalidPinLengthMsgFor6).toBe('Please enter 6 digit pincode.');
  });

  test('should handle other pin lengths gracefully', () => {
    const invalidPinLengthMsgForOther = getInvalidErrorCodeMsg(4);

    expect(invalidPinLengthMsgForOther).toBe('Please enter 4 digit pincode.');
  });
});
