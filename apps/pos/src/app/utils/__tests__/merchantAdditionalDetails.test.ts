import { SUCCESS_MODULAR_RESPONSE } from 'apps/pos/src/services/mocks/fixtures/modularConfig';
import { MerchantModularOnboardingDetailsSuccessResponse } from 'apps/pos/src/app/types/modular';
import { getAdditionalDetailFields } from 'apps/pos/src/app/utils/merchantAdditionalDetails';

describe('getAdditionalDetailFields', () => {
  test('should return additional details field with correct acquirer preference', () => {
    const fields = getAdditionalDetailFields({
      modularConfig:
        SUCCESS_MODULAR_RESPONSE.merchantModularOnboardingDetailsAsSales as unknown as MerchantModularOnboardingDetailsSuccessResponse,
      omcValue: '',
    });

    const acquirerPrefFields = fields.find(
      (field) => field.name === 'additional_details_acquirer_preference_field',
    );
    expect(acquirerPrefFields?.meta?.options?.[0]).toStrictEqual({ label: 'SBI', value: 'sbi' });
  });
});
