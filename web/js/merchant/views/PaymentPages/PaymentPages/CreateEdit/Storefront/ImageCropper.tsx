import React, { useEffect, useState } from 'react';
import Cropper, { getInitialCropFromCroppedAreaPercentages } from 'react-easy-crop';
import {
  Box,
  Button,
  Modal,
  ModalBody,
  ModalFooter,
  ModalHeader,
  FullScreenEnterIcon,
  Text,
  BottomSheet,
  BottomSheetBody,
  BottomSheetFooter,
  BottomSheetHeader,
} from '@razorpay/blade/components';
import { CropWrapper, DragWrapperDesktop, DragWrapperMobile } from './styled';
import { getCroppedImg } from './utils';
import Loader from 'common/ui/Loader';
import { IBannerImage } from 'merchant/reducers/paymentPages/types';

interface CropDimensions {
  x: number;
  y: number;
  width: number;
  height: number;
}

interface Crop {
  x: number;
  y: number;
}

interface ImageCropperProps {
  imageSrc: string;
  onCancel: () => void;
  onApply: (croppedImageFile: File, croppedArea: CropDimensions) => void;
  isMobile: boolean;
  selectedBanner: IBannerImage;
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
  onCropComplete: (croppedArea: CropDimensions, croppedAreaPixels: CropDimensions) => void;
  zoom: number;
  setZoom: React.Dispatch<React.SetStateAction<number>>;
  setCrop: (crop: Crop) => void;
  isMobile?: boolean;
  setCropSize: React.Dispatch<React.SetStateAction<{ width: number; height: number }>>;
  setMediaSize: React.Dispatch<
    React.SetStateAction<{
      width: number;
      height: number;
      naturalWidth: number;
      naturalHeight: number;
    }>
  >;
}

const RenderCropper: React.FC<RenderCropperProps> = ({
  imageSrc,
  crop,
  onCropComplete,
  zoom,
  setZoom,
  setCrop,
  isMobile,
  setCropSize,
  setMediaSize,
}) => {
  return (
    <CropWrapper>
      <Cropper
        image={imageSrc}
        crop={crop}
        zoom={zoom}
        aspect={4 / 3}
        onCropChange={setCrop}
        onCropComplete={onCropComplete}
        onZoomChange={setZoom}
        cropSize={{
          width: isMobile ? 360 : 984,
          height: isMobile ? 124 : 180,
        }}
        setCropSize={setCropSize}
        setMediaSize={setMediaSize}
      />
    </CropWrapper>
  );
};

const ImageCropper: React.FC<ImageCropperProps> = ({
  imageSrc,
  onCancel,
  onApply,
  isMobile,
  selectedBanner,
}) => {
  const [crop, setCrop] = useState<Crop>({ x: 0, y: 0 });
  const [rotation, setRotation] = useState<number>(0);
  const [zoom, setZoom] = useState<number>(1);
  const [shouldRenderCropper, setShouldRenderCropper] = useState<boolean>(false);
  const [cropSize, setCropSize] = useState<{ width: number; height: number }>({
    width: 0,
    height: 0,
  });
  const [croppedArea, setCroppedArea] = useState<CropDimensions>(
    selectedBanner && selectedBanner.selected_area
      ? selectedBanner.selected_area
      : { x: 0, y: 0, width: 0, height: 0 },
  );

  const [croppedImageFile, setCroppedImageFile] = useState<File | null>(null);

  const [mediaSize, setMediaSize] = useState<{
    width: number;
    height: number;
    naturalWidth: number;
    naturalHeight: number;
  }>({
    width: 0,
    height: 0,
    naturalWidth: 0,
    naturalHeight: 0,
  });
  const handleCropComplete = async (
    croppedArea: CropDimensions,
    croppedAreaPixels: CropDimensions,
  ) => {
    try {
      const croppedImageFile = await getCroppedImg(imageSrc, croppedAreaPixels, rotation);
      setCroppedArea(croppedArea);
      setCroppedImageFile(croppedImageFile);
    } catch (e) {
      console.error(e);
    }
  };

  useEffect(() => {
    const timer = setTimeout(() => {
      setShouldRenderCropper(true);
    }, 300);
    return () => clearTimeout(timer);
  }, []);

  const computePrevSelectedArea = () => {
    const desiredCroppedAreaPercentages = {
      x: selectedBanner?.selected_area?.x ?? 0,
      y: selectedBanner?.selected_area?.y ?? 0,
      width: 100,
      height: 35,
    };

    const { crop, zoom } = getInitialCropFromCroppedAreaPercentages(
      desiredCroppedAreaPercentages,
      mediaSize,
      0,
      cropSize,
      1,
      3,
    );
    setCrop(crop);
    setZoom(zoom);
  };

  useEffect(() => {
    if (
      selectedBanner &&
      mediaSize.width &&
      mediaSize.height &&
      cropSize.width &&
      cropSize.height
    ) {
      computePrevSelectedArea();
    }
  }, [mediaSize, cropSize, selectedBanner]);

  if (isMobile) {
    return (
      <BottomSheet isOpen={true} onDismiss={onCancel} zIndex={10000} snapPoints={[1, 1, 1]}>
        <BottomSheetHeader />
        <BottomSheetBody padding="spacing.0">
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
              zoom={zoom}
              setZoom={setZoom}
              setCrop={setCrop}
              isMobile={isMobile}
              setMediaSize={setMediaSize}
              setCropSize={setCropSize}
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
                onClick={() => {
                  if (croppedImageFile) {
                    onApply(croppedImageFile, croppedArea);
                  }
                }}
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
        {shouldRenderCropper ? (
          <>
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
              zoom={zoom}
              setZoom={setZoom}
              setCrop={setCrop}
              isMobile={isMobile}
              setMediaSize={setMediaSize}
              setCropSize={setCropSize}
            />
          </>
        ) : (
          <Loader />
        )}
      </ModalBody>
      <ModalFooter>
        <Box display="flex" gap="spacing.3" justifyContent="flex-end" width="100%">
          <RenderFooterButtons
            onApply={() => {
              if (croppedImageFile) {
                onApply(croppedImageFile, croppedArea);
              }
            }}
            onCancel={onCancel}
          />
        </Box>
      </ModalFooter>
    </Modal>
  );
};

export default ImageCropper;
