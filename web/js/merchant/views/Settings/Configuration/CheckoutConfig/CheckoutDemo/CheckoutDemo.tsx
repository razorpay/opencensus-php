import React from 'react';
import { Badge } from '@razorpay/blade/components';

import { useSplitzService } from 'common/splitz';
import { isExperimentActive } from 'common/utils/rzp-utils';
import { CheckoutPreviewButtons } from 'merchant/views/Settings/Configuration/CheckoutConfig/CheckoutDemo/CheckoutPreviewButtons';
import {
  Wrapper,
  FrameContainer,
} from 'merchant/views/Settings/Configuration/CheckoutConfig/CheckoutDemo/styles';
import {
  CHECKOUT_CONFIG_FIELDS,
  useCheckoutConfig,
} from 'merchant/views/Settings/Configuration/CheckoutConfig/context';

import CheckoutV2 from './CheckoutV2';

const CheckoutDemo = () => {
  const { values } = useCheckoutConfig();
  const isDesktopPreview = values[CHECKOUT_CONFIG_FIELDS.IS_DESKTOP_PREVIEW];
  const {
    abExperiments: { checkout_editor_v2_preview },
  } = useSplitzService();

  const isCheckoutEditorV2PreviewEnabled = isExperimentActive(checkout_editor_v2_preview);
  return (
    <Wrapper>
      <Badge color="information" size="medium">
        Live Preview
      </Badge>
      <FrameContainer isDesktopPreview={isCheckoutEditorV2PreviewEnabled ? isDesktopPreview : true}>
        <CheckoutV2 shouldScaleToFit={isCheckoutEditorV2PreviewEnabled} />
      </FrameContainer>
      {isCheckoutEditorV2PreviewEnabled && <CheckoutPreviewButtons />}
    </Wrapper>
  );
};

export default CheckoutDemo;
