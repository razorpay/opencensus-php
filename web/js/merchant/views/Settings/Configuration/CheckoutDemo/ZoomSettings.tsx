import React from 'react';
import { Box, ZoomInIcon, ZoomOutIcon } from '@razorpay/blade/components';

import { PREVIEW_SCREEN } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/constants';
import { useCheckoutEditor } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/index';

import { ZoomButton } from './styles';

export function ZoomSettings() {
  const { handlePreviewScreenChange } = useCheckoutEditor();
  return (
    <Box display="flex" gap="spacing.3">
      <Box display="flex">
        <ZoomButton
          type="zoom-in"
          onClick={() => handlePreviewScreenChange(PREVIEW_SCREEN.METHODS)}
        >
          <ZoomInIcon size="large" />
        </ZoomButton>
        <ZoomButton type="zoom-out" onClick={() => handlePreviewScreenChange(PREVIEW_SCREEN.HOME)}>
          <ZoomOutIcon size="large" />
        </ZoomButton>
      </Box>
    </Box>
  );
}
