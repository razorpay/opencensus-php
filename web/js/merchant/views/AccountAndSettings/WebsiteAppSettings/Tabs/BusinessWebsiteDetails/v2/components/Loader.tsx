import React from 'react';
import { Box } from '@razorpay/blade/components';
import Lottie from 'react-lottie';

import { loaderVariant } from './utils';
import useModalComponents from '../hooks/useModalComponents';
import { WebsiteSubmitModalSteps } from '../types';
import { snapPoints } from '../utils';

interface LoaderProps {
  isMobile: boolean;
  isOpen: boolean;
  onDismiss: () => void;
  onClick: () => void;
  variant: WebsiteSubmitModalSteps;
}

const Loader: React.FC<LoaderProps> = ({ isMobile, onClick, variant, isOpen, onDismiss }) => {
  const { Modal, ModalBody } = useModalComponents(isMobile);

  const { Content, lottieAnimation } = loaderVariant[variant];

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
          testID={`loader-${variant}`}
          minHeight={isMobile ? 'auto' : '586px'}
        >
          <Box>
            <Lottie
              options={{
                loop: true,
                autoplay: true,
                animationData: lottieAnimation,
                rendererSettings: {
                  preserveAspectRatio: 'xMidYMid slice',
                },
              }}
              height="100%"
              width="100%"
            />
          </Box>
          {Content ? <Content onClick={onClick} /> : null}
        </Box>
      </ModalBody>
    </Modal>
  );
};
export default Loader;
