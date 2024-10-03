import React, { useState } from 'react';
import { bindActionCreators } from 'redux';
import { connect } from 'react-redux';

import {
  Modal,
  ModalHeader,
  ModalBody,
  ModalFooter,
  Box,
  Button,
} from '@razorpay/blade/components';
import { Form } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/Components/Form';
import { DisplayNotificationTxt } from 'merchant/views/MagicCheckout/common/components/ConfirmationModal';

import { updateShippingMethod } from 'merchant/reducers/magicCheckout/shippingEngine/action';
import { showNotification } from 'merchant_common/reducers/notifications';
import { convertFormDataToPayload } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/helpers';

import { useFormContext } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/Context';

const EditModal = ({ isOpen, handleModalClose, updateShippingMethod, showNotification }) => {
  const { formData, formErrors } = useFormContext();
  const [isLoading, setIsLoading] = useState(false);

  const notify = (type: string, message: string) =>
    showNotification({ type, message: <DisplayNotificationTxt notificationTxt={message} /> });

  const handleSubmit = () => {
    setIsLoading(true);
    updateShippingMethod(convertFormDataToPayload(formData))
      .then(() => {
        notify('success', 'Shipping method updated successfully');
      })
      .catch((err) => {
        notify('error', `${err?.errors?.[0] || 'Something went wrong. Please try again later.'}`);
      })
      .finally(() => {
        setIsLoading(false);
        handleModalClose();
      });
  };

  return (
    <Modal isOpen={isOpen} onDismiss={handleModalClose} size="medium">
      <ModalHeader
        title={`Edit Shipping Method - ${formData.name}`}
        subtitle={formData.shippingZone}
      />
      <ModalBody>
        <Form />
      </ModalBody>
      <ModalFooter>
        <Box display="flex" gap="spacing.3" justifyContent="flex-end" width="100%">
          <Button isDisabled={isLoading} onClick={handleModalClose} variant="secondary">
            Cancel
          </Button>
          <Button
            testID="confirm-button"
            isLoading={isLoading}
            isDisabled={Boolean(Object.keys(formErrors)?.length) || isLoading}
            onClick={handleSubmit}
          >
            Submit
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      updateShippingMethod,
      showNotification,
    },
    dispatch,
  );

export default connect(null, mapDispatchToProps)(EditModal);
