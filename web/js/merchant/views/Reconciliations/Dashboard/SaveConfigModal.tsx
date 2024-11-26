import React, { memo } from 'react';
import {
  Box,
  Button,
  Modal,
  ModalHeader,
  ModalBody,
  ModalFooter,
  TextInput,
  TextArea,
} from '@razorpay/blade/components';
import { SaveConfigModalProps } from 'merchant/views/Reconciliations/Dashboard/types';
import { connect } from 'react-redux';
import { compose } from 'redux';

import { showNotification } from 'merchant_common/reducers/notifications';

const SaveConfigModal: React.FC<SaveConfigModalProps> = memo(
  ({
    isOpenSaveConfigModal,
    setIsOpenSaveConfigModal,
    configForm,
    setConfigForm,
    setHasCompletedCreateReportStep,
  }) => {
    const updateForm = (key, value) =>
      setConfigForm((prevValue) => ({ ...prevValue, [key]: value }));

    return (
      <Modal
        isOpen={isOpenSaveConfigModal}
        onDismiss={() => setIsOpenSaveConfigModal(false)}
        size="small"
      >
        <ModalHeader title="Save your report template" />
        <ModalBody>
          <Box display="flex" flexDirection="column" gap="spacing.5">
            <TextInput
              label="Report name"
              necessityIndicator="required"
              value={configForm.name || ''}
              name="name"
              isRequired={true}
              onChange={({ name, value }) => updateForm(name, value)}
            />
            <TextArea
              label="Description"
              necessityIndicator="optional"
              value={configForm.description || ''}
              name="description"
              onChange={({ name, value }) => updateForm(name, value)}
            />
          </Box>
        </ModalBody>
        <ModalFooter>
          <Box display="flex" justifyContent="flex-end" alignItems="center" gap="spacing.4">
            <Button variant="tertiary" onClick={() => setIsOpenSaveConfigModal(false)}>
              Go Back
            </Button>
            <Button
              variant="primary"
              onClick={() => {
                if (!configForm.name) {
                  showNotification({
                    type: 'error',
                    message: 'Please enter a report name to save your custom reporting template.',
                  });
                  return;
                }
                setHasCompletedCreateReportStep((prevStep) => ({
                  ...prevStep,
                  selectColumns: true,
                }));
                setIsOpenSaveConfigModal(false);
              }}
            >
              Confirm
            </Button>
          </Box>
        </ModalFooter>
      </Modal>
    );
  },
);

export default compose(
  connect(null, {
    showNotification,
  }),
)(SaveConfigModal);
