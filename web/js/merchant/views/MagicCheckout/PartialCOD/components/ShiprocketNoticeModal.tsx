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

const ShiprocketNoticeModal = ({
  isOpen = false,
  onClose = () => {},
}: {
  isOpen: boolean;
  onClose: () => void;
}) => {
  return (
    <Modal accessibilityLabel="base-confirm-modal" onDismiss={onClose} isOpen={isOpen}>
      <ModalHeader title="If you use Shiprocket for shipping" />
      <ModalBody>
        <Text size="medium" color="surface.text.gray.normal">
          If you're using Shiprocket for shipping, please contact their support team to enable
          Partial COD support on Shiprocket before activating it in your Razorpay Dashboard.
        </Text>
        <Text size="medium" color="surface.text.gray.normal" marginTop="2px">
          Activating it on Razorpay first may cause your Partial COD orders to fail if support isn't
          already set up on Shiprocket.
        </Text>
      </ModalBody>
      <ModalFooter>
        <Box display="flex" justifyContent="flex-end" gap="spacing.5">
          <Button variant="primary" onClick={onClose}>
            Understood
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
};

export default ShiprocketNoticeModal;
