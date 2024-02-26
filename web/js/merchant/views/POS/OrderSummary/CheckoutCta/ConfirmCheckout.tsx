import React from 'react';
import {
  Box,
  Button,
  Modal,
  ModalBody,
  ModalFooter,
  ModalHeader,
  Text,
} from '@razorpay/blade/components';

type ConfirmCheckoutProps = {
  isOpen: boolean;
  onDismiss: () => void;
  onSubmit: () => void;
};

const ConfirmCheckout = ({ isOpen, onDismiss, onSubmit }: ConfirmCheckoutProps): JSX.Element => {
  return (
    <Modal isOpen={isOpen} onDismiss={onDismiss} size="small">
      <ModalHeader title="Confirm Order" />
      <ModalBody>
        <Text>
          By clicking on <b>Confirm</b>, your order will be placed! 🎉
        </Text>
      </ModalBody>
      <ModalFooter>
        <Box display="flex" gap="spacing.3" justifyContent="flex-end" width="100%">
          <Button variant="secondary" onClick={onDismiss}>
            Cancel
          </Button>
          <Button onClick={onSubmit} testID="pos-checkout-confim-modal-confirm">
            Confirm
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
};

export default ConfirmCheckout;
