import React, { useState } from 'react';
import { Box } from '@razorpay/blade/components';
import mobileBg from 'assets/mobile.png';

import { useSplitzService } from 'common/splitz';
import { isExperimentActive } from 'common/utils/rzp-utils';
import { CheckoutPreviewButtons } from 'merchant/views/Settings/Configuration/CheckoutDemo/CheckoutPreviewButtons';
import {
  FrameContainer,
  MobileBackground,
  MobileToolbar,
  PreviewBadge,
} from 'merchant/views/Settings/Configuration/CheckoutDemo/styles';
import {
  CHECKOUT_EDITOR_FIELDS,
  useCheckoutEditor,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context';
import { PREVIEW_SCREEN } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/constants';

import CheckoutV2 from './CheckoutV2';
import { ZoomSettings } from './ZoomSettings';
import { PREFERRED_PREVIEW_SESSION_STORAGE_KEY } from './constants';
import { CheckoutPreviewContext } from './context/createContext';

const Wrapper = ({ children }) => (
  <Box
    display="flex"
    flex="3"
    alignItems="center"
    flexDirection="column"
    overflow="hidden"
    borderRadius="2xlarge"
    backgroundColor="surface.background.gray.moderate"
    height="100%"
  >
    {children}
  </Box>
);

const ScrollablePreviewWrapper = ({ children, isDesktopPreview }) => (
  <Box
    position="relative"
    overflow="scroll"
    height="500px"
    width={isDesktopPreview ? 'auto' : '100%'}
  >
    {children}
  </Box>
);

const CheckoutDemo = (): JSX.Element => {
  const hasUserPreferredDesktopPreview =
    sessionStorage.getItem(PREFERRED_PREVIEW_SESSION_STORAGE_KEY) === 'true';
  const [isDesktopPreview, setIsDesktopPreivew] = useState(hasUserPreferredDesktopPreview);
  const {
    abExperiments: { checkout_editor_v2_preview },
  } = useSplitzService();

  const {
    values: {
      [CHECKOUT_EDITOR_FIELDS.COLOR]: brandColor,
      [CHECKOUT_EDITOR_FIELDS.PREVIEW_SCREEN]: previewScreen,
    },
  } = useCheckoutEditor();

  const isCheckoutEditorV2PreviewEnabled = isExperimentActive(checkout_editor_v2_preview);
  return (
    <CheckoutPreviewContext.Provider
      value={{
        isDesktopPreview,
        setIsDesktopPreivew,
      }}
    >
      <Wrapper>
        <PreviewBadge>Preview</PreviewBadge>

        <ScrollablePreviewWrapper isDesktopPreview={isDesktopPreview}>
          {!isDesktopPreview ? (
            <MobileBackground
              zoomMethodsScreen={previewScreen === PREVIEW_SCREEN.METHODS}
              backgroundImg={mobileBg}
            />
          ) : null}
          {!isDesktopPreview ? (
            <MobileToolbar
              zoomMethodsScreen={previewScreen === PREVIEW_SCREEN.METHODS}
              backgroundColor={brandColor}
            />
          ) : null}
          <FrameContainer
            isDesktopPreview={isCheckoutEditorV2PreviewEnabled ? isDesktopPreview : true}
            zoomMethodsScreen={previewScreen === PREVIEW_SCREEN.METHODS}
          >
            <CheckoutV2 />
          </FrameContainer>
        </ScrollablePreviewWrapper>
        <Box display="flex" gap="spacing.6" alignSelf="flex-start" padding="spacing.4" width="45%">
          <CheckoutPreviewButtons />
          <ZoomSettings />
        </Box>
      </Wrapper>
    </CheckoutPreviewContext.Provider>
  );
};

export default CheckoutDemo;
