import { getMockModularConfigWithAdditionalDetailsStep } from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/MerchantAdditionalDetails/mocks/fixtures';
import {
  checkIfNACHIsMandatory,
  createDefaultNACHForm,
} from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/MerchantAdditionalDetails/additionalDetailsUtils';
import { DeviceModel } from 'apps/pos/src/app/utils/deviceSelection';
import { NachFormKeyNames } from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/MerchantAdditionalDetails/NACHFormEkyc/NACHFormEkyc';

describe('checkIfNACHIsMandatory', () => {
  test('should return false if modular config is absent', () => {
    const result = checkIfNACHIsMandatory(null);
    expect(result).toBe(false);
  });

  test('should return false if no devices are added', () => {
    expect(
      checkIfNACHIsMandatory(
        getMockModularConfigWithAdditionalDetailsStep({ addedDevices: [] })
          .merchantModularOnboardingDetailsAsSales as any,
      ),
    ).toBe(false);
  });

  test('should return true if SOUNDBOX_KIT device is added with non-lifetime renewal', () => {
    const modularConfig = getMockModularConfigWithAdditionalDetailsStep({
      addedDevices: [{ deviceName: DeviceModel.SOUNDBOX_KIT, renewal: 'monthly' }],
    }).merchantModularOnboardingDetailsAsSales;
    expect(checkIfNACHIsMandatory(modularConfig as any)).toBe(true);
  });

  test('should return false if SOUNDBOX_KIT device is added with lifetime renewal', () => {
    const modularConfig = getMockModularConfigWithAdditionalDetailsStep({
      addedDevices: [{ deviceName: DeviceModel.SOUNDBOX_KIT, renewal: 'lifetime' }],
    }).merchantModularOnboardingDetailsAsSales;
    expect(checkIfNACHIsMandatory(modularConfig as any)).toBe(false);
  });
});

describe('createDefaultNACHForm', () => {
  test('should return default NACH form object with empty values', () => {
    const result = createDefaultNACHForm();
    expect(result).toEqual({
      [NachFormKeyNames.NACH_FORM_COMMENTS_FIELD]: '',
      [NachFormKeyNames.NACH_FORM_DOCUMENT_FIELD]: [],
    });
  });
});
