import {
  getSlugSuggestions,
  getSlugAvailability,
  calculateLoaderSize,
  getPHProductOnboarding,
  isHandleAvailableForMerchant,
} from 'merchant/views/PaymentHandle/utils';
import { isStringAlphabetAndNumberOnly } from 'common/utils/rzp-utils';
import { getSlugSuggestionsApi as slugSuggestions } from 'merchant/reducers/paymentHandle/api';
import { SHIMMER_BAR_VARIANTS, ERROR_MAPPING } from 'merchant/views/PaymentHandle/constants';

const MOBILE = 'mobile';
const DESKTOP = 'desktop';

jest.mock('merchant/reducers/paymentHandle/api', () => ({
  checkSlugAvailabilityApi: jest.fn(),
}));

jest.mock('merchant/reducers/paymentHandle/api', () => ({
  getSlugSuggestionsApi: jest.fn(),
}));

jest.mock('merchant/components/OnBoarding', () => ({
  getOnBoardingDataFromLocalState: jest.fn().mockReturnValue({ isEnabled: true }),
}));

describe('PaymentHandle functions', () => {
  describe('calculateLoaderSize', () => {
    it('should return correct loader size for desktop and large variant', () => {
      const size = calculateLoaderSize(DESKTOP, SHIMMER_BAR_VARIANTS.LARGE);
      expect(size).toEqual({ width: '432px', height: '32px' });
    });

    it('should return correct loader size for mobile and large variant', () => {
      const size = calculateLoaderSize(MOBILE, SHIMMER_BAR_VARIANTS.LARGE);
      expect(size).toEqual({ width: '240px', height: '32px' });
    });

    it('should return correct loader size for desktop and medium variant', () => {
      const size = calculateLoaderSize(DESKTOP, SHIMMER_BAR_VARIANTS.MEDIUM);
      expect(size).toEqual({ width: '432px', height: '24px' });
    });

    it('should return correct loader size for mobile and medium variant', () => {
      const size = calculateLoaderSize(MOBILE, SHIMMER_BAR_VARIANTS.MEDIUM);
      expect(size).toEqual({ width: '200px', height: '24px' });
    });

    it('should return correct loader size for desktop and small variant', () => {
      const size = calculateLoaderSize(DESKTOP, SHIMMER_BAR_VARIANTS.SMALL);
      expect(size).toEqual({ width: '290px', height: '24px' });
    });

    it('should return correct loader size for mobile and small variant', () => {
      const size = calculateLoaderSize(MOBILE, SHIMMER_BAR_VARIANTS.SMALL);
      expect(size).toEqual({ width: '180px', height: '24px' });
    });

    it('should return 0px for width and height for unknown variant', () => {
      const size = calculateLoaderSize('desktop', 'UNKNOWN');
      expect(size).toEqual({ width: '0px', height: '0px' });
    });
  });

  describe('isHandleAvailableForMerchant', () => {
    it('should return false if handle is invalid', () => {
      const result = isHandleAvailableForMerchant([ERROR_MAPPING.HANDLE_INVALID]);
      expect(result).toBe(false);
    });

    it('should return true if handle is valid', () => {
      const result = isHandleAvailableForMerchant(['VALID_HANDLE']);
      expect(result).toBe(true);
    });
  });

  describe('isStringAlphabetAndNumberOnly', () => {
    it('should return false if string contains special characters', () => {
      const result = isStringAlphabetAndNumberOnly('abc!@#$%');
      expect(result).toBe(true);
    });

    it('should return true if string contains only alphabets and numbers', () => {
      const result = isStringAlphabetAndNumberOnly('abc123');
      expect(result).toBe(false);
    });
  });

  describe('getSlugAvailability', () => {
    it('should return false if an error occurs', async () => {
      const mockCheckSlugAvailability = jest.fn().mockRejectedValue(new Error('Error'));
      jest.mock('merchant/reducers/paymentHandle/api', () => ({
        checkSlugAvailabilityApi: mockCheckSlugAvailability,
      }));

      const result = await getSlugAvailability('test-slug');
      expect(result).toBe(false);
    });

    it('should return suggestions data', async () => {
      slugSuggestions.mockResolvedValue({
        data: {
          suggestions: ['suggestion1', 'suggestion2', 'suggestion3'],
        },
      });

      const suggestions = await getSlugSuggestions();
      expect(suggestions).toEqual(['suggestion1', 'suggestion2', 'suggestion3']);
    });

    it('should return an empty array when there is an error', async () => {
      slugSuggestions.mockRejectedValue(new Error('error'));
      const suggestions = await getSlugSuggestions();
      expect(suggestions).toEqual([]);
    });
  });

  describe('getPHProductOnboarding', () => {
    it('should return true if user is not payment handle enabled', () => {
      const user = {
        isPaymentHandleEnabled: true,
      };
      const result = getPHProductOnboarding(user);
      expect(result).toBe(true);
    });

    it('should return false if user is not payment handle enabled', () => {
      const user = {
        isPaymentHandleEnabled: false,
      };
      const result = getPHProductOnboarding(user);
      expect(result).toBe(false);
    });
  });
});
