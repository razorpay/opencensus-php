import {
  ModularOnboardingField,
  ModularOnboardingFieldForDeviceCharges,
  ModularOnboardingFieldForOrderSummaryItem,
  ModularOnboardingFieldForArrayOfDocumentsUpload,
  ModularOnboardingFieldWithStringValue,
} from 'apps/pos/src/app/types/modular';

export function isStringValue(
  field: ModularOnboardingField | null,
): field is ModularOnboardingFieldWithStringValue {
  return typeof (field as ModularOnboardingFieldWithStringValue)?.stringValue === 'string';
}

export function isDeviceCharges(
  field: ModularOnboardingField | null,
): field is ModularOnboardingFieldForDeviceCharges {
  return (
    (field as ModularOnboardingFieldForDeviceCharges)?.orderSummary instanceof Object &&
    (field as ModularOnboardingFieldForDeviceCharges)?.orderSummary.hasOwnProperty(
      'advanceRentalCharge',
    )
  );
}

export function isOrderSummaryItem(
  field: ModularOnboardingField | null,
): field is ModularOnboardingFieldForOrderSummaryItem {
  return (
    Array.isArray((field as ModularOnboardingFieldForOrderSummaryItem)?.addedDevices) &&
    (field as ModularOnboardingFieldForOrderSummaryItem)?.addedDevices?.[0]?.hasOwnProperty(
      'deviceName',
    )
  );
}

export function isArrayOfDocumentsUpload(
  field: ModularOnboardingField | null,
): field is ModularOnboardingFieldForArrayOfDocumentsUpload {
  const documents = (field as ModularOnboardingFieldForArrayOfDocumentsUpload)
    ?.arrayOfDocumentsUploadValue;
  return Array.isArray(documents) && documents?.[0]?.hasOwnProperty('fileStoreId');
}
