import React from 'react';
import { Box, Spinner, Text } from '@razorpay/blade/components';
import ModalWithBottomSheet from 'apps/pos/src/app/components/ModalWithBottomSheet';

interface KYCRedirectionLoaderProps {
  isOpen: boolean;
  message?: string;
}

const KYCRedirectionLoader = ({ isOpen, message = ''  }: KYCRedirectionLoaderProps): JSX.Element => {
  return (
    <ModalWithBottomSheet
      isOpen={isOpen}
      onDismiss={() => null}
      snapPoints={[0.5, 0.5, 0.5]}
      content={
        <Box display="flex" flexDirection="column" alignItems="center" justifyContent="center">
          <Spinner
            size="medium"
            accessibilityLabel="easy-dashboard-redirection-spinner"
            marginBottom="spacing.3"
          />
          <Text size="medium" marginBottom="spacing.11">
            {message || 'Redirecting you to the onboarding journey....'}
          </Text>
        </Box>
      }
    />
  );
};

export default KYCRedirectionLoader;
