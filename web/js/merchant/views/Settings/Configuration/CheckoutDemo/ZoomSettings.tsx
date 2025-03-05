import React from 'react';
import { Box, ZoomInIcon, ZoomOutIcon } from '@razorpay/blade/components';

import {
  CHECKOUT_EDITOR_FIELDS,
  PREVIEW_SCREEN,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context/constants';
import { useCheckoutEditor } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/index';

import { ZoomButton } from './styles';
import _track from './track';

export function ZoomSettings() {
  const { values, handlePreviewScreenChange } = useCheckoutEditor();
  const currentPreviewScreen = values[CHECKOUT_EDITOR_FIELDS.PREVIEW_SCREEN];

  function handleZoomChange(screen: PREVIEW_SCREEN) {
    handlePreviewScreenChange(screen);
    _track.togglePreview(screen);
  }
  return (
    <Box display="flex" gap="spacing.3">
      <Box display="flex">
        <ZoomButton
          disabled={currentPreviewScreen === PREVIEW_SCREEN.METHODS}
          isDisabled={currentPreviewScreen === PREVIEW_SCREEN.METHODS}
          type="zoom-in"
          onClick={() => handleZoomChange(PREVIEW_SCREEN.METHODS)}
        >
          <ZoomInIcon size="large" />
        </ZoomButton>
        <ZoomButton
          disabled={currentPreviewScreen === PREVIEW_SCREEN.HOME}
          isDisabled={currentPreviewScreen === PREVIEW_SCREEN.HOME}
          type="zoom-out"
          onClick={() => handleZoomChange(PREVIEW_SCREEN.HOME)}
        >
          <ZoomOutIcon size="large" />
        </ZoomButton>
      </Box>
    </Box>
  );
}
