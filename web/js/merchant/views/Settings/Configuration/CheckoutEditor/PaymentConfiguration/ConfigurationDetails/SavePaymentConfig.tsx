import {
  Box,
  Button,
  Modal,
  ModalBody,
  ModalFooter,
  ModalHeader,
  Text,
} from '@razorpay/blade/components';
import React, { useState } from 'react';
import {
  CHECKOUT_EDITOR_FIELDS,
  useCheckoutEditor,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context';
import { MerchantCheckoutPaymentConfig } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/types';

export function SavePaymentConfig({ isOpen, onClose, isSaving, modalType }: { isOpen: boolean; onClose: () => void; isSaving: boolean; modalType: string }) {
  const { values, handleSave } = useCheckoutEditor();
  const [isClickedOnNotNow, setIsClickedOnNotNow] = useState(false);
  const handleSaveAndClose = () => {
    handleSave();
    onClose();
  };
  const handleSaveAsDefault = () => {
    const selectedPaymentConfig = values[CHECKOUT_EDITOR_FIELDS.SELECTED_PAYMENT_CONFIG];
    const updatedPaymentConfig: MerchantCheckoutPaymentConfig = {
      ...selectedPaymentConfig,
      is_default: true,
    };
    const updatedValues = {
      ...values,
      selectedPaymentConfig: { ...updatedPaymentConfig },
    };
    handleSave(updatedValues);
    onClose();
  };
  return (
    <>
      {modalType !== 'default' ? (
        <Modal isOpen={isOpen} onDismiss={onClose} size='medium'>
          <ModalHeader title={`Do you want to save this Configuration as Default?`} />
          <ModalBody>
            <Text>
              Click “Yes” to save changes and set this as your default checkout configuration. Click “Not now” to save changes without activating.
            </Text>
          </ModalBody>
          <ModalFooter>
            <Box display="flex" gap="spacing.3" justifyContent="flex-end" width="100%">
              <Button
                variant="tertiary"
                onClick={() => {
                  setIsClickedOnNotNow(true);
                  handleSaveAndClose();
                }}
                isLoading={isSaving && isClickedOnNotNow}
              >
                Not now
              </Button>
              <Button
                variant="primary"
                onClick={handleSaveAsDefault}
                isLoading={isSaving && !isClickedOnNotNow}
              >
                Yes
              </Button>
            </Box>
          </ModalFooter>
        </Modal>
      ) : (
        <Modal isOpen={isOpen} onDismiss={onClose} size='medium'>
          <ModalHeader title={`You are saving this configuration as default`} />
          <ModalBody>
            <Text>
              Setting this as the default configuration will activate this on your checkout for
              your users.
            </Text>
          </ModalBody>
          <ModalFooter>
            <Box display="flex" gap="spacing.3" justifyContent="flex-end" width="100%">
              <Button variant="tertiary" onClick={onClose}>
                No
              </Button>
              <Button variant="primary" onClick={handleSaveAndClose} isLoading={isSaving}>
                Yes
              </Button>
            </Box>
          </ModalFooter>
        </Modal>
      )}
    </>
  );
}
