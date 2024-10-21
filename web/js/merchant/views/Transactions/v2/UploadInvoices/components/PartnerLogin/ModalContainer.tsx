import React, { useState, useEffect, useContext } from 'react';
import { useFormikContext } from 'formik';
import {
  ArrowRightIcon,
  Box,
  Button,
  Modal,
  ModalBody,
  ModalHeader,
  ModalFooter,
} from '@razorpay/blade/components';

import { OnboardingDetailsContext } from 'merchant/views/Transactions/v2/UploadInvoices/context/OnboardingDetailsContext';
import { PopupContext } from 'merchant/views/Transactions/v2/UploadInvoices/context/PopupContext';
import { MODAL_TYPES } from 'merchant/views/Transactions/v2/UploadInvoices/constants';

import OtpInput from './OtpInput';
import SetupSuccess from './SetupSuccess';
import LoginDetails from './LoginDetails';
import { onboardPartner, verifyOtp } from './services';
import { FORM_STEPS, ONBOARDING_PARTNERS, ONBOARDING_STATUS } from './constants';
import { getButtonText, getFormStep, getModalTitle, isButtonDisabled } from './utils';
import { FormikValues, ModalConatinerProps } from './types';

const ModalContainer = ({
  partner,
  status,
  showNotification,
}: ModalConatinerProps): JSX.Element => {
  const [formStep, setFormStep] = useState(getFormStep(partner, status));
  const [isLoading, setIsLoading] = useState(false);
  const [otp, setOtp] = useState('');

  const { validateForm, isValid, values } = useFormikContext<FormikValues>();
  const { refetchOnboardingData } = useContext(OnboardingDetailsContext);
  const { openPopup, closePopup } = useContext(PopupContext);

  const modalTitle = getModalTitle(partner);
  const isPrimaryButtonDisabled = isButtonDisabled(formStep, isValid, otp);
  const { primaryButton, secondaryButton } = getButtonText(partner, formStep, status);

  const onOnbardingSuccess = async () => {
    await refetchOnboardingData();
    setFormStep(FORM_STEPS.SETUP_INFO);
  };

  const onSubmitForm = async () => {
    const { gstin, username, password } = values;
    setIsLoading(true);
    if (partner === ONBOARDING_PARTNERS.GST_PORTAL) {
      await onboardPartner(partner, gstin, username);
      setFormStep(FORM_STEPS.OTP);
    }
    if (partner === ONBOARDING_PARTNERS.E_INVOICE) {
      await onboardPartner(partner, gstin, username, password);
      await onOnbardingSuccess();
    }
  };

  const onSubmitOtp = async () => {
    const { username } = values;
    setIsLoading(true);
    await verifyOtp(partner, otp, username);
    await onOnbardingSuccess();
  };

  const onSetupInfoClicked = () => {
    if (status === ONBOARDING_STATUS.EXPIRED) {
      setFormStep(FORM_STEPS.LOGIN_DETAILS);
    } else {
      closePopup();
    }
  };

  const onPrimaryButtonClick = async () => {
    try {
      if (formStep === FORM_STEPS.LOGIN_DETAILS) await onSubmitForm();
      if (formStep === FORM_STEPS.OTP) await onSubmitOtp();
      if (formStep === FORM_STEPS.SETUP_INFO) onSetupInfoClicked();
    } catch (error: any) {
      showNotification({
        type: 'error',
        message: error?.errors?.join(' ') || 'Something went wrong',
      });
    } finally {
      setIsLoading(false);
    }
  };

  const onSecondaryButtonClick = () => {
    if (formStep === FORM_STEPS.LOGIN_DETAILS) {
      openPopup(MODAL_TYPES.ONBOARDING, { partner, status });
    }
    if (formStep === FORM_STEPS.SETUP_INFO && status === ONBOARDING_STATUS.EXPIRED) {
      closePopup();
    }
  };

  const onResendOtp = async () => {
    try {
      const { gstin, username } = values;
      await onboardPartner(partner, gstin, username);
      showNotification({
        type: 'success',
        message: 'Otp sent successfully!',
      });
    } catch (error: any) {
      showNotification({
        type: 'error',
        message: error?.errors?.join(' ') || 'Something went wrong',
      });
    }
  };

  /** to validate the form on mount */
  useEffect(() => {
    validateForm();
  }, []);

  return (
    <Modal isOpen={true} onDismiss={closePopup}>
      <ModalHeader title={modalTitle} />
      <ModalBody padding="spacing.6">
        {formStep === FORM_STEPS.LOGIN_DETAILS && <LoginDetails partner={partner} />}
        {formStep === FORM_STEPS.OTP && (
          <OtpInput otp={otp} setOtp={setOtp} onResendOtp={onResendOtp} />
        )}
        {formStep === FORM_STEPS.SETUP_INFO && <SetupSuccess partner={partner} status={status} />}
      </ModalBody>
      <ModalFooter>
        <Box display="flex" flexDirection="row" justifyContent="flex-end" width="100%">
          {secondaryButton && (
            <Button variant="tertiary" marginRight="spacing.5" onClick={onSecondaryButtonClick}>
              {secondaryButton}
            </Button>
          )}
          {primaryButton && (
            <Button
              isLoading={isLoading}
              variant="primary"
              icon={ArrowRightIcon}
              iconPosition="right"
              isDisabled={isPrimaryButtonDisabled}
              onClick={onPrimaryButtonClick}
            >
              {primaryButton}
            </Button>
          )}
        </Box>
      </ModalFooter>
    </Modal>
  );
};

export default ModalContainer;
