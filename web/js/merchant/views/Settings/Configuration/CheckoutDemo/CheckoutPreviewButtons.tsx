import React from 'react';

import { DesktopIcon } from 'merchant/views/Settings/Configuration/CheckoutDemo/icons/DesktopIcon';
import { MobileIcon } from 'merchant/views/Settings/Configuration/CheckoutDemo/icons/MobileIcon';
import { PREVIEW_SCREEN } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/constants';
import { useCheckoutEditor } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/index';

import { PREFERRED_PREVIEW_SESSION_STORAGE_KEY } from './constants';
import { useCheckoutPreview } from './context/createContext';
import { PreviewButton, SwitchPreviewWrapper } from './styles';
import track from './track';

export const CheckoutPreviewButtons = (): JSX.Element => {
  const { isDesktopPreview, setIsDesktopPreivew } = useCheckoutPreview();
  const { handlePreviewScreenChange } = useCheckoutEditor();
  function switchPreview(shouldSwitchToDesktop: boolean) {
    setIsDesktopPreivew(shouldSwitchToDesktop);
    sessionStorage.setItem(PREFERRED_PREVIEW_SESSION_STORAGE_KEY, shouldSwitchToDesktop.toString());
    track.togglePreview(shouldSwitchToDesktop ? 'dweb' : 'mweb');
    handlePreviewScreenChange(PREVIEW_SCREEN.HOME);
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
