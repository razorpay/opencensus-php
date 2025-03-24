import React from 'react';
import {
  Box,
  MonitorIcon,
  SmartphoneIcon,
  TabItem,
  TabList,
  Tabs,
} from '@razorpay/blade/components';

import { PREVIEW_SCREEN } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/constants';
import { useCheckoutEditor } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/index';

import { PREFERRED_PREVIEW_SESSION_STORAGE_KEY } from './constants';
import { useCheckoutPreview } from './context/createContext';
import track from './track';

export const CheckoutPreviewButtons = (): JSX.Element => {
  const { isDesktopPreview, setIsDesktopPreivew } = useCheckoutPreview();
  const { handlePreviewScreenChange } = useCheckoutEditor();

  function togglePreview(selectedPreviewScreen: string) {
    if (selectedPreviewScreen === 'mweb') {
      setIsDesktopPreivew(false);
      sessionStorage.setItem(PREFERRED_PREVIEW_SESSION_STORAGE_KEY, 'false');
    } else {
      setIsDesktopPreivew(true);
      sessionStorage.setItem(PREFERRED_PREVIEW_SESSION_STORAGE_KEY, 'true');
    }
    track.togglePreview(selectedPreviewScreen);
    handlePreviewScreenChange(PREVIEW_SCREEN.HOME);
  }

  return (
    <Box flex="1">
      <Tabs
        onChange={togglePreview}
        variant="filled"
        size="medium"
        isFullWidthTabItem={true}
        orientation="horizontal"
        value={isDesktopPreview ? 'dweb' : 'mweb'}
      >
        <TabList>
          <TabItem value="mweb">
            <Box display="flex" alignSelf="center" justifyContent="center" alignItems="center">
              <SmartphoneIcon
                size="medium"
                color={
                  !isDesktopPreview
                    ? 'interactive.icon.primary.subtle'
                    : 'interactive.icon.gray.muted'
                }
              />
            </Box>
          </TabItem>
          <TabItem value="dweb">
            <Box display="flex" alignSelf="center" justifyContent="center" alignItems="center">
              <MonitorIcon
                size="medium"
                color={
                  isDesktopPreview
                    ? 'interactive.icon.primary.subtle'
                    : 'interactive.icon.gray.muted'
                }
              />
            </Box>
          </TabItem>
        </TabList>
      </Tabs>
    </Box>
  );
};
