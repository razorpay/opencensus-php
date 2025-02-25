import { Box } from '@razorpay/blade/components';
import errorService from '@razorpay/universe-cli/errorService';
import ErrorBoundary from '@razorpay/universe-cli/errorService/ErrorBoundary';
import PageError from 'apps/pos/src/app/components/PageError';
import { processFilesForModularSave } from 'apps/pos/src/app/components/SalesFileUpload/helper';
import { MODULES } from 'apps/pos/src/app/types/common';
import { FileItem } from 'apps/pos/src/app/types/fileUpload';
import { isKycQualified } from 'apps/pos/src/app/utils/merchantActivation';
import {
  populateNACHFormWithModularConfigData,
  checkIfNACHIsMandatory,
} from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/MerchantAdditionalDetails/additionalDetailsUtils';
import NACHFormEkyc, {
  NachFormKeyNames,
  NachFormObject,
} from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/MerchantAdditionalDetails/NACHFormEkyc/NACHFormEkyc';
import useOnboardingContext from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/providers/useOnboardingContext';
import React, { useState } from 'react';

interface TextChange {
  name?: string;
  value?: string;
}

const NACHFormEkycContainer = (): JSX.Element | null => {
  const { states, handlers } = useOnboardingContext();
  const { handleProceedToNextComponent, updateModularConfig } = handlers;
  const { isUpdateModularLoading, isModularLoading, merchantDetails, modularConfig } = states;
  const initialNachData = populateNACHFormWithModularConfigData(modularConfig);
  const [nachForm, setNachForm] = useState<NachFormObject>(initialNachData);
  const isFormDisabled = isKycQualified(merchantDetails?.activation?.posActivationStatus);
  const isNACHMandatory = checkIfNACHIsMandatory(modularConfig);

  const onNachTextAreaChange = (event: TextChange) => {
    setNachForm((prev) => {
      const newNACHForm: NachFormObject = JSON.parse(JSON.stringify(prev));
      newNACHForm[NachFormKeyNames.NACH_FORM_COMMENTS_FIELD] = event.value || '';
      return newNACHForm;
    });
  };

  const onNachFileUploadChange = (files: FileItem[]) => {
    setNachForm((prev) => {
      const newNACHForm: NachFormObject = JSON.parse(JSON.stringify(prev));
      newNACHForm[NachFormKeyNames.NACH_FORM_DOCUMENT_FIELD] = files;
      return newNACHForm;
    });
  };

  const onNachSubmitClick = () => {
    const payload: any = {
      ...nachForm,
    };
    payload[NachFormKeyNames.NACH_FORM_DOCUMENT_FIELD] = processFilesForModularSave(
      nachForm[NachFormKeyNames.NACH_FORM_DOCUMENT_FIELD],
    )[0];

    payload.modular_callback = handleProceedToNextComponent;
    updateModularConfig(payload);
  };

  return (
    <ErrorBoundary
      rank={errorService.ErrorRank.P0}
      tags={{ module: MODULES.ADDITIONAL_DETAILS }}
      fallbackComponent={
        <Box marginTop="spacing.8">
          <PageError
            title="Something went wrong!"
            description="We are facing some issues. Please try again later."
          />
        </Box>
      }
    >
      <NACHFormEkyc
        isModularLoading={isModularLoading}
        isUpdateModularLoading={isUpdateModularLoading}
        isFormDisabled={isFormDisabled}
        nachForm={nachForm}
        onNachTextAreaChange={onNachTextAreaChange}
        onNachFileUploadChange={onNachFileUploadChange}
        onNachSubmitClick={onNachSubmitClick}
        onNachSkipClick={handleProceedToNextComponent}
        isNACHMandatory={isNACHMandatory}
      />
    </ErrorBoundary>
  );
};

export default NACHFormEkycContainer;
