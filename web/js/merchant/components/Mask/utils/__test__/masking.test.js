import { getMaskedEmail, getMaskedContact } from 'merchant/components/Mask/utils/masking';

describe('merchant/components/Mask/utils/masking', () => {
  test('should mask email', () => {
    const maskedEmail = getMaskedEmail('test@razorpay.com');

    expect(maskedEmail).toEqual('t**t@razorpay.com');
  });

  test('should mask phone number', () => {
    const maskedPhoneNo = getMaskedContact('+911234567890');

    expect(maskedPhoneNo).toEqual('+9112******90');
  });

  test('should return empty string when email is passed as empty string', () => {
    const maskedEmail = getMaskedEmail('');

    expect(maskedEmail).toEqual('');
  });

  test('should return empty string when phone number is passed as empty string', () => {
    const maskedPhoneNo = getMaskedContact('');

    expect(maskedPhoneNo).toEqual('');
  });

  test('should return empty string when no email is passed', () => {
    const maskedEmail = getMaskedEmail();

    expect(maskedEmail).toEqual('');
  });

  test('should return empty string when no phone number is passed', () => {
    const maskedPhoneNo = getMaskedContact();

    expect(maskedPhoneNo).toEqual('');
  });

  test('should mask first two letters when no phone number does not contain +91', () => {
    const maskedPhoneNo = getMaskedContact('1234567890');

    expect(maskedPhoneNo).toEqual('12******90');
  });
});
