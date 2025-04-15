import React from 'react';
import { Box } from '@razorpay/blade/components';
import { DesktopIcon } from 'merchant/views/Settings/Configuration/CheckoutDemo/icons/DesktopIcon';
import { MobileIcon } from 'merchant/views/Settings/Configuration/CheckoutDemo/icons/MobileIcon';
import {
  PreviewButton,
  SwitchPreviewWrapper,
} from 'merchant/views/MagicCheckout/Settings/containers/SSO/styled';

type SSOPreviewButtonsProps = {
  isDesktopPreview: boolean;
  setIsDesktopPreview: (isDesktop: boolean) => void;
};

const SSOPreviewButtons: React.FC<SSOPreviewButtonsProps> = ({
  isDesktopPreview,
  setIsDesktopPreview,
}) => {
  return (
    <Box display="flex" gap="spacing.3">
      <SwitchPreviewWrapper>
        <PreviewButton isActive={!isDesktopPreview} onClick={() => setIsDesktopPreview(false)}>
          <MobileIcon />
        </PreviewButton>
        <PreviewButton isActive={isDesktopPreview} onClick={() => setIsDesktopPreview(true)}>
          <DesktopIcon />
        </PreviewButton>
      </SwitchPreviewWrapper>
    </Box>
  );
};

export default SSOPreviewButtons;
