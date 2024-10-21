import React, { useState } from 'react';
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

import CheckoutV2 from './CheckoutV2';
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
    values: { [CHECKOUT_EDITOR_FIELDS.COLOR]: brandColor },
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

        <ScrollablePreview>
          {!isDesktopPreview ? <MobileBackground backgroundImg={mobileBg} /> : null}
          {!isDesktopPreview ? <MobileToolbar backgroundColor={brandColor} /> : null}
          <FrameContainer
            isDesktopPreview={isCheckoutEditorV2PreviewEnabled ? isDesktopPreview : true}
          >
            <CheckoutV2 />
          </FrameContainer>
        </ScrollablePreview>

        {isCheckoutEditorV2PreviewEnabled && <CheckoutPreviewButtons />}
      </Wrapper>
    </CheckoutPreviewContext.Provider>
  );
};

export default CheckoutDemo;
