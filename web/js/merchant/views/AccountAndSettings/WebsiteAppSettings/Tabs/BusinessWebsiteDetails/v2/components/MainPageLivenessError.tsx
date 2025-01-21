import React from 'react';
import { Box, Heading, Text, Link } from '@razorpay/blade/components';
import Lottie from 'react-lottie';

import ErrorAlert from './assets/lottie/ErrorAlert.json';
import useModalComponents from '../hooks/useModalComponents';
import { MainFormFields, MainPageFormData } from '../types';
import { snapPoints } from '../utils';

interface LoaderProps {
  isMobile: boolean;
  isOpen: boolean;
  onDismiss: () => void;
  onWebsiteChangeClick: () => void;
  mainPageFormState?: MainPageFormData;
}

const MainPageLivenessError: React.FC<LoaderProps> = ({
  isMobile,
  isOpen,
  onDismiss,
  onWebsiteChangeClick,
  mainPageFormState,
}) => {
  const { Modal, ModalBody } = useModalComponents(isMobile);
  const websiteUrl = mainPageFormState?.[MainFormFields.URL].value;

  return (
    <Modal isOpen={isOpen} onDismiss={onDismiss} snapPoints={snapPoints} size="large">
      <ModalBody padding={isMobile ? 'spacing.5' : 'spacing.0'}>
        <Box
          display="flex"
          flexDirection="column"
          alignItems="center"
          justifyContent="center"
          gap="spacing.4"
          padding="spacing.5"
          testID="loader-liveness-error"
          minHeight={isMobile ? 'auto' : '586px'}
        >
          <Box>
            <Lottie
              options={{
                loop: true,
                autoplay: true,
                animationData: ErrorAlert,
                rendererSettings: {
                  preserveAspectRatio: 'xMidYMid slice',
                },
              }}
              height="100%"
              width="100%"
            />
          </Box>
          <Heading size="large" textAlign="center">
            We're having trouble verifying your website
          </Heading>
          <Text
            textAlign="center"
            color="surface.text.gray.subtle"
            size="large"
            marginBottom="spacing.4"
          >
            Website URL: {websiteUrl} <Link onClick={onWebsiteChangeClick}>Change</Link>
          </Text>
        </Box>
      </ModalBody>
    </Modal>
  );
};
export default MainPageLivenessError;
