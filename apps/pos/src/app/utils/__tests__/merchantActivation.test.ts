import { DashboardGraphQLMerchant } from '@libs/shared-types';
import { checkIfKycComplete, isKycQualified } from '../merchantActivation';

const COMMON_PAYMENT_ACCEPTANCE_CHANNELS = {
  websites: {
    urls: [{ value: 'https://example.com' }],
  },
  android: {
    urls: [{ value: 'https://play.google.com/store/apps/details?id=example' }],
  },
  ios: {
    urls: [{ value: 'https://apps.apple.com/us/app/example/id1234567890' }],
  },
  socialMedia: {
    socialMediaUrls: ['https://twitter.com/example'],
  },
};

describe('checkIfKycComplete', () => {
  test('should return true when the merchant has online presence and L2 form is submitted', () => {
    const merchant = {
      business: {
        paymentAcceptanceChannels: COMMON_PAYMENT_ACCEPTANCE_CHANNELS,
      },
      document: {
        shopFront: { values: [] },
        shopInterior: { values: [] },
      },
      activation: {
        isFormSubmitted: true,
      },
    } as unknown as DashboardGraphQLMerchant;

    expect(checkIfKycComplete({ merchant })).toBe(true);
  });

  test('should return true when the merchant has shop images and L2 form is submitted but no online presence', () => {
    const merchant = {
      business: {
        paymentAcceptanceChannels: {},
      },
      document: {
        shopFront: { values: ['shopFrontImage'] },
        shopInterior: { values: ['shopInteriorImage'] },
      },
      activation: {
        isFormSubmitted: true,
      },
    } as unknown as DashboardGraphQLMerchant;

    expect(checkIfKycComplete({ merchant })).toBe(true);
  });

  test('should return false when the merchant has no online presence, shop images, and L2 form is not submitted', () => {
    const merchant = {
      business: {
        paymentAcceptanceChannels: {},
      },
      document: {
        shopFront: { values: [] },
        shopInterior: { values: [] },
      },
      activation: {
        isFormSubmitted: false,
      },
    } as unknown as DashboardGraphQLMerchant;

    expect(checkIfKycComplete({ merchant })).toBe(false);
  });

  test('should return false when the merchant has online presence but L2 form is not submitted', () => {
    const merchant = {
      business: {
        paymentAcceptanceChannels: COMMON_PAYMENT_ACCEPTANCE_CHANNELS,
      },
      document: {
        shopFront: { values: [] },
        shopInterior: { values: [] },
      },
      activation: {
        isFormSubmitted: false,
      },
    } as unknown as DashboardGraphQLMerchant;

    expect(checkIfKycComplete({ merchant })).toBe(false);
  });

  test('should return false when the merchant has shop images but L2 form is not submitted', () => {
    const merchant = {
      business: {
        paymentAcceptanceChannels: {},
      },
      document: {
        shopFront: { values: ['shopFrontImage'] },
        shopInterior: { values: ['shopInteriorImage'] },
      },
      activation: {
        isFormSubmitted: false,
      },
    } as unknown as DashboardGraphQLMerchant;

    expect(checkIfKycComplete({ merchant })).toBe(false);
  });
});

describe('isKycQualified', () => {
  test('returns true for "ACTIVATED"', () => {
    expect(isKycQualified('ACTIVATED')).toBe(true);
  });

  test('returns true for "REJECTED"', () => {
    expect(isKycQualified('REJECTED')).toBe(true);
  });

  test('returns true for "KYC_QUALIFIED_STB"', () => {
    expect(isKycQualified('KYC_QUALIFIED_STB')).toBe(true);
  });

  test('returns true for "NEEDS_CLARIFICATION"', () => {
    expect(isKycQualified('NEEDS_CLARIFICATION')).toBe(false);
  });

  test('returns false for an unknown status', () => {
    expect(isKycQualified('UNKNOWN_STATUS')).toBe(false);
  });

  test('returns false for an empty string', () => {
    expect(isKycQualified('')).toBe(false);
  });

  test('returns false for undefined input', () => {
    expect(isKycQualified(undefined)).toBe(false);
  });

  test('returns false for null input', () => {
    expect(isKycQualified(null)).toBe(false);
  });

  test('returns false for non-string input (number)', () => {
    expect(isKycQualified(123)).toBe(false);
  });
});
