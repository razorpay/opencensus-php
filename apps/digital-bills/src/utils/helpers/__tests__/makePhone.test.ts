import makePhoneNumber from '@apps/digital-bills/src/utils/helpers/makePhone';

const mocks = {
  phone: {
    countryCode: '+91',
    number: '982212223',
  },
};

describe('makePhoneNumber', () => {
  test('should return the correct phone number', () => {
    const phoneNumber = makePhoneNumber(mocks.phone);
    expect(phoneNumber).toEqual('+91982212223');
  });
  test('should return phone number without country code', () => {
    const phoneNumber = makePhoneNumber({ countryCode: null, number: mocks.phone.number });
    expect(phoneNumber).toEqual('982212223');
  });
});
