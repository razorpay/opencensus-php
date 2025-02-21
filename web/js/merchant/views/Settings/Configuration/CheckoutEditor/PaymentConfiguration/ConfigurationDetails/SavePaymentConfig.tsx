import {
  Box,
  Button,
  Modal,
  ModalBody,
  ModalFooter,
  ModalHeader,
  Text,
} from '@razorpay/blade/components';
import React from 'react';
import {
  CHECKOUT_EDITOR_FIELDS,
  useCheckoutEditor,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context';

export function SavePaymentConfig({ isOpen, onClose, onSave, isSaving, modalType }) {
  const { handleSetConfigAsDefault, values } = useCheckoutEditor();
  const selectedConfig = values[CHECKOUT_EDITOR_FIELDS.SELECTED_PAYMENT_CONFIG];
  const handleSaveAndClose = async () => {
    await onSave();
    onClose();
  };
  const handleSaveAsDefault = async () => {
    await handleSetConfigAsDefault(selectedConfig);
    handleSaveAndClose();
  };
  return (
    <>
      {modalType !== 'default' ? (
        <Modal isOpen={isOpen} onDismiss={onClose}>
          <ModalHeader title={`Do you want to save this Configuration as Default?`} />
          <ModalBody>
            <Text>
              Set this as the default configuration to activate this configuration on your checkout
              for your users.
            </Text>
          </ModalBody>
          <ModalFooter>
            <Box display="flex" gap="spacing.3" justifyContent="flex-end" width="100%">
              <Button variant="tertiary" onClick={handleSaveAndClose}>
                Not now
              </Button>
              <Button variant="primary" onClick={handleSaveAsDefault} isLoading={isSaving}>
                Yes
              </Button>
            </Box>
          </ModalFooter>
        </Modal>
      ) : (
        <Modal isOpen={isOpen} onDismiss={onClose}>
          <ModalHeader title={`You are saving this configuration as default`} />
          <ModalBody>
            <Text>
              Setting this as the default configuration will activate this it on your checkout for
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
