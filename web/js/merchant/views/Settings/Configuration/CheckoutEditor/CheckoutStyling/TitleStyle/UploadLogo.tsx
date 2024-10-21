import React, { useEffect, useState } from 'react';
import { Box, CheckCircleIcon, IconButton, Text, TrashIcon } from '@razorpay/blade/components';
import uploadedSvg from 'assets/checkout-editor/title-style/uploaded-image.svg';
import { UploadLogoProps } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/types/titleType';

import { useCheckoutEditor } from 'merchant/views/Settings/Configuration/CheckoutEditor/context';
import {
  EMPTY_LOGO,
  EMPTY_WORDMARK,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context/constants';
import { showNotification } from 'merchant_common/reducers/notifications';

import { ImageSelectedWrapper, ImageSelector } from './styled';

const BRAND_LOGO_SIZE_LIMIT = 5;

const UploadLogo: React.FC<UploadLogoProps> = ({ image, imageRaw, type }) => {
  const { handleLogoChange, handleWordmarkChange } = useCheckoutEditor();

  const [fileName, setFileName] = useState('');

  useEffect(() => {
    const url = image;
    const rawLogo = imageRaw;
    if (url && url.length) {
      const fileName = url.split('/').pop();
      setFileName(fileName ?? '');
    } else if (rawLogo) {
      setFileName(rawLogo.name);
    }
  }, [image, imageRaw]);

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

      if (type === 'logo') {
        handleLogoChange(file, file?.name);
      }
      if (type === 'wordmark') {
        handleWordmarkChange(file, file?.name);
      }
    };
  };

  const isImageEmpty = image === EMPTY_LOGO || image === EMPTY_WORDMARK;

  return (
    <>
      {!isImageEmpty || imageRaw ? (
        <Box width="100%">
          <Text
            weight="semibold"
            size="medium"
            color="surface.text.gray.muted"
            marginBottom="spacing.3"
          >
            Upload Image
          </Text>
          <ImageSelectedWrapper>
            <Box display="flex" justifyContent="center" alignItems="center" gap="spacing.4">
              <img src={uploadedSvg} alt="uploaded-svg" />
              <Box display="flex" justifyContent="center" alignItems="center" gap="spacing.3">
                <Box width="240px">
                  <Text
                    weight="medium"
                    size="medium"
                    color="surface.text.gray.subtle"
                    wordBreak="break-word"
                    truncateAfterLines={1}
                  >
                    {fileName}
                  </Text>
                </Box>
                <CheckCircleIcon size="medium" color="interactive.icon.primary.normal" />
              </Box>
            </Box>
            <IconButton
              icon={() => <TrashIcon size="large" color="interactive.icon.gray.muted" />}
              onClick={() =>
                type === 'logo'
                  ? handleLogoChange(null, EMPTY_LOGO)
                  : handleWordmarkChange(null, EMPTY_WORDMARK)
              }
              accessibilityLabel="delete-logo"
            />
          </ImageSelectedWrapper>
          <Text
            variant="caption"
            weight="medium"
            size="medium"
            color="surface.text.gray.muted"
            marginTop="spacing.3"
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
            marginBottom="spacing.3"
          >
            Upload Logo
          </Text>
          <ImageSelector onClick={onImageUpload} />
          <Text
            variant="caption"
            weight="regular"
            size="medium"
            color="surface.text.gray.muted"
            marginTop="spacing.3"
          >
            Recommended aspect ratio 1:1. Max size 5MB.
          </Text>
        </Box>
      )}
    </>
  );
};

export default UploadLogo;
