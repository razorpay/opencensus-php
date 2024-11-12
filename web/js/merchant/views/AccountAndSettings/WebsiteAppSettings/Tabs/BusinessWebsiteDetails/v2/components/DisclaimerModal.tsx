import React from 'react';
import { Box, Text, Button } from '@razorpay/blade/components';

import { policyPagePublishDisclaimer } from './constants';
import useModalComponents from '../hooks/useModalComponents';
import { snapPoints } from '../utils';

const DisclaimerModal = ({ isMobile, isDisclaimerOpen, setIsDisclaimerOpen }) => {
  const { Modal, ModalHeader, ModalBody, ModalFooter } = useModalComponents(isMobile);

  return (
    <Modal
      isOpen={isDisclaimerOpen}
      onDismiss={() => setIsDisclaimerOpen(false)}
      snapPoints={snapPoints}
      size="medium"
    >
      <ModalHeader title="Disclaimer" />
      <ModalBody>
        <Box>
          <Text color="surface.text.gray.subtle">{policyPagePublishDisclaimer}</Text>
        </Box>
      </ModalBody>
      <ModalFooter>
        <Box display="flex" gap="spacing.3" justifyContent="flex-end" width="100%">
          <Button onClick={() => setIsDisclaimerOpen(false)}>Okay, got it</Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
};

export default DisclaimerModal;
