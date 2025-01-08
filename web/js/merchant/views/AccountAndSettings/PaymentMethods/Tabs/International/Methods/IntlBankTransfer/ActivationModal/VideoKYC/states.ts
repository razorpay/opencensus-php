import copyToClipboard from 'common/utils/copyToClipboard';
import { useActivationState } from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/Methods/IntlBankTransfer/ActivationModal/activationStates';

import { STEPS } from './constants';

export const useVideoKyc = () => {
  const promoterPanName = useActivationState(
    (state) => state.steps[STEPS.VIDEO_KYC].fields.promoterPanName,
  );
  const owner = useActivationState((state) => state.steps[STEPS.VIDEO_KYC].fields.owner);
  const webLink = useActivationState((state) => state.steps[STEPS.VIDEO_KYC].fields.webLink);
  const isSavingForm = useActivationState((state) => state.isSavingForm);
  const validationState = useActivationState(
    (state) => state.steps[STEPS.VIDEO_KYC].validationState,
  );
  const setStepFields = useActivationState((state) => state.setStepFields);
  const setStepValidationState = useActivationState((state) => state.setStepValidationState);
  const setStepSubmitBtnText = useActivationState((state) => state.setStepSubmitBtnText);

  const handleSelectOption = ({ value }: { value: string }): void => {
    setStepFields(STEPS.VIDEO_KYC, {
      owner: value,
    });

    let btnText = 'Generate V-KYC Link';

    if (value === 'yes') {
      btnText = 'Start Video KYC';
    } else if (webLink) {
      btnText = 'Close';
    }

    setStepSubmitBtnText(STEPS.VIDEO_KYC, btnText);
    setStepValidationState(STEPS.VIDEO_KYC, 'owner', {
      state: 'none',
      errorText: '',
    });
  };

  const handleCopyLink = () => {
    copyToClipboard(webLink);
  };

  return {
    owner,
    webLink,
    isSavingForm,
    validationState,
    promoterPanName,
    handleCopyLink,
    handleSelectOption,
  };
};
