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
  BottomSheet,
  BottomSheetBody,
  BottomSheetFooter,
  BottomSheetHeader,
} from '@razorpay/blade/components';
import { CropWrapper, DragWrapperDesktop, DragWrapperMobile } from './styled';

interface ImageCropperProps {
  imageSrc: string;
  onCropComplete: (croppedFile: File) => void;
  onCancel: () => void;
  onApply: () => void;
  isMobile: boolean;
}

interface RenderFooterButtonsProps {
  onCancel: () => void;
  onApply: () => void;
}

const RenderFooterButtons: React.FC<RenderFooterButtonsProps> = ({ onCancel, onApply }) => (
  <>
    <Button variant="tertiary" onClick={onCancel} size="medium" color="primary">
      Cancel
    </Button>
    <Button onClick={onApply}>Apply</Button>
  </>
);

interface RenderCropperProps {
  imageSrc: string;
  crop: Crop;
  onCropComplete: (crop: PixelCrop) => void;
  onCropChange: (crop: Crop) => void;
  onImageLoaded: (image: HTMLImageElement) => void;
}

const RenderCropper: React.FC<RenderCropperProps> = ({
  imageSrc,
  crop,
  onCropComplete,
  onCropChange,
  onImageLoaded,
}) => {
  return (
    <CropWrapper>
      <ReactCrop
        src={imageSrc}
        crop={crop}
        ruleOfThirds
        onImageLoaded={onImageLoaded}
        onComplete={onCropComplete}
        onChange={onCropChange}
      />
    </CropWrapper>
  );
};

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

  if (isMobile) {
    return (
      <BottomSheet isOpen={true} onDismiss={onCancel} zIndex={10000} snapPoints={[1, 1, 1]}>
        <BottomSheetHeader />
        <BottomSheetBody padding="spacing.0">
          <ToastContainer />
          <Box
            display="flex"
            justifyContent="center"
            alignItems="center"
            flexDirection="column"
            height="calc(100vh - 80px)"
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
            <RenderCropper
              imageSrc={imageSrc}
              crop={crop}
              onCropComplete={handleCropComplete}
              onCropChange={handleCropChange}
              onImageLoaded={handleImageLoad}
            />
          </Box>
        </BottomSheetBody>
        <BottomSheetFooter>
          <Box display="flex" alignItems="center" gap="spacing.5">
            <Box flex={1}>
              <Button
                isFullWidth
                size="medium"
                type="button"
                variant="secondary"
                key="cancel"
                onClick={onCancel}
              >
                Cancel
              </Button>
            </Box>
            <Box flex={1}>
              <Button
                isFullWidth
                size="medium"
                type="button"
                variant="primary"
                key="submit"
                onClick={onApply}
              >
                Save
              </Button>
            </Box>
          </Box>
        </BottomSheetFooter>
      </BottomSheet>
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
        <RenderCropper
          imageSrc={imageSrc}
          crop={crop}
          onCropComplete={handleCropComplete}
          onCropChange={handleCropChange}
          onImageLoaded={handleImageLoad}
        />
      </ModalBody>
      <ModalFooter>
        <Box display="flex" gap="spacing.3" justifyContent="flex-end" width="100%">
          <RenderFooterButtons onApply={onApply} onCancel={onCancel} />
        </Box>
      </ModalFooter>
    </Modal>
  );
};

export default ImageCropper;
