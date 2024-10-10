import React, { useState } from 'react';
import { Badge } from '@razorpay/blade/components';

import { useSplitzService } from 'common/splitz';
import { isExperimentActive } from 'common/utils/rzp-utils';
import { CheckoutPreviewButtons } from 'merchant/views/Settings/Configuration/CheckoutDemo/CheckoutPreviewButtons';
import {
  Wrapper,
  FrameContainer,
  MobileBackground,
} from 'merchant/views/Settings/Configuration/CheckoutDemo/styles';
import mobileBg from 'assets/mobile.png';

import CheckoutV2 from './CheckoutV2';
import { CheckoutPreviewContext } from './context/createContext';

const CheckoutDemo = (): JSX.Element => {
  const [isDesktopPreview, setIsDesktopPreivew] = useState(false);
  const {
    abExperiments: { checkout_editor_v2_preview },
  } = useSplitzService();

  const isCheckoutEditorV2PreviewEnabled = isExperimentActive(checkout_editor_v2_preview);
  return (
    <CheckoutPreviewContext.Provider
      value={{
        isDesktopPreview,
        setIsDesktopPreivew,
      }}
    >
      <Wrapper>
        <Badge color="information" size="medium">
          Live Preview
        </Badge>
        {!isDesktopPreview ? <MobileBackground backgroundImg={mobileBg} /> : null}
        <FrameContainer
          isDesktopPreview={isCheckoutEditorV2PreviewEnabled ? isDesktopPreview : true}
        >
          <CheckoutV2 shouldScaleToFit={isCheckoutEditorV2PreviewEnabled} />
        </FrameContainer>
        {isCheckoutEditorV2PreviewEnabled && <CheckoutPreviewButtons />}
      </Wrapper>
    </CheckoutPreviewContext.Provider>
  );
};

export default CheckoutDemo;
