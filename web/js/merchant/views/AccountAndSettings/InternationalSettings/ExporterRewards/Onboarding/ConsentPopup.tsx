import React from 'react';
import {
  Modal,
  ModalHeader,
  ModalBody,
  ModalFooter,
  Button,
  Link,
  Box,
} from '@razorpay/blade/components';

import { EXPORTER_REWARDS_LINKS } from '../constants';

interface ActionProps {
  isVisible: boolean;
  onClose: () => void;
}

const ConsentPopup = ({ isVisible, onClose }: ActionProps) => {
  const handleConsent = () => {
    onClose();
  };

  return (
    <Modal isOpen={isVisible} onDismiss={onClose} size="small" zIndex={9999}>
      <ModalHeader
        title="Terms of Use and Privacy Policy"
        subtitle="Rewards program for Cross Border Exporters"
      />
      <ModalBody>
        To proceed, kindly confirm if you agree to our{' '}
        <Link href={EXPORTER_REWARDS_LINKS.TERMS} target="_blank" rel="noopener noreferer">
          terms of use
        </Link>{' '}
        and{' '}
        <Link href={EXPORTER_REWARDS_LINKS.PRIVACY_POLICY} target="_blank" rel="noopener noreferer">
          privacy policy
        </Link>
        .
      </ModalBody>
      <ModalFooter>
        <Box display="flex">
          <Button variant="primary" marginLeft="auto" onClick={handleConsent}>
            I agree
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
};

export default ConsentPopup;
