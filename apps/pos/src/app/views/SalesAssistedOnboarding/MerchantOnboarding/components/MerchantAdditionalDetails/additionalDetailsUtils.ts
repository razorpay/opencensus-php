import { MODULAR_DEVICE_FIELDS } from 'apps/pos/src/app/types/DeviceSelection';
import { MODULAR_ADDITIONAL_DETAILS_FIELDS } from 'apps/pos/src/app/types/MerchantAdditionalDetails';
import { MerchantModularOnboardingDetailsSuccessResponse } from 'apps/pos/src/app/types/modular';
import { getComponentFromStep } from 'apps/pos/src/app/utils/modularConfig';
import {
  isArrayOfDocumentsUpload,
  isDocumentUpload,
  isStringArrayValue,
  isStringValue,
} from 'apps/pos/src/app/utils/modularTypeResolvers';
import {
  NachFormKeyNames,
  NachFormObject,
} from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/MerchantAdditionalDetails/NACHFormEkyc/NACHFormEkyc';

export const createDefaultNACHForm = (): NachFormObject => {
  return {
    [NachFormKeyNames.NACH_FORM_COMMENTS_FIELD]: '',
    [NachFormKeyNames.NACH_FORM_DOCUMENT_FIELD]: [],
  };
};

export const populateNACHFormWithModularConfigData = (
  modularConfig: MerchantModularOnboardingDetailsSuccessResponse | null,
) => {
  const newForm: NachFormObject = createDefaultNACHForm();
  if (!modularConfig) return newForm;

  const component = getComponentFromStep({
    modularConfig,
    step: MODULAR_ADDITIONAL_DETAILS_FIELDS.ADDITIONAL_DETAILS_STEP,
    component: MODULAR_DEVICE_FIELDS.NACH_FORM_COMPONENT,
  });

  if (component) {
    const fields = component?.fields;
    fields?.forEach((f) => {
      if (f && f.name && f.meta) {
        if (f.name === NachFormKeyNames.NACH_FORM_DOCUMENT_FIELD) {
          if (isStringArrayValue(f)) newForm[f.name] = f.stringArrayValue;
          else if (isArrayOfDocumentsUpload(f)) {
            newForm[f.name] = f.arrayOfDocumentsUploadValue;
          } else if (isDocumentUpload(f)) {
            newForm[f.name] = [f.documentUploadValue];
          }
        } else if (f.name === NachFormKeyNames.NACH_FORM_COMMENTS_FIELD && isStringValue(f))
          newForm[f.name] = f.stringValue;
      }
    });
  }
  return newForm;
};
