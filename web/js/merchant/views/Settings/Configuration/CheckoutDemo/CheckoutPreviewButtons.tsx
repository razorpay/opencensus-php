import React from 'react';

import { DesktopIcon } from 'merchant/views/Settings/Configuration/CheckoutDemo/icons/DesktopIcon';
import { MobileIcon } from 'merchant/views/Settings/Configuration/CheckoutDemo/icons/MobileIcon';

import { useCheckoutPreview } from './context/createContext';
import { PreviewButton, PreviewSizeWrapper } from './styles';

export const CheckoutPreviewButtons = (): JSX.Element => {
  const { isDesktopPreview, setIsDesktopPreivew } = useCheckoutPreview();
  function handleDesktopPreviewClick() {
    setIsDesktopPreivew(true);
  }
  function handleMobilePreviewClick() {
    setIsDesktopPreivew(false);
  }
  return (
    <PreviewSizeWrapper>
      <PreviewButton isActive={!isDesktopPreview} onClick={handleMobilePreviewClick}>
        <MobileIcon />
      </PreviewButton>
      <PreviewButton isActive={isDesktopPreview} onClick={handleDesktopPreviewClick}>
        <DesktopIcon />
      </PreviewButton>
    </PreviewSizeWrapper>
  );
};
