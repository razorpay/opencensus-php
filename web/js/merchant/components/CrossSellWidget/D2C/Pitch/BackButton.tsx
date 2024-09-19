import { ArrowLeftIcon, Box, IconButton } from '@razorpay/blade/components';
import React from 'react';

interface BackButtonProps {
  shouldShowBackButton: boolean;
  onBackButtonClick: () => void;
}

function BackButton({
  shouldShowBackButton,
  onBackButtonClick,
}: BackButtonProps): JSX.Element | null {
  if (!shouldShowBackButton) {
    return null;
  }
  return (
    <Box position="absolute" top="spacing.6" left="spacing.6">
      <IconButton
        icon={ArrowLeftIcon}
        accessibilityLabel="Go back"
        emphasis="intense"
        size="large"
        onClick={onBackButtonClick}
      />
    </Box>
  );
}

export default BackButton;
