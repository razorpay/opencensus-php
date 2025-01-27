import React, { useState } from 'react';
import { Box } from '@razorpay/blade/components';
import mobileBg from 'assets/mobile.png';

import { useSplitzService } from 'common/splitz';
import { isExperimentActive } from 'common/utils/rzp-utils';
import { CheckoutPreviewButtons } from 'merchant/views/Settings/Configuration/CheckoutDemo/CheckoutPreviewButtons';
import {
  Wrapper,
  FrameContainer,
  MobileBackground,
  MobileToolbar,
  PreviewBadge,
  ScrollablePreview,
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

        <ScrollablePreview isDesktopPreview={isDesktopPreview}>
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
        </ScrollablePreview>

        <Box display="flex" gap="spacing.6" alignSelf="flex-start" padding="spacing.4">
          <CheckoutPreviewButtons />
          <ZoomSettings />
        </Box>
      </Wrapper>
    </CheckoutPreviewContext.Provider>
  );
};

export default CheckoutDemo;
