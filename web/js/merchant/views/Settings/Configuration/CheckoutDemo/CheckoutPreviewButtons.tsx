import React from 'react';

import { DesktopIcon } from 'merchant/views/Settings/Configuration/CheckoutDemo/icons/DesktopIcon';
import { MobileIcon } from 'merchant/views/Settings/Configuration/CheckoutDemo/icons/MobileIcon';

import { PREFERRED_PREVIEW_SESSION_STORAGE_KEY } from './constants';
import { useCheckoutPreview } from './context/createContext';
import { PreviewButton, SwitchPreviewWrapper } from './styles';

export const CheckoutPreviewButtons = (): JSX.Element => {
  const { isDesktopPreview, setIsDesktopPreivew } = useCheckoutPreview();
  function switchPreview(shouldSwitchToDesktop: boolean) {
    setIsDesktopPreivew(shouldSwitchToDesktop);
    sessionStorage.setItem(PREFERRED_PREVIEW_SESSION_STORAGE_KEY, shouldSwitchToDesktop.toString());
  }
  return (
    <SwitchPreviewWrapper>
      <PreviewButton isActive={!isDesktopPreview} onClick={() => switchPreview(false)}>
        <MobileIcon />
      </PreviewButton>
      <PreviewButton isActive={isDesktopPreview} onClick={() => switchPreview(true)}>
        <DesktopIcon />
      </PreviewButton>
    </SwitchPreviewWrapper>
  );
};
