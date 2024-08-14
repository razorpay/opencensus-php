import { Merchant } from '@dashboard/shared-utils/graphql/graph-types';
import { checkIfKycComplete } from '../merchantActivation';

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
    } as unknown as Merchant;

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
    } as unknown as Merchant;

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
    } as unknown as Merchant;

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
    } as unknown as Merchant;

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
    } as unknown as Merchant;

    expect(checkIfKycComplete({ merchant })).toBe(false);
  });
});
