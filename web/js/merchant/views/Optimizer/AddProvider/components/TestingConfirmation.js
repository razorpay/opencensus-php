import React from 'react';
import {
  Modal,
  ModalHeader,
  ModalBody,
  ModalFooter,
  Text,
  Box,
  Button,
} from '@razorpay/blade/components';

export const TestingConfirmation = ({
  isModalOpen,
  closeTestingConfirmationModal,
  startIntegrationTesting,
  raiseTicket,
  isTicketLoading,
}) => {
  const testNow = () => {
    startIntegrationTesting();
  };

  return (
    <Modal isOpen={isModalOpen} onDismiss={closeTestingConfirmationModal} size="small">
      <ModalHeader title="Optimizer Testing" />
      <ModalBody>
        <Text as="p">You can test optimizer integration in two ways:</Text>
        <Text as="p" marginTop="spacing.6">
          <Text as="span" weight="bold">
            1. Test it yourself
          </Text>
          {` (estimated time - 5 minutes), or`}
        </Text>
        <Text as="p" marginTop="spacing.6">
          <Text as="span" weight="bold">
            2. Raise a support ticket
          </Text>
          {` (estimated time - 48 hours)`}
        </Text>
      </ModalBody>
      <ModalFooter>
        <Box display="flex" gap="spacing.3" justifyContent="flex-end" width="100%">
          <Button
            variant="secondary"
            onClick={() => raiseTicket('test_help')}
            isLoading={isTicketLoading}
          >
            Raise a ticket
          </Button>
          <Button onClick={testNow}>Test now</Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
};
