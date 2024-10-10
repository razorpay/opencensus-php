import React from 'react';

import { Box, CheckCircleIcon, IconButton, Text, TrashIcon } from '@razorpay/blade/components';
import { ImageSelectedWrapper, ImageSelector } from './styled';

import uploadedSvg from 'assets/checkout-editor/title-style/uploaded-image.svg';

import { useCheckoutEditor } from 'merchant/views/Settings/Configuration/CheckoutEditor/context';
import { UploadLogoProps } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/types/titleType';
import { showNotification } from 'merchant_common/reducers/notifications';

const BRAND_LOGO_SIZE_LIMIT = 5;

const UploadLogo: React.FC<UploadLogoProps> = ({ logo, logoRaw, fileName, setFileName }) => {
  const { handleLogoChange, handleEditLogoModalDiscard } = useCheckoutEditor();

  const onImageUpload = () => {
    const input = document.createElement('input');
    input.setAttribute('type', 'file');
    input.click();

    input.onchange = () => {
      const file = input.files && input.files[0];
      if (!file) {
        return;
      }
      const fileSizeMB = file.size / 1024 / 1024;
      if (fileSizeMB > BRAND_LOGO_SIZE_LIMIT) {
        showNotification({
          type: 'error',
          message: `Image too large. Max limit ${BRAND_LOGO_SIZE_LIMIT / (1024 * 1024)}MB`,
        });
        return;
      }

      const isImageType = /^image\//.test(file.type);

      if (!isImageType) {
        const errorMessage = 'Select a valid image';
        showNotification({
          type: 'error',
          message: errorMessage,
        });
        return;
      }
      setFileName(file?.name ?? '');
      handleLogoChange(file);
    };
  };

  return (
    <>
      {logo || logoRaw ? (
        <Box width="100%">
          <Text
            weight="semibold"
            size="medium"
            color="surface.text.gray.muted"
            marginBottom={'spacing.3'}
          >
            Upload Image
          </Text>
          <ImageSelectedWrapper>
            <Box display="flex" justifyContent="center" alignItems="center" gap={'spacing.4'}>
              <img src={uploadedSvg} alt="uploaded-svg" />
              <Box display="flex" justifyContent="center" alignItems="center" gap={'spacing.3'}>
                <Text weight="medium" size="medium" color="surface.text.gray.subtle">
                  {fileName}
                </Text>
                <CheckCircleIcon size="medium" color="interactive.icon.primary.normal" />
              </Box>
            </Box>
            <IconButton
              icon={() => <TrashIcon size="large" color="interactive.icon.gray.muted" />}
              onClick={() => {
                handleEditLogoModalDiscard('', null);
              }}
              accessibilityLabel="delete-logo"
            />
          </ImageSelectedWrapper>
          <Text
            variant="caption"
            weight="medium"
            size="medium"
            color="surface.text.gray.muted"
            marginTop={'spacing.3'}
          >
            Recommended aspect ratio 1:1. Max size 5MB.
          </Text>
        </Box>
      ) : (
        <Box width="100%">
          <Text
            weight="semibold"
            size="medium"
            color="surface.text.gray.muted"
            marginBottom={'spacing.3'}
          >
            Upload Logo
          </Text>
          <ImageSelector onClick={onImageUpload} />
          <Text
            variant="caption"
            weight="regular"
            size="medium"
            color="surface.text.gray.muted"
            marginTop={'spacing.3'}
          >
            Recommended aspect ratio 1:1. Max size 5MB.
          </Text>
        </Box>
      )}
    </>
  );
};

export default UploadLogo;
