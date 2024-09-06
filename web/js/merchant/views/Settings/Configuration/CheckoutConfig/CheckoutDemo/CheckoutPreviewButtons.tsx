import React from 'react';

import { DesktopIcon } from 'merchant/views/Settings/Configuration/CheckoutConfig/CheckoutDemo/icons/DesktopIcon';
import { MobileIcon } from 'merchant/views/Settings/Configuration/CheckoutConfig/CheckoutDemo/icons/MobileIcon';
import { useCheckoutConfig } from 'merchant/views/Settings/Configuration/CheckoutConfig/context';

import { PreviewButton, PreviewSizeWrapper } from './styles';

export const CheckoutPreviewButtons = () => {
  const { values, handlePreviewChange } = useCheckoutConfig();
  return (
    <PreviewSizeWrapper>
      <PreviewButton isActive={values.isDesktopPreview} onClick={() => handlePreviewChange(true)}>
        <DesktopIcon />
      </PreviewButton>
      <PreviewButton isActive={!values.isDesktopPreview} onClick={() => handlePreviewChange(false)}>
        <MobileIcon />
      </PreviewButton>
    </PreviewSizeWrapper>
  );
};
