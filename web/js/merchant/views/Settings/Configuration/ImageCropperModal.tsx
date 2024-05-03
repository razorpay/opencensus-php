import React, { useState, useEffect, useRef } from 'react';
import { Button, Box, Text } from '@razorpay/blade/components';
import Croppie from 'croppie';
import { connect } from 'react-redux';
import { compose } from 'redux';

import { ModalMask, Modal } from 'common/new-ui/Modal';
import { uploadRectLogo } from 'merchant/reducers/config';
import {
  VIEWPORT,
  BOUNDARY,
  SUCCESS,
  UPLOADING_IMAGE,
  CLOSE_TIMEOUT,
  ERROR,
  FILE_UPLOADED_SUCCESSFULLY,
  LOGO,
} from 'merchant/views/Settings/Configuration/constants';
import { showNotification } from 'merchant_common/reducers/notifications';

const ImageCropperModal = ({
  rectangularImageFile,
  showNotification,
  uploadRectLogo,
  closeModal,
}) => {
  let cropperAreaEl = useRef(null);
  const [isLoading, setIsLoading] = useState(false);
  let resize;

  const initImgCropper = (img) => {
    resize = new Croppie(cropperAreaEl, {
      viewport: VIEWPORT,
      boundary: BOUNDARY,
      showZoomer: true,
      enableOrientation: false,
      enableResize: true,
    });

    resize.bind({
      url: img,
    });
  };
  useEffect(() => {
    initImgCropper(rectangularImageFile.file);
  }, []);

  const handleImageUpload = (blob) => {
    const { name: fileName } = rectangularImageFile;

    // API takes file instead of blob, hence saving explicitly;
    const file = new File([blob], fileName, {
      type: blob.type,
    });

    showNotification({
      type: SUCCESS,
      message: UPLOADING_IMAGE,
      closeTimeout: CLOSE_TIMEOUT,
    });
    setIsLoading(true);
    uploadRectLogo(file, LOGO)
      .then(() => {
        showNotification({
          type: SUCCESS,
          message: FILE_UPLOADED_SUCCESSFULLY,
        });
        closeModal();
      })
      .catch(({ errors }) => {
        showNotification({
          type: ERROR,
          message: errors,
        });
      })
      .finally(() => {
        setIsLoading(false);
      });
  };

  const onSaveImage = (e) => {
    e.preventDefault();
    resize.result('blob').then(function cb(blob) {
      handleImageUpload(blob);
    });
  };

  const setRef = (el) => (cropperAreaEl = el);

  return (
    <ModalMask maskClosable={false} className="merchant-80g-details">
      <Modal onClose={closeModal} className="animate-appear ImageCropper">
        <Box marginBottom="10px">
          <Box>
            <Text weight="semibold" size="large" marginBottom="10px">
              Adjust Image
            </Text>
          </Box>
          <Box>
            <Text size="medium">
              Crop and resize image for clear logo visibility with minimal white space.
            </Text>
          </Box>
        </Box>

        <Box>
          <Box ref={setRef} />

          <Box marginLeft="60%" marginTop="10px">
            <Button
              isDisabled={isLoading}
              marginRight="spacing.4"
              variant="primary"
              onClick={closeModal}
            >
              Cancel
            </Button>
            <Button isLoading={isLoading} variant="primary" onClick={onSaveImage}>
              Save
            </Button>
          </Box>
        </Box>
      </Modal>
    </ModalMask>
  );
};

export default compose(connect(null, { showNotification, uploadRectLogo }))(ImageCropperModal);
