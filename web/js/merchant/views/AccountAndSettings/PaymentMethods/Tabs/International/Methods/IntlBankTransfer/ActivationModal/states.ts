import { useEffect } from 'react';
import { useMutation } from '@tanstack/react-query';

import { useActivationState } from './activationStates';
import { NOT_APPLICABLE, STEPS, VERIFIED } from './constants';
import { patchMerchantPurposeCode, postVKycLink } from './helpers';
import { ActivationModalProps } from './types';

export const useModal = ({
  step,
  iecCode,
  purposeCode,
  isEddVerified,
  purposeCodeDesc,
  promoterPanName,
  onDismiss,
}: Omit<ActivationModalProps, 'isOpen'>) => {
  const steps = useActivationState((state) => state.steps);
  const current = useActivationState((state) => state.current);
  const currentStepSubmitBtnText = useActivationState(
    (state) => state.steps[state.current].submitBtnText || 'Continue',
  );
  const isSavingForm = useActivationState((state) => state.isSavingForm);
  const setStepCompleted = useActivationState((state) => state.setStepCompleted);
  const setStepFields = useActivationState((state) => state.setStepFields);
  const validateStep = useActivationState((state) => state.validateStep);
  const setCurrent = useActivationState((state) => state.setCurrent);
  const setStepReadOnly = useActivationState((state) => state.setStepReadOnly);
  const setIsSavingForm = useActivationState((state) => state.setIsSavingForm);
  const setStepSubmitBtnText = useActivationState((state) => state.setStepSubmitBtnText);
  const hiddenSteps = useActivationState((state) => state.hiddenSteps);
  const setHiddenSteps = useActivationState((state) => state.setHiddenSteps);

  const { mutateAsync: savePurposeCode } = useMutation({
    mutationFn: patchMerchantPurposeCode,
    onError: () => {
      setIsSavingForm(false);
    },
  });

  const { mutateAsync: generateLink } = useMutation({
    mutationFn: postVKycLink,
    onError: () => {
      setIsSavingForm(false);
    },
  });

  const handleContinueClick = async () => {
    if (steps[current].submitBtnText === 'Close') {
      onDismiss();
      return;
    }

    if (!validateStep(current)) {
      return;
    }

    setIsSavingForm(true);

    if (current === STEPS.PURPOSE_CODE) {
      setCurrent(STEPS.IEC_CODE);
    }

    if (current === STEPS.IEC_CODE) {
      const isReadOnlySteps =
        steps[STEPS.PURPOSE_CODE].isReadOnly && steps[STEPS.IEC_CODE].isReadOnly;

      if (!isReadOnlySteps) {
        const { purposeCode, purposeCodeDesc } = steps[STEPS.PURPOSE_CODE].fields;
        const { iecCode } = steps[STEPS.IEC_CODE].fields;
        await savePurposeCode({
          purpose_code: purposeCode,
          purpose_code_desc: purposeCodeDesc,
          iec_code: iecCode,
        });
      }

      if (isEddVerified) {
        // if edd is verified, close the modal and trigger activation of virtual accounts
        onDismiss(true);
        return;
      }
      setCurrent(STEPS.VIDEO_KYC);
    }

    if (current === STEPS.INTERNATIONAL_DETAILS) {
      setCurrent(STEPS.VIDEO_KYC);
    }

    if (current === STEPS.VIDEO_KYC) {
      // generate web link and redirect to the link
      const { fields } = steps[current];
      let { webLink } = fields;
      if (!webLink) {
        // if web link is not available, generate it
        const { data } = await generateLink(fields.promoterPanName);

        webLink = data?.details?.weblink ?? '';

        setStepFields(STEPS.VIDEO_KYC, {
          webLink,
        });
      }

      if (fields.owner === 'yes' && webLink) {
        // Open link into new tab and close the modal
        window.open(webLink, '_blank');
        onDismiss();
      }

      if (fields.owner === 'no' && webLink) {
        setStepSubmitBtnText(STEPS.VIDEO_KYC, 'Close');
      }
    }

    setIsSavingForm(false);
  };

  useEffect(() => {
    const stepsToBeHidden: number[] = [];
    if (purposeCode && iecCode) {
      setStepReadOnly(STEPS.INTERNATIONAL_DETAILS, true);
      setStepCompleted(STEPS.INTERNATIONAL_DETAILS, true);
      setStepFields(STEPS.INTERNATIONAL_DETAILS, {
        iecCode,
        purposeCode,
        purposeCodeDesc: purposeCodeDesc || '',
        acceptTnc: isEddVerified ? VERIFIED : '',
      });
      stepsToBeHidden.push(STEPS.PURPOSE_CODE);
      stepsToBeHidden.push(STEPS.IEC_CODE);
    }

    if (purposeCode) {
      setStepReadOnly(STEPS.PURPOSE_CODE, true);
      setStepCompleted(STEPS.PURPOSE_CODE, true);
      setStepFields(STEPS.PURPOSE_CODE, {
        purposeCode,
        purposeCodeDesc: '',
      });
    }

    if (iecCode) {
      setStepReadOnly(STEPS.IEC_CODE, true);
      setStepCompleted(STEPS.IEC_CODE, true);
      setStepFields(STEPS.IEC_CODE, {
        iecCodeOption: iecCode === NOT_APPLICABLE ? NOT_APPLICABLE : 'yes',
        iecCode,
        acceptTnc: isEddVerified ? VERIFIED : '',
      });
    }

    if (isEddVerified) {
      stepsToBeHidden.push(STEPS.VIDEO_KYC);
    }

    if (stepsToBeHidden.length) {
      setHiddenSteps(stepsToBeHidden);
    }
  }, [
    iecCode,
    isEddVerified,
    purposeCode,
    purposeCodeDesc,
    setHiddenSteps,
    setStepCompleted,
    setStepFields,
    setStepReadOnly,
  ]);

  useEffect(() => {
    if (step) {
      setCurrent(step);
    }
  }, [setCurrent, step]);

  useEffect(() => {
    if (promoterPanName) {
      setStepFields(STEPS.VIDEO_KYC, {
        promoterPanName,
        webLink: '',
      });
    }
  }, [promoterPanName, setStepFields]);

  return {
    steps,
    current,
    hiddenSteps,
    isSavingForm,
    currentStepSubmitBtnText,
    handleContinueClick,
  };
};
