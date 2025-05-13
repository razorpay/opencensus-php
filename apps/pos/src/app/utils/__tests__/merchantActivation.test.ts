import { DashboardGraphQLMerchant } from '@libs/shared-types';
import { checkIfKycComplete, isKycActivatedOrRejected } from '../merchantActivation';

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

describe('isKycActivatedOrRejected', () => {
  test('returns true for "ACTIVATED"', () => {
    expect(isKycActivatedOrRejected('ACTIVATED')).toBe(true);
  });

  test('returns true for "REJECTED"', () => {
    expect(isKycActivatedOrRejected('REJECTED')).toBe(true);
  });

  test('returns false for "KYC_QUALIFIED_STB"', () => {
    expect(isKycActivatedOrRejected('KYC_QUALIFIED_STB')).toBe(false);
  });

  test('returns false for "NEEDS_CLARIFICATION"', () => {
    expect(isKycActivatedOrRejected('NEEDS_CLARIFICATION')).toBe(false);
  });

  test('returns false for an unknown status', () => {
    expect(isKycActivatedOrRejected('UNKNOWN_STATUS')).toBe(false);
  });

  test('returns false for an empty string', () => {
    expect(isKycActivatedOrRejected('')).toBe(false);
  });

  test('returns false for undefined input', () => {
    expect(isKycActivatedOrRejected(undefined)).toBe(false);
  });

  test('returns false for null input', () => {
    expect(isKycActivatedOrRejected(null)).toBe(false);
  });
});
