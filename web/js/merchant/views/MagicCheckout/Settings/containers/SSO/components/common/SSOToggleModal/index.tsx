import React, { useEffect } from 'react';
import { Modal, Box } from '@razorpay/blade/components';
import { SSOToggleModalProps } from './types';
import { useSSOToggle } from './useSSOToggle';
import { LoadingContent } from './LoadingContent';
import { SuccessContent } from './SuccessContent';
import { ErrorContent } from './ErrorContent';

const SSOToggleModal: React.FC<SSOToggleModalProps> = ({ isOpen, onDismiss }) => {
  const { currentState, setCurrentState, activationLink, errorMsg, handleToggle } = useSSOToggle();

  useEffect(() => {
    if (isOpen) {
      setCurrentState('loading');
      handleToggle().then(setCurrentState);
    }
  }, [isOpen]);

  const renderContent = () => {
    switch (currentState) {
      case 'loading':
        return <LoadingContent />;
      case 'success':
        return <SuccessContent activationLink={activationLink} onDismiss={onDismiss} />;
      case 'error':
        return <ErrorContent errorMsg={errorMsg} />;
      default:
        return null;
    }
  };

  return (
    <Modal isOpen={isOpen} onDismiss={onDismiss}>
      <Box
        padding="spacing.7"
        display="flex"
        flexDirection="column"
        alignItems="center"
        gap="spacing.5"
      >
        {renderContent()}
      </Box>
    </Modal>
  );
};

export default SSOToggleModal;
