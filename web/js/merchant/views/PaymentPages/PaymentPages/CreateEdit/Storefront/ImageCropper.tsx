import React, { useRef, useState, useCallback } from 'react';
import ReactCrop, { Crop, PixelCrop } from 'react-image-crop';
import 'react-image-crop/dist/ReactCrop.css';
import {
  Box,
  Button,
  Modal,
  ModalBody,
  ModalFooter,
  ModalHeader,
  ToastContainer,
  FullScreenEnterIcon,
  Text,
} from '@razorpay/blade/components';
import { CropWrapper, DragWrapperDesktop, DragWrapperMobile } from './styled';
import PaymentPagesDrawer from 'merchant/views/PaymentPages/common/Drawer';

interface ImageCropperProps {
  imageSrc: string;
  onCropComplete: (croppedFile: File) => void;
  onCancel: () => void;
  onApply: () => void;
  isMobile: boolean;
}

const ImageCropper: React.FC<ImageCropperProps> = ({
  imageSrc,
  onCropComplete,
  onCancel,
  onApply,
  isMobile,
}) => {
  const [crop, setCrop] = useState<Crop>({
    unit: '%',
    x: 0,
    y: 0,
    width: 50,
    height: 50,
  });
  const imageRef = useRef<HTMLImageElement | null>(null);

  const generateCroppedImage = async (
    image: HTMLImageElement,
    crop: PixelCrop,
    fileName: string,
  ): Promise<File | null> => {
    try {
      const canvas = document.createElement('canvas');
      const scaleX = image.naturalWidth / image.width || 1;
      const scaleY = image.naturalHeight / image.height || 1;
      canvas.width = crop?.width || 0;
      canvas.height = crop?.height || 0;

      const ctx = canvas.getContext('2d');
      if (!ctx) {
        throw new Error('Failed to get canvas context');
      }

      ctx.drawImage(
        image,
        crop?.x * scaleX || 0,
        crop?.y * scaleY || 0,
        (crop?.width || 0) * scaleX,
        (crop?.height || 0) * scaleY,
        0,
        0,
        crop?.width || 0,
        crop?.height || 0,
      );

      return new Promise((resolve, reject) => {
        canvas.toBlob((blob) => {
          if (!blob) {
            reject(new Error('Canvas is empty or could not create a blob'));
            return;
          }
          resolve(new File([blob], fileName, { type: 'image/jpeg' }));
        }, 'image/jpeg');
      });
    } catch (error) {
      console.error('Error generating cropped image:', error);
      return null;
    }
  };

  const handleImageLoad = useCallback((image: HTMLImageElement) => {
    imageRef.current = image;
  }, []);

  const handleCropChange = useCallback((crop: Crop) => {
    setCrop(crop);
  }, []);

  const handleCropComplete = async (crop: PixelCrop) => {
    if (imageRef.current && crop.width && crop.height) {
      const croppedFile = await generateCroppedImage(imageRef.current, crop, 'croppedImage.jpeg');
      if (croppedFile) onCropComplete(croppedFile);
    }
  };

  const renderFooterButtons = () => (
    <>
      <Button variant="tertiary" onClick={onCancel} size="medium" color="primary">
        Cancel
      </Button>
      <Button onClick={onApply}>Apply</Button>
    </>
  );

  const renderCropper = () => (
    <CropWrapper>
      <ReactCrop
        src={imageSrc}
        crop={crop}
        ruleOfThirds
        onImageLoaded={handleImageLoad}
        onComplete={handleCropComplete}
        onChange={handleCropChange}
      />
    </CropWrapper>
  );

  if (isMobile) {
    return (
      <PaymentPagesDrawer
        showCloseBtn={false}
        footerButtons={renderFooterButtons()}
        maskClosable={false}
        onClose={onCancel}
        top="0px"
        hasTransparentBackground={true}
        isMobileCropper={true}
      >
        <ToastContainer />
        <Box
          display="flex"
          justifyContent="center"
          alignItems="center"
          flexDirection="column"
          height="calc(100vh - 45px - 120px)"
        >
          <DragWrapperMobile>
            <FullScreenEnterIcon color="currentColor" />
            <Text
              color="surface.text.staticWhite.normal"
              variant="body"
              size="small"
              weight="regular"
            >
              Drag to set the cover
            </Text>
          </DragWrapperMobile>
          {renderCropper()}
        </Box>
      </PaymentPagesDrawer>
    );
  }

  return (
    <Modal isOpen={true} onDismiss={onCancel} size="large">
      <ModalHeader title="Set the store banner" />
      <ModalBody>
        <DragWrapperDesktop>
          <FullScreenEnterIcon color="currentColor" />
          <Text
            color="surface.text.staticWhite.normal"
            variant="body"
            size="small"
            weight="regular"
          >
            Drag to set the cover
          </Text>
        </DragWrapperDesktop>
        {renderCropper()}
      </ModalBody>
      <ModalFooter>
        <Box display="flex" gap="spacing.3" justifyContent="flex-end" width="100%">
          {renderFooterButtons()}
        </Box>
      </ModalFooter>
    </Modal>
  );
};

export default ImageCropper;
