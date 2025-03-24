import React from 'react';
import { Box, Button, ButtonGroup, ZoomInIcon, ZoomOutIcon } from '@razorpay/blade/components';

import { PREVIEW_SCREEN } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/constants';
import { useCheckoutEditor } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/index';
import _track from './track';

export function ZoomSettings() {
  const { handlePreviewScreenChange } = useCheckoutEditor();
  function handleZoomChange(screen: PREVIEW_SCREEN) {
    handlePreviewScreenChange(screen);
    _track.togglePreview(screen);
  }
  return (
    <Box flex="1" display="flex" alignItems="center" borderRadius="medium">
      <ButtonGroup variant="tertiary" isFullWidth={true} size="medium">
        <Button
          icon={ZoomInIcon}
          onClick={() => handleZoomChange(PREVIEW_SCREEN.METHODS)}
          isFullWidth={true}
        />

        <Button
          icon={ZoomOutIcon}
          onClick={() => handleZoomChange(PREVIEW_SCREEN.HOME)}
          isFullWidth={true}
        />
      </ButtonGroup>
    </Box>
  );
}
