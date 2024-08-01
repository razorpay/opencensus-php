import React from 'react';
import {
  Modal,
  ModalHeader,
  ModalBody,
  ModalFooter,
  Box,
  Button,
  Text,
} from '@razorpay/blade/components';

import { OPTIMIZER_SVGS } from 'merchant/views/Optimizer/utils';

export const RaiseTicketSuccess = ({ isModalOpen, closeRaiseTicketSuccessModal }): JSX.Element => {
  return (
    <Modal isOpen={isModalOpen} onDismiss={closeRaiseTicketSuccessModal} size="small" zIndex={9999}>
      <ModalHeader title="Optimizer Integration Testing" />
      <ModalBody>
        <Box textAlign="center" marginBottom="spacing.5">
          <img src={OPTIMIZER_SVGS.checkCircle} />
        </Box>
        <Text textAlign="center" marginBottom="spacing.5" size="large" weight="semibold">
          Ticket raised successfully!
        </Text>
        <Text textAlign="center" size="small" color="surface.text.gray.muted">
          Our support team will reach out to you within 48 hours
        </Text>
      </ModalBody>
      <ModalFooter>
        <Button isFullWidth={true} onClick={closeRaiseTicketSuccessModal}>
          Close
        </Button>
      </ModalFooter>
    </Modal>
  );
};
