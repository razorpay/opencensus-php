import {
  ModularOnboardingField,
  ModularOnboardingFieldForDeviceCharges,
  ModularOnboardingFieldForOrderSummaryItem,
  ModularOnboardingFieldForArrayOfDocumentsUpload,
  ModularOnboardingFieldForDocumentUpload,
  ModularOnboardingFieldWithStringValue,
  ModularOnboardingFieldWithBooleanValue,
  ModularOnboardingStepWithModularComponents,
  ModularOnboardingStep,
  ModularOnboardingFieldWithStringArrayValue,
} from 'apps/pos/src/app/types/modular';

export function isStringValue(
  field: ModularOnboardingField | null,
): field is ModularOnboardingFieldWithStringValue {
  return typeof (field as ModularOnboardingFieldWithStringValue)?.stringValue === 'string';
}

export function isNullValue(
  field: ModularOnboardingField | null | undefined,
): field is ModularOnboardingFieldWithStringValue {
  return (field as ModularOnboardingFieldWithStringValue)?.stringValue === null;
}

export function isBooleanValue(
  field: ModularOnboardingField | null,
): field is ModularOnboardingFieldWithBooleanValue {
  return typeof (field as ModularOnboardingFieldWithBooleanValue)?.booleanValue === 'boolean';
}

export function isStringArrayValue(
  field: ModularOnboardingField | null,
): field is ModularOnboardingFieldWithStringArrayValue {
  return (
    typeof (field as ModularOnboardingFieldWithStringArrayValue)?.stringArrayValue === 'object'
  );
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

export function isDocumentUpload(
  field: ModularOnboardingField | null,
): field is ModularOnboardingFieldForDocumentUpload {
  const documents = (field as ModularOnboardingFieldForDocumentUpload)?.documentUploadValue;
  return documents?.fileStoreId ? true : false;
}

export function isStepModularComponents(
  field: ModularOnboardingStep | null,
): field is ModularOnboardingStepWithModularComponents {
  const modularComponents = (field as ModularOnboardingStepWithModularComponents)
    ?.modularComponents;
  return modularComponents && modularComponents.length > 0;
}
