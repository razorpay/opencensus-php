import React, { useState, useEffect } from 'react';
import {
  Modal,
  ModalHeader,
  ModalBody,
  ModalFooter,
  Button,
  Box,
  SelectInput,
  Dropdown,
  DropdownOverlay,
  ActionList,
  ActionListItem,
  TextArea,
  TextInput,
} from '@razorpay/blade/components';
import { connect } from 'react-redux';

import { createSupportTicketForBlockRule } from 'merchant/views/RiskAndFraud/RiskAnalytics/services';
import { showNotification } from 'merchant_common/reducers/notifications';

import SuccessPopup from './SuccessPopup';
import UploadButon from './UploadButton';
import { BLOCK_PARAMETERS, FORM_INITIAL_VALUES } from './constants';
import { RequestBlacklistProps, FormValues, FormError } from './types';
import { validateForm } from './utils';
import { CreateFDTicketParams } from '../types';

const RequestBlacklist = ({ user, isOpen, onDismiss, showNotification }: RequestBlacklistProps) => {
  const [formValues, setFormValues] = useState<FormValues>(FORM_INITIAL_VALUES);
  const [formError, setFormError] = useState<FormError>({});
  const [successModalData, setSuccessModalData] = useState({ isOpen: false, ticketId: '' });
  const [isSendingRequest, setIsSendingRequest] = useState(false);

  const onSendRequest = async () => {
    try {
      const errors = validateForm(formValues);
      if (Object.keys(errors).length === 0) {
        setIsSendingRequest(true);
        const response = await createSupportTicketForBlockRule(
          user,
          formValues as CreateFDTicketParams,
        );
        onDismiss();
        setSuccessModalData({ isOpen: true, ticketId: response.ticketId });
      }
      setFormError(errors);
    } catch (error: any) {
      showNotification({
        type: 'error',
        message: error.errors[0],
      });
    } finally {
      setIsSendingRequest(false);
    }
  };

  const onChange = (event) => {
    setFormValues((prev) => ({ ...prev, [event.name]: event.value ?? event.values[0] }));
  };

  const onFileChange = (file) => {
    setFormValues((prev) => ({ ...prev, file }));
  };

  const onSuccessModalDismiss = () => {
    setSuccessModalData((prev) => ({ ...prev, isOpen: false }));
  };

  useEffect(() => {
    if (isOpen) {
      setFormValues(FORM_INITIAL_VALUES);
      setFormError({});
    }
  }, [isOpen]);

  return (
    <>
      <Modal isOpen={isOpen} onDismiss={onDismiss} size="medium" zIndex={10000}>
        <ModalHeader title="Request blacklist" />
        <ModalBody padding="spacing.6" overflow-y="hidden">
          <Dropdown marginBottom="spacing.5">
            <SelectInput
              label="Parameter"
              name="parameters"
              placeholder="Select parameter to blacklist"
              labelPosition="left"
              value={formValues.parameters}
              validationState={formError.parameters ? 'error' : 'none'}
              errorText={formError.parameters}
              onChange={onChange}
              necessityIndicator="required"
              isRequired
            />
            <DropdownOverlay>
              <ActionList>
                {BLOCK_PARAMETERS.map(({ title, value }) => (
                  <ActionListItem key={value} title={title} value={value} />
                ))}
              </ActionList>
            </DropdownOverlay>
          </Dropdown>
          <TextArea
            name="comments"
            label="Comments"
            labelPosition="left"
            placeholder="Add comments about why you want to blacklist or any additional specifics about the action"
            marginBottom="spacing.5"
            value={formValues.comments}
            onChange={onChange}
          />
          <TextInput
            name="email"
            label="Email updates to"
            labelPosition="left"
            placeholder="Enter any other comma seperated email id(s) that you want the updates to be"
            helpText="This is in addition to the email id already associated with this Razorpay account"
            marginBottom="spacing.5"
            value={formValues.email}
            validationState={formError.email ? 'error' : 'none'}
            errorText={formError.email}
            onChange={onChange}
            necessityIndicator="required"
            isRequired
          />
          <UploadButon
            value={formValues.file}
            fileError={formError.file as string}
            onFileChange={onFileChange}
          />
        </ModalBody>
        <ModalFooter>
          <Box display="flex" gap="spacing.5" justifyContent="flex-end" width="100%">
            <Button variant="secondary" onClick={onDismiss}>
              Cancel
            </Button>
            <Button onClick={onSendRequest} isLoading={isSendingRequest}>
              Send request
            </Button>
          </Box>
        </ModalFooter>
      </Modal>
      <SuccessPopup
        isOpen={successModalData.isOpen}
        ticketId={successModalData.ticketId}
        onDismiss={onSuccessModalDismiss}
      />
    </>
  );
};

const mapStateToProps = ({ session }) => ({
  user: session.user,
});

const mapDispatchToProps = (dispatch) => ({
  showNotification: (payload) => dispatch(showNotification(payload)),
});

export default connect(mapStateToProps, mapDispatchToProps)(RequestBlacklist);
