import React from 'react';
import {
  Box,
  Modal,
  Button,
  ModalBody,
  ModalFooter,
  ModalHeader,
  TextInput,
  ModalProps,
} from '@razorpay/blade/components';

import useResendModalForm, {
  FormSubmitCallback,
} from '@apps/digital-bills/src/views/BillDetails/hooks/useResendModalForm';

type ResendModalProps = {
  modalProps: Omit<ModalProps, 'children'>;
  onResend: FormSubmitCallback;
  isLoading?: boolean;
  formValues?: {
    email?: string;
    phoneNo?: string;
  };
};

const ResendModal = (props: ResendModalProps): React.ReactElement => {
  const { modalProps, isLoading, onResend, formValues } = props;
  const { emailValidationState, onEmailChange, onPhoneNoChange, onFormSubmit, email, phoneNo } =
    useResendModalForm(formValues);

  return (
    <Modal {...modalProps}>
      <ModalHeader
        subtitle="Add email address or phone number, or both to resend bill."
        title="Resend Bill"
      />
      <ModalBody>
        <Box display="flex" flexDirection="column" gap="spacing.6" marginY="spacing.4">
          <TextInput
            label="Email Address"
            placeholder="Enter Email ID"
            labelPosition="top"
            validationState={emailValidationState}
            onChange={onEmailChange}
            name="emailAddress"
            isDisabled={isLoading}
            value={email}
          />
          <TextInput
            label="Phone Number"
            placeholder="Enter Phone Number"
            labelPosition="top"
            name="phoneNumber"
            isDisabled={isLoading}
            value={phoneNo || ''}
            onChange={onPhoneNoChange}
          />
        </Box>
      </ModalBody>
      <ModalFooter>
        <Box display="flex" gap="spacing.3" justifyContent="flex-end" width="100%">
          <Button
            variant="tertiary"
            onClick={modalProps.onDismiss}
            type="button"
            isDisabled={isLoading}
          >
            Cancel
          </Button>
          <Button
            onClick={(): void => {
              onFormSubmit(onResend);
            }}
            isLoading={isLoading}
          >
            Resend
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
};
export default ResendModal;
